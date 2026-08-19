<?php

namespace App\Http\Controllers;

use App\Exports\ScoreSheetScanExport;
use App\Models\ScoreEntry;
use App\Models\ScoreSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Standalone "Scan & Export Score Sheet" admin page.
 *
 * This is the wakata-scanner project's Scan -> Review/Edit -> Export flow,
 * ported into WAKATA as its own full page (not a modal), styled to match
 * the "Scan & Auto-Fill Marks" modal already used on the Enter Marks screen
 * (see resources/views/itemGrading/results.blade.php).
 *
 * OCR extraction itself is NOT duplicated here — the page's "Run OCR &
 * Extract Data" step calls the existing ScoreScanController@scan endpoint
 * (POST /iteb/scan-score-sheet), the same one the Enter Marks modal uses.
 * This controller only adds what that one doesn't do: persisting the
 * reviewed rows to the database ("Import") and generating the .xlsx
 * download ("Export"), using maatwebsite/excel (already a dependency of
 * this project).
 */
class ScoreSheetExportController extends Controller
{
    // -----------------------------------------------------------------------
    // PAGE
    // -----------------------------------------------------------------------
    public function index()
    {
        $recentSheets = ScoreSheet::withCount('entries')->latest()->take(10)->get();

        return view('ScoreExport.index', compact('recentSheets'));
    }

    // -----------------------------------------------------------------------
    // IMPORT — save the reviewed/edited rows to the database
    // -----------------------------------------------------------------------
    public function save(Request $request)
    {
        $request->validate([
            'school_name' => 'nullable|string|max:255',
            'zone' => 'nullable|string|max:100',
            'ref_no' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:255',
            'exam_year' => 'nullable|string|max:10',
            'source_file' => 'nullable|string|max:255',
            'scan_type' => 'nullable|in:pdf,image',
            'entries' => 'required|array|min:1',
            'entries.*.candidate_name' => 'required|string|max:255',
            'entries.*.p1' => 'nullable|numeric',
            'entries.*.p2' => 'nullable|numeric',
            'entries.*.p3' => 'nullable|numeric',
            'entries.*.p4' => 'nullable|numeric',
            'entries.*.average' => 'nullable|numeric',
            'entries.*.grade' => 'nullable|string|max:10',
        ]);

        DB::beginTransaction();
        try {
            $sheet = ScoreSheet::create([
                'school_name' => $request->input('school_name'),
                'zone' => $request->input('zone'),
                'ref_no' => $request->input('ref_no'),
                'subject' => $request->input('subject'),
                'exam_year' => $request->input('exam_year'),
                'source_file' => $request->input('source_file'),
                'scan_type' => $request->input('scan_type', 'pdf'),
                'created_by' => Session('LoggedAdmin'),
            ]);

            foreach ($request->input('entries') as $index => $entry) {
                if (empty(trim($entry['candidate_name'] ?? ''))) {
                    continue;
                }
                ScoreEntry::create([
                    'score_sheet_id' => $sheet->id,
                    'serial_no' => $entry['serial_no'] ?? ($index + 1),
                    'candidate_name' => trim($entry['candidate_name']),
                    'p1' => $entry['p1'] ?? null,
                    'p2' => $entry['p2'] ?? null,
                    'p3' => $entry['p3'] ?? null,
                    'p4' => $entry['p4'] ?? null,
                    'average' => $entry['average'] ?? null,
                    'grade' => $entry['grade'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'sheet_id' => $sheet->id,
                'saved_rows' => $sheet->entries()->count(),
                'message' => 'Score sheet saved successfully!',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Score sheet save failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // -----------------------------------------------------------------------
    // EXPORT (unsaved preview) — Step-2 review table -> .xlsx, no DB write
    // -----------------------------------------------------------------------
    public function exportPreview(Request $request)
    {
        $request->validate([
            'school_name' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'entries' => 'required|array|min:1',
            'entries.*.candidate_name' => 'required|string|max:255',
            'entries.*.p1' => 'nullable|numeric',
            'entries.*.p2' => 'nullable|numeric',
            'entries.*.p3' => 'nullable|numeric',
            'entries.*.p4' => 'nullable|numeric',
            'entries.*.average' => 'nullable|numeric',
            'entries.*.grade' => 'nullable|string|max:10',
        ]);

        $meta = [
            'school_name' => $request->input('school_name'),
            'subject' => $request->input('subject'),
        ];

        $entries = collect($request->input('entries'))
            ->filter(fn ($e) => trim($e['candidate_name'] ?? '') !== '')
            ->values()
            ->all();

        if (empty($entries)) {
            return response()->json(['success' => false, 'message' => 'No candidate rows to export.'], 422);
        }

        $filename = $this->exportFilename($meta['school_name'], $meta['subject']);

        return Excel::download(new ScoreSheetScanExport($meta, $entries), $filename);
    }

    // -----------------------------------------------------------------------
    // EXPORT (saved record) — a stored ScoreSheet -> .xlsx
    // -----------------------------------------------------------------------
    public function exportSaved(ScoreSheet $scoreSheet)
    {
        $scoreSheet->load('entries');

        $meta = [
            'school_name' => $scoreSheet->school_name,
            'subject' => $scoreSheet->subject,
        ];

        $entries = $scoreSheet->entries->sortBy('serial_no')->map(fn ($e) => [
            'candidate_name' => $e->candidate_name,
            'p1' => $e->p1,
            'p2' => $e->p2,
            'p3' => $e->p3,
            'p4' => $e->p4,
            'average' => $e->average,
            'grade' => $e->grade,
        ])->values()->all();

        if (empty($entries)) {
            return back()->with('error', 'No candidate rows to export.');
        }

        $filename = $this->exportFilename($meta['school_name'], $meta['subject'], $scoreSheet->id);

        return Excel::download(new ScoreSheetScanExport($meta, $entries), $filename);
    }

    public function destroy(ScoreSheet $scoreSheet)
    {
        $scoreSheet->delete();

        return response()->json(['success' => true, 'message' => 'Record deleted.']);
    }

    private function exportFilename(?string $schoolName, ?string $subject, ?int $sheetId = null): string
    {
        $base = trim(($schoolName ?: 'score_sheet') . '_' . ($subject ?: ''));
        $base = preg_replace('/[^A-Za-z0-9]+/', '_', $base);
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'score_sheet';
        }
        if ($sheetId) {
            $base .= '_' . $sheetId;
        }

        return strtolower($base) . '.xlsx';
    }
}