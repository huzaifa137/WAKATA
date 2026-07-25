<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UceUaceSubjectsSeeder extends Seeder
{
    // php artisan db:seed --class=UceUaceSubjectsSeeder
    public function run(): void
    {
        $uceCode  = config('constants.options.UCEPapers', 24);
        $uaceCode = config('constants.options.UACEPapers', 25);

        // Uganda Certificate of Education (O-LEVEL) subjects.
        // 'Compulsory' subjects are auto-registered for every UCE student.
        // 'Optional' subjects are only registered when picked during import / manual registration.
        $uce = [
            ['code' => 'ENG', 'name' => 'English Language', 'status' => 'Compulsory'],
            ['code' => 'MATH', 'name' => 'Mathematics', 'status' => 'Compulsory'],
            ['code' => 'BIO', 'name' => 'Biology', 'status' => 'Optional'],
            ['code' => 'PHY', 'name' => 'Physics', 'status' => 'Optional'],
            ['code' => 'CHEM', 'name' => 'Chemistry', 'status' => 'Optional'],
            ['code' => 'HIST', 'name' => 'History', 'status' => 'Optional'],
            ['code' => 'GEOG', 'name' => 'Geography', 'status' => 'Optional'],
            ['code' => 'CRE', 'name' => 'Christian Religious Education', 'status' => 'Optional'],
            ['code' => 'IRE', 'name' => 'Islamic Religious Education', 'status' => 'Optional'],
            ['code' => 'AGRIC', 'name' => 'Agriculture', 'status' => 'Optional'],
            ['code' => 'COMM', 'name' => 'Commerce', 'status' => 'Optional'],
            ['code' => 'COMP', 'name' => 'Computer Studies', 'status' => 'Optional'],
            ['code' => 'FART', 'name' => 'Fine Art', 'status' => 'Optional'],
            ['code' => 'LIT', 'name' => 'Literature in English', 'status' => 'Optional'],
            ['code' => 'KISW', 'name' => 'Kiswahili', 'status' => 'Optional'],
            ['code' => 'LUG', 'name' => 'Luganda', 'status' => 'Optional'],
            ['code' => 'ARAB', 'name' => 'Arabic', 'status' => 'Optional'],
            ['code' => 'PE', 'name' => 'Physical Education', 'status' => 'Optional'],
            ['code' => 'ENT', 'name' => 'Entrepreneurship Education', 'status' => 'Optional'],
            ['code' => 'TD', 'name' => 'Technical Drawing', 'status' => 'Optional'],
            ['code' => 'HE', 'name' => 'Home Economics', 'status' => 'Optional'],
        ];

        // Uganda Advanced Certificate of Education (A-LEVEL) subjects.
        // General Paper is compulsory for every UACE student. The rest are
        // 'Optional', and split into two pools via `role`:
        //   - Principal: the subjects combinations are built from (3 per
        //     combination, e.g. PCM = Physics + Chemistry + Mathematics).
        //   - Subsidiary: a separate pool (Sub Math, Sub ICT) — at most one
        //     is attached to a combination, alongside its 3 principals.
        // `role` is only meaningful for Optional UACE subjects; it's left
        // null for Compulsory subjects and for all UCE subjects.
        $uace = [
            ['code' => 'GP', 'name' => 'General Paper', 'status' => 'Compulsory', 'role' => null],
            ['code' => 'PHY', 'name' => 'Physics', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'CHEM', 'name' => 'Chemistry', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'BIO', 'name' => 'Biology', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'MATH', 'name' => 'Mathematics', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'ECON', 'name' => 'Economics', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'GEOG', 'name' => 'Geography', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'HIST', 'name' => 'History', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'DIV', 'name' => 'Divinity (CRE)', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'IRE', 'name' => 'Islamic Religious Education', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'LIT', 'name' => 'Literature in English', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'KISW', 'name' => 'Kiswahili', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'FART', 'name' => 'Fine Art', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'AGRIC', 'name' => 'Agriculture', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'CS', 'name' => 'Computer Science', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'ENT', 'name' => 'Entrepreneurship', 'status' => 'Optional', 'role' => 'Principal'],
            ['code' => 'SMATH', 'name' => 'Subsidiary Mathematics', 'status' => 'Optional', 'role' => 'Subsidiary'],
            ['code' => 'SICT', 'name' => 'Subsidiary ICT', 'status' => 'Optional', 'role' => 'Subsidiary'],
        ];

        $now = now();

        // updateOrInsert (not delete+insert) so md_id stays stable across
        // re-runs — important once real marks/registrations start
        // referencing these subject ids as foreign keys.
        foreach ($uce as $s) {
            $this->upsertSubject($uceCode, $s, $now);
        }
        foreach ($uace as $s) {
            $this->upsertSubject($uaceCode, $s, $now);
        }
    }

    private function upsertSubject(int $masterCodeId, array $s, $now): void
    {
        DB::table('master_datas')->updateOrInsert(
            ['md_master_code_id' => $masterCodeId, 'md_code' => $s['code']],
            [
                'md_name' => $s['name'],
                'md_description' => $s['status'] . ' subject',
                'md_date_added' => $now->toDateString(),
                'md_added_by' => 'system',
                'md_misc1' => $s['status'], // 'Compulsory' or 'Optional'
                'md_misc4' => $s['role'] ?? null, // 'Principal' / 'Subsidiary' — UACE optional subjects only
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
}