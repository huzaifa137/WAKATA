<?php

namespace App\Http\Controllers;

use App\Models\House;
use App\Models\SchoolPassword;
use App\Models\User; // adjust to your contacts model
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class HouseController extends Controller
{
    /**
     * Show the create form.
     */
    public function create()
    {
        $nextNumber = $this->generateNextNumber();
        $contacts = User::orderBy('name')->get(); // adjust model/query as needed

        return view('houses.create', compact('nextNumber', 'contacts'));
    }

    /**
     * Store a new house.
     *
     * School numbers (IT-001, IT-002, ...) are derived by reading the
     * current max out of the table — with two admins registering a school
     * at the same moment (one via /houses/create, one via
     * /users/school-register — both post here), it's possible for both
     * requests to read the same max and try to save the same number.
     * `houses.Number` has a DB-level UNIQUE constraint, so that used to
     * surface as an unhandled 500 for whichever request lost the race,
     * silently dropping that school's registration.
     *
     * Fixed two ways, together:
     *   1. The number is (re-)computed inside a transaction with
     *      `lockForUpdate()`, so once one request has grabbed IT-005,
     *      a concurrent request reading the max blocks until the first
     *      commits, then correctly sees IT-005 taken and moves to IT-006.
     *   2. As a safety net for the one case locking can't cover — two
     *      requests racing on a *brand new/empty* table, where there's no
     *      existing row yet to lock — we catch the duplicate-key error
     *      and simply retry with the next number, instead of letting it
     *      bubble up as a crash.
     */
    public function store(Request $request)
    {
        $request->validate([
            'House' => 'required|string|max:255',
            'Location' => 'required|string|max:100',
            'Category' => 'required|string|in:Answer Sheets,No Answer Sheets',
            'AdministratorNames' => 'required|string|max:255',
            'AdministratorTelephones' => 'required|string|max:20',
            'Title' => 'required|string|max:255',
        ]);

        $defaultPassword = '123456789';
        $hashedPassword = Hash::make($defaultPassword);

        $maxAttempts = 5;
        $house = null;
        $numberString = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                [$house, $numberString] = DB::transaction(function () use ($request, $defaultPassword, $hashedPassword) {
                    $nextNumber = $this->generateNextNumber(lock: true);
                    $numberString = 'IT-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

                    $house = House::create([
                        'House' => strtoupper(trim($request->House)),
                        'Number' => $numberString,
                        'Location' => $request->Location,
                        'district' => $request->Location, // Store in both columns for backward compatibility
                        'RegistrationDate' => now(),
                        'Head' => 0,
                        'ContactPerson' => 0,
                        'administrator_names' => $request->AdministratorNames,
                        'administrator_telephones' => $request->AdministratorTelephones,
                        'title' => $request->Title,
                        'category' => $request->Category,
                        'school_status' => 1, // Default status
                    ]);

                    SchoolPassword::create([
                        'school_id' => $numberString,
                        'phonenumber' => $request->AdministratorTelephones,
                        'password_plain' => $defaultPassword,
                        'password_hashed' => $hashedPassword,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return [$house, $numberString];
                });

                break; // success
            } catch (QueryException $e) {
                $isDuplicateNumber = $e->getCode() === '23000'
                    && stripos($e->getMessage(), 'Number') !== false;

                if (!$isDuplicateNumber || $attempt === $maxAttempts) {
                    throw $e; // some other DB error, or we've genuinely run out of retries
                }
                // Another request grabbed this number between our lock window
                // and the commit — loop around and try the next one.
            }
        }

        // Calculate the next number AFTER saving, for the badge refresh
        $newNext = 'IT-' . str_pad($this->generateNextNumber(), 3, '0', STR_PAD_LEFT);

        return response()->json([
            'message' => "School '{$house->House}' has been added successfully.",
            'house' => $house,
            'next_number' => $newNext,
            'password' => $defaultPassword, // Optional: Include in response if needed
        ]);
    }

    /**
     * Get the next sequential number by reading the highest IT-XXX in the DB.
     * Returns an integer (e.g. 6 when the highest is IT-005).
     *
     * @param bool $lock  When true, takes a row lock (`FOR UPDATE`) on the
     *                     matching row so concurrent transactions calling
     *                     this with $lock=true block until this one commits.
     *                     Only pass true from inside store()'s DB::transaction —
     *                     it's a no-op outside a transaction and just wastes
     *                     a lock that's released immediately.
     */
    private function generateNextNumber(bool $lock = false): int
    {
        $query = House::where('Number', 'LIKE', 'IT-%')
            ->orderByRaw('CAST(SUBSTRING(Number, 4) AS UNSIGNED) DESC');

        if ($lock) {
            $query->lockForUpdate();
        }

        $last = $query->value('Number');

        if (!$last) {
            return 1;
        }

        $lastInt = (int) ltrim(substr($last, 3), '0');
        return $lastInt + 1;
    }
}