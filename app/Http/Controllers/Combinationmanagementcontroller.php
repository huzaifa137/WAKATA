<?php

namespace App\Http\Controllers;

use App\Models\Combination;
use App\Models\MasterData;
use App\Models\StudentCombination;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * CRUD management for UACE combinations (e.g. PCM, HEG) and which
 * principal subjects belong to each. This is the "source of truth" screen
 * that feeds:
 *   - Subject Registration's per-student Combination dropdown (UACE)
 *   - The UACE Excel template/import
 *   - Reports (passlip / analysed report "Combination" field)
 *
 * Combinations only apply to categories that use them — currently UACE.
 */
class CombinationManagementController extends Controller
{
    private $categories = [
        'UACE' => 'UACE (A-LEVEL)',
    ];

    public function index()
    {
        
        $categories = $this->categories;
        $combinations = [];

        foreach ($categories as $code => $label) {
            $rows = Combination::with('subjects')
                ->where('category', $code)
                ->orderBy('code')
                ->get();

            $studentCounts = StudentCombination::whereIn('combination_id', $rows->pluck('id'))
                ->selectRaw('combination_id, count(*) as total')
                ->groupBy('combination_id')
                ->pluck('total', 'combination_id');

            $combinations[$code] = $rows->map(function ($row) use ($studentCounts) {
                $row->student_count = $studentCounts[$row->id] ?? 0;
                return $row;
            });
        }

        // Subjects available to attach, per category, split into the two
        // pools a combination is built from:
        //   - principal: exactly 3 required per combination
        //   - subsidiary: at most 1 per combination
        // Compulsory subjects (General Paper) are auto-registered separately
        // and never belong to a combination. Subjects created before the
        // Principal/Subsidiary role existed have no md_misc4 — treat those
        // as Principal so they don't just disappear from the screen.
        $availableSubjects = [];
        foreach ($categories as $code => $label) {
            $optional = MasterData::where('md_master_code_id', $this->masterCodeFor($code))
                ->where('md_misc1', 'Optional')
                ->where(function ($q) {
                    $q->whereNull('md_misc2')->orWhere('md_misc2', '!=', 'Inactive');
                })
                ->orderBy('md_name')
                ->get();

            $availableSubjects[$code] = [
                'principal' => $optional->filter(fn($s) => $s->md_misc4 !== 'Subsidiary')->values(),
                'subsidiary' => $optional->filter(fn($s) => $s->md_misc4 === 'Subsidiary')->values(),
            ];
        }

        return view('itemGrading.combination-management.index', compact('categories', 'combinations', 'availableSubjects'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateCombination($request);

        $code = strtoupper($validated['code']);

        $exists = Combination::where('category', $validated['category'])
            ->where('code', $code)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['code' => "Combination code '{$code}' already exists under {$this->categories[$validated['category']]}."])
                ->withInput();
        }

        $subjectIds = array_values(array_filter(array_merge(
            $validated['principal_subject_ids'],
            [$validated['subsidiary_subject_id'] ?? null]
        )));

        $combination = Combination::create([
            'category' => $validated['category'],
            'code' => $code,
            'name' => $this->nameFromSubjectIds($subjectIds),
            'status' => 'Active',
        ]);

        $combination->subjects()->sync($subjectIds);

        return back()->with('success', "Combination '{$code}' created.");
    }

    public function update(Request $request, $id)
    {
        $combination = Combination::findOrFail($id);

        $validated = $this->validateCombination($request);

        $code = strtoupper($validated['code']);

        $duplicate = Combination::where('category', $combination->category)
            ->where('code', $code)
            ->where('id', '!=', $combination->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['code' => "Combination code '{$code}' is already used by another combination."])->withInput();
        }

        $subjectIds = array_values(array_filter(array_merge(
            $validated['principal_subject_ids'],
            [$validated['subsidiary_subject_id'] ?? null]
        )));

        $combination->update([
            'code' => $code,
            'name' => $this->nameFromSubjectIds($subjectIds),
        ]);

