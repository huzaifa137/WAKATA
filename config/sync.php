<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sync role
    |--------------------------------------------------------------------------
    | 'central'  -> the main WAKATA server. Accepts pushes, serves pulls.
    | 'school'   -> a remote/offline installation (XAMPP at a school or
    |               town field office). Queues local changes and pushes
    |               them to the central server when internet is available.
    */
    'role' => env('SYNC_ROLE', 'central'),

    /*
    |--------------------------------------------------------------------------
    | Central connection (only used when role = school)
    |--------------------------------------------------------------------------
    */
    'central_url' => env('SYNC_CENTRAL_URL'),
    'token' => env('SYNC_TOKEN'),
    'school_number' => env('SYNC_SCHOOL_NUMBER'),
    'device_name' => env('SYNC_DEVICE_NAME', gethostname() ?: 'unknown-device'),

    /*
    |--------------------------------------------------------------------------
    | Syncable models
    |--------------------------------------------------------------------------
    | Every model listed here must use the App\Support\Sync\Syncable trait.
    | 'school_scoped' => true means each school only pushes/pulls its own
    | rows (matched via the school_column below). false means the model is
    | shared reference data (e.g. Subjects) — schools only ever pull it.
    */
    'models' => [
        \App\Models\Mark::class => [
            'school_scoped' => true,
            'school_column' => 'school_number',
            'school_resolution' => 'direct',
        ],
        \App\Models\MarkPaper::class => [
            'school_scoped' => true,
            'school_column' => 'school_number',
            'school_resolution' => 'direct',
        ],
        \App\Models\StudentRegistration::class => [
            'school_scoped' => true,
            'school_column' => 'school_id',
            'school_resolution' => 'house_id',
        ],
        \App\Models\SubmissionDocument::class => [
            'school_scoped' => true,
            'school_column' => 'school_id',
            'school_resolution' => 'house_id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Push batching
    |--------------------------------------------------------------------------
    */
    'push_chunk_size' => env('SYNC_PUSH_CHUNK_SIZE', 200),
    'http_timeout' => env('SYNC_HTTP_TIMEOUT', 15),
];
