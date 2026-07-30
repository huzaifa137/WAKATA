<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * student_registrations can be CREATED offline (a school registering a
 * new student while disconnected). Two different schools' offline
 * installs could both mint auto-increment id 501 for different students,
 * so — unlike Mark/MarkPaper, which already have a natural key — this
 * table needs its own collision-proof identifier for sync matching.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
        });

        // Backfill existing rows so sync matching never has to compare
        // against a null uuid (which never matches itself in SQL).
        DB::table('student_registrations')->whereNull('uuid')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('student_registrations')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