        $combination->subjects()->sync($subjectIds);

        return back()->with('success', "Combination '{$code}' updated.");
    }

    /**
     * Shared validation for store()/update(): exactly 3 Principal subjects
     * (from md_misc4 = 'Principal', or legacy subjects with no role set)
     * plus an optional single Subsidiary subject.
     */
    private function validateCombination(Request $request): array
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys($this->categories))],
            'code' => ['required', 'string', 'max:10', 'alpha_num'],
            'principal_subject_ids' => ['required', 'array', 'size:3'],
            'principal_subject_ids.*' => ['integer', 'distinct', 'exists:master_datas,md_id'],
            'subsidiary_subject_id' => ['nullable', 'integer', 'exists:master_datas,md_id'],
        ], [
            'principal_subject_ids.size' => 'A combination needs exactly 3 principal subjects.',
        ]);

        $masterCodeId = $this->masterCodeFor($validated['category']);

        $principalCount = MasterData::where('md_master_code_id', $masterCodeId)
            ->whereIn('md_id', $validated['principal_subject_ids'])
            ->where('md_misc1', 'Optional')
            ->where(function ($q) {
                $q->whereNull('md_misc4')->orWhere('md_misc4', '!=', 'Subsidiary');
            })
            ->count();

        if ($principalCount !== 3) {
            throw ValidationException::withMessages([
                'principal_subject_ids' => 'The 3 selected subjects must be Optional Principal subjects under ' . $this->categories[$validated['category']] . '.',
            ]);
        }

        if (!empty($validated['subsidiary_subject_id'])) {
            $isSubsidiary = MasterData::where('md_master_code_id', $masterCodeId)
                ->where('md_id', $validated['subsidiary_subject_id'])
                ->where('md_misc1', 'Optional')
                ->where('md_misc4', 'Subsidiary')
                ->exists();

            if (!$isSubsidiary) {
                throw ValidationException::withMessages([
                    'subsidiary_subject_id' => 'The selected subsidiary subject is not marked as Subsidiary in Subject Management.',
                ]);
            }
        }

        return $validated;
    }

    /** Builds "Physics, Chemistry, Mathematics[, Subsidiary Mathematics]" from subject ids, in the order given. */
    private function nameFromSubjectIds(array $subjectIds): string
    {
        $names = MasterData::whereIn('md_id', $subjectIds)->pluck('md_name', 'md_id');

        return collect($subjectIds)
            ->map(fn($id) => $names[$id] ?? null)
            ->filter()
            ->implode(', ');
    }

    /**
     * Flip a combination between Active / Inactive. Inactive combinations
     * disappear from the Subject Registration dropdown, the Excel
     * template's validation list, and the import's accepted-codes list —
     * but students already assigned to it, and their subject
     * registrations, are left untouched.
     */
    public function toggleStatus($id)
    {
        $combination = Combination::findOrFail($id);

        $combination->status = $combination->status === 'Inactive' ? 'Active' : 'Inactive';
        $combination->save();

        return response()->json([
            'success' => true,
            'status' => $combination->status,
            'message' => "Combination marked {$combination->status}.",
        ]);
    }

    /**
     * Hard-delete a combination — only allowed when no student has ever
     * been assigned to it. Otherwise, deactivate instead so historic
     * passlips/reports still resolve the combination name correctly.
     */
    public function destroy($id)
    {
        $combination = Combination::findOrFail($id);

        $inUse = StudentCombination::where('combination_id', $id)->exists();

        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'This combination already has students assigned to it. Deactivate it instead of deleting, to keep historic records intact.',
            ], 422);
        }

        $combination->subjects()->detach();
        $combination->delete();

        return response()->json(['success' => true, 'message' => 'Combination deleted.']);
    }

    private function masterCodeFor(string $category): int
    {
        return $category === 'UACE'
            ? config('constants.options.UACEPapers')
            : config('constants.options.UCEPapers');
    }
}