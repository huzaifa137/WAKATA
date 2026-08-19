<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Optional overrides for the OCR command-line tools used by
    // ScoreScanController. Leave blank to auto-detect from PATH (works on
    // Linux/Mac out of the box). On Windows, if `tesseract --version` /
    // `pdftoppm -v` / `magick --version` work fine in Command Prompt but
    // the /iteb/scan-score-sheet/check endpoint still reports them missing,
    // it usually means the terminal PHP is launched from doesn't have the
    // same PATH — set the full .exe path here instead, e.g.:
    //   TESSERACT_PATH="C:\Program Files\Tesseract-OCR\tesseract.exe"
    //   PDFTOPPM_PATH="C:\poppler\Library\bin\pdftoppm.exe"
    //   IMAGEMAGICK_PATH="C:\Program Files\ImageMagick-7.1.1-Q16\magick.exe"
    'ocr' => [
        'tesseract_path' => env('TESSERACT_PATH'),
        'pdftoppm_path' => env('PDFTOPPM_PATH'),
        'imagemagick_path' => env('IMAGEMAGICK_PATH'),
    ],

    // Used by App\Http\Controllers\ScoreScanController for the AI-vision
    // fallback when Tesseract can't confidently read a handwritten or
    // low-quality score sheet photo. Optional — scanning still works with
    // Tesseract alone if this is left blank, just without the fallback.
    //
    // GEMINI_API_KEYS takes a comma-separated list so the rotator can fail
    // over between them: GEMINI_API_KEYS=key1,key2,key3,...
    'gemini' => [
        'keys' => array_filter(explode(',', env('GEMINI_API_KEYS', ''))),
    ],

];