<?php

namespace App\Http\Controllers;

use App\Exports\StudentBulkImportTemplateExport;
use App\Imports\StudentBulkImportImport;
use App\Models\ClassAllocation;
use App\Models\House;
use App\Models\Mark;
use App\Models\StudentBasic;
use App\Models\StudentResult;
use App\Models\StudentSubjectRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class StudentBulkImportController extends Controller
{
    /** Every examination category that can have students bulk-imported. */
    private $categories = [
        // 'TH' => 'ALevel',
        // 'ID' => 'OLevel',
        // 'PLE' => 'Primary (PLE)',
        'UCE' => 'UCE (O-LEVEL)',
        'UACE' => 'UACE (A-LEVEL)',
    ];

    /**
     * Selection screen: pick year / category / school.
     */
    public function index()
    {
        $houses = House::select('ID', 'House', 'Number')->get();
        $categories = $this->categories;

        return view('itemGrading.student-bulk-import.select', compact('houses', 'categories'));
    }

    /**
     * Management screen for a chosen year/category/school: shows students
     * already imported, plus the download-template / upload-import tools.
     */
    public function manage(Request $request, string $portal = 'bureau')
    {
        $request->validate([
            'year' => 'required|digits:4',
            'category' => 'required|in:TH,ID,PLE,UCE,UACE',
            'school_id' => 'required|integer',
        ]);

        $year = $request->year;
        $category = $request->category;
        $schoolId = $request->school_id;

        $house = House::findOrFail($schoolId);
        $schoolNumber = $house->Number;
        $schoolName = $house->House;

        $students = ClassAllocation::where('Student_ID', 'LIKE', "$schoolNumber-%")
            ->where('Student_ID', 'LIKE', "%-$category-%")
            ->where('Student_ID', 'LIKE', "%-$year")
            ->select('Student_ID')
            ->distinct()
            ->orderBy('Student_ID')
            ->get();

        $studentRows = StudentBasic::whereIn('Student_ID', $students->pluck('Student_ID'))
            ->orderBy('Student_ID')
            ->get(['Student_ID', 'Student_Name', 'StudentSex']);

        return view('itemGrading.student-bulk-import.manage', compact(
            'studentRows',
            'year',
            'category',
            'schoolId',
            'schoolNumber',
            'schoolName',
            'portal'
        ));
    }

    /**
     * Download the fill-in Excel template for a year/category/school.
     */
    public function downloadTemplate(Request $request)
    {
        $request->validate([
            'year' => 'required|digits:4',
            'category' => 'required|in:TH,ID,PLE,UCE,UACE',
            'school_number' => 'required|string',
        ]);

        $filename = "student-import-{$request->category}-{$request->school_number}-{$request->year}.xlsx";

        return Excel::download(
            new StudentBulkImportTemplateExport($request->category, $request->year, $request->school_number),
            $filename
        );
    }

    /**
     * Import a filled-in Excel template: creates Student_ID + students_basic
     * + class_allocation rows for every valid row.
     */
    public function import(Request $request, string $redirectRoute = 'student.bulk.import.manage')
    {
        $request->validate([
            'year' => 'required|digits:4',
            'category' => 'required|in:TH,ID,PLE,UCE,UACE',
            'school_id' => 'required|integer',
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $house = House::findOrFail($request->school_id);
        $schoolNumber = $house->Number;

        $import = new StudentBulkImportImport(
            $request->category,
            $request->year,
            (string) $request->school_id,
            $schoolNumber
        );

        try {
            Excel::import($import, $request->file('file'));
        } catch (ValidationException $e) {
            return back()->withErrors(['import_errors' => $e->errors()])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['import_errors' => ['Could not read the file: ' . $e->getMessage()]])->withInput();
        }

        $summary = $import->getSummary();

        return redirect()
            ->route($redirectRoute, [
                'year' => $request->year,
                'category' => $request->category,
                'school_id' => $request->school_id,
            ])
            ->with('success', "Imported {$summary['students_processed']} student(s).")
            ->with('import_skipped', $summary['skipped']);
    }

    /**
     * Update a single imported student's Full Name and/or Sex.
     * Used by the "Edit" modal on the manage screen — Student_ID itself is
     * never changed here since it's auto-generated and referenced by
     * subject registrations, marks, and results.
     */
    public function updateStudent(Request $request, string $studentId, string $redirectRoute = 'student.bulk.import.manage')
    {
        $request->validate([
            'year' => 'required|digits:4',
            'category' => 'required|in:TH,ID,PLE,UCE,UACE',
            'school_id' => 'required|integer',
            'student_name' => 'required|string|max:255',
            'student_sex' => 'required|in:Male,Female',
        ]);

        $student = StudentBasic::where('Student_ID', $studentId)->first();

        if (!$student) {
            return redirect()
                ->route($redirectRoute, [
                    'year' => $request->year,
                    'category' => $request->category,
                    'school_id' => $request->school_id,
                ])
                ->withErrors(['import_errors' => ["Student {$studentId} was not found."]]);
        }

        $student->update([
            'Student_Name' => trim($request->student_name),
            'StudentSex' => $request->student_sex,
        ]);

        return redirect()
            ->route($redirectRoute, [
                'year' => $request->year,
                'category' => $request->category,
                'school_id' => $request->school_id,
            ])
            ->with('success', "Updated {$studentId}.");
    }

    /**
     * Delete a single imported student (and everything that hangs off
     * their Student_ID: class allocation, subject registrations, marks,
     * results). Used to undo an accidental double-import of one row, or
     * to remove a student who was entered by mistake.
     */
    public function destroyStudent(Request $request, string $studentId, string $redirectRoute = 'student.bulk.import.manage')
    {
        $request->validate([
            'year' => 'required|digits:4',
            'category' => 'required|in:TH,ID,PLE,UCE,UACE',
            'school_id' => 'required|integer',
        ]);

        DB::transaction(function () use ($studentId) {
            StudentSubjectRegistration::where('student_id', $studentId)->delete();
            Mark::where('student_id', $studentId)->delete();
            StudentResult::where('student_id', $studentId)->delete();
            ClassAllocation::where('Student_ID', $studentId)->delete();
            StudentBasic::where('Student_ID', $studentId)->delete();
        });

        return redirect()
            ->route($redirectRoute, [
                'year' => $request->year,
                'category' => $request->category,
                'school_id' => $request->school_id,
            ])
            ->with('success', "Deleted student {$studentId} and all of their records.");
    }

    /**
     * Wipe every student imported for a given year/category/school in one
     * go — the "start over" button for when a template was imported twice
     * or the wrong file was uploaded entirely.
     */
    public function destroyAll(Request $request, string $redirectRoute = 'student.bulk.import.manage')
    {
        $request->validate([
            'year' => 'required|digits:4',
            'category' => 'required|in:TH,ID,PLE,UCE,UACE',
            'school_id' => 'required|integer',
        ]);

        $house = House::findOrFail($request->school_id);
        $schoolNumber = $house->Number;
        $year = $request->year;
        $category = $request->category;

        $studentIds = ClassAllocation::where('Student_ID', 'LIKE', "$schoolNumber-%")
            ->where('Student_ID', 'LIKE', "%-$category-%")
            ->where('Student_ID', 'LIKE', "%-$year")
            ->pluck('Student_ID');

        $count = $studentIds->count();

        DB::transaction(function () use ($studentIds) {
            StudentSubjectRegistration::whereIn('student_id', $studentIds)->delete();
            Mark::whereIn('student_id', $studentIds)->delete();
            StudentResult::whereIn('student_id', $studentIds)->delete();
            ClassAllocation::whereIn('Student_ID', $studentIds)->delete();
            StudentBasic::whereIn('Student_ID', $studentIds)->delete();
        });

        return redirect()
            ->route($redirectRoute, [
                'year' => $year,
                'category' => $category,
                'school_id' => $request->school_id,
            ])
            ->with('success', "Cleared {$count} student(s) for {$category} {$schoolNumber} {$year}. You can now re-import a clean file.");
    }

    // ---- School portal entry points (school locked server-side from session) ----

    private function loggedInHouse(): ?House
    {
        return House::find(session('LoggedSchool'));
    }

    /**
     * Selection screen for the school portal: year + category only — the
     * school itself is never a choice, it's always the logged-in one.
     */
    public function schoolIndex()
    {
        return view('itemGrading.student-bulk-import.select', [
            'houses' => collect(),
            'categories' => $this->categories,
            'lockedSchool' => $this->loggedInHouse(),
        ]);
    }

    public function schoolManage(Request $request)
    {
        $house = $this->loggedInHouse();
        $request->merge(['school_id' => $house->ID ?? session('LoggedSchool')]);

        return $this->manage($request, 'school');
    }

    public function schoolDownloadTemplate(Request $request)
    {
        $house = $this->loggedInHouse();
        $request->merge(['school_number' => $house->Number ?? session('LoggedSchoolCode')]);

        return $this->downloadTemplate($request);
    }

    public function schoolImport(Request $request)
    {
        $house = $this->loggedInHouse();
        $request->merge(['school_id' => $house->ID ?? session('LoggedSchool')]);

        return $this->import($request, 'school.student.bulk.import.manage');
    }

    /** A school account may only ever touch its own students. */
    private function assertOwnedByLoggedInSchool(string $studentId): void
    {
        $schoolNumber = $this->loggedInHouse()->Number ?? session('LoggedSchoolCode');

        abort_unless(str_starts_with($studentId, "{$schoolNumber}-"), 403, 'This student does not belong to your school.');
    }

    public function schoolUpdateStudent(Request $request, string $studentId)
    {
        $this->assertOwnedByLoggedInSchool($studentId);
        $house = $this->loggedInHouse();
        $request->merge(['school_id' => $house->ID ?? session('LoggedSchool')]);

        return $this->updateStudent($request, $studentId, 'school.student.bulk.import.manage');
    }

    public function schoolDestroyStudent(Request $request, string $studentId)
    {
        $this->assertOwnedByLoggedInSchool($studentId);
        $house = $this->loggedInHouse();
        $request->merge(['school_id' => $house->ID ?? session('LoggedSchool')]);

        return $this->destroyStudent($request, $studentId, 'school.student.bulk.import.manage');
    }

    public function schoolDestroyAll(Request $request)
    {
        $house = $this->loggedInHouse();
        $request->merge(['school_id' => $house->ID ?? session('LoggedSchool')]);

        return $this->destroyAll($request, 'school.student.bulk.import.manage');
    }
}