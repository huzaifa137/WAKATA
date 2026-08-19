<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Ported from the standalone "wakata-scanner" test project.
 *
 * Reads a photo or PDF of a printed/handwritten score sheet and returns the
 * rows it can find as structured JSON. It never writes to the database
 * itself — the "enter marks" screen (results.blade.php) takes the JSON this
 * returns, fuzzy-matches each row's name against the students already
 * loaded for the selected school/subject, and lets the user drop the
 * matched scores straight into the existing marks form before saving
 * through the normal /iteb/save-marks flow.
 *
 * Pipeline, in order:
 *   1. PDF with a real text layer  -> smalot/pdfparser (fast, exact)
 *   2. PDF that's actually a scanned image (e.g. CamScanner export)
 *      -> rasterized page-by-page with poppler's pdftoppm, then run
 *         through the image pipeline below
 *   3. Image -> ImageMagick preprocessing -> Tesseract OCR (free, local)
 *   4. If Tesseract reads too few rows (typical for handwriting), the
 *      image is automatically re-sent to Google's Gemini vision API as a
 *      fallback -- only when GEMINI_API_KEY is configured in .env
 *
 * Requires on the server (same as wakata-scanner):
 *   sudo apt-get install -y tesseract-ocr tesseract-ocr-eng poppler-utils imagemagick
 *   composer require smalot/pdfparser
 */
class ScoreScanController extends Controller
{
    // -----------------------------------------------------------------------
    // SCAN — receive file, run OCR, return structured JSON
    // -----------------------------------------------------------------------
    public function scan(Request $request)
    {
        // DEBUG: raise the PHP script ceiling for this request only, so a
        // slow stage produces a log line instead of a silent 60s kill.
        set_time_limit(180);

        $t0 = microtime(true);
        $mark = function (string $label) use ($t0) {
            Log::info(sprintf('[SCAN TIMING] %s at +%.2fs', $label, microtime(true) - $t0));
        };
        $mark('request received');

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
            'scan_type' => 'required|in:pdf,image',
        ]);
        $mark('validation done');

        Storage::disk('local')->makeDirectory('score-scans/temp');

        $file = $request->file('file');
        $scanType = $request->input('scan_type');
        $extension = strtolower($file->getClientOriginalExtension());

        $storedPath = $file->store('score-scans/temp', 'local');
        $fullPath = Storage::disk('local')->path($storedPath);
        $mark('file stored to disk');

        try {
            if ($extension === 'pdf') {
                $extracted = $this->extractFromPdf($fullPath, $mark);
            } else {
                $extracted = $this->extractFromImage($fullPath, $mark);
            }
            $mark('extraction finished');

            Storage::disk('local')->delete($storedPath);

            return response()->json([
                'success' => true,
                'data' => $extracted,
                'source_file' => $file->getClientOriginalName(),
                'scan_type' => $scanType,
            ]);
        } catch (\Throwable $e) {
            $mark('extraction threw: ' . $e->getMessage());
            Log::error('Score sheet scan failed: ' . $e->getMessage());
            Storage::disk('local')->delete($storedPath);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // System check — GET /iteb/scan-score-sheet/check to verify server deps
    public function check()
    {
        $checks = [];

        $tesseractBin = $this->tesseractBin();
        exec(sprintf('%s --version 2>&1', escapeshellarg($tesseractBin)), $tOut, $tCode);
        $checks['tesseract'] = [
            'ok' => $tCode === 0,
            'version' => $tOut[0] ?? 'not found',
            'fix' => $this->isWindows()
                ? 'Install Tesseract (UB-Mannheim build), add its folder to PATH, then restart your '
                    . 'terminal/IDE. If it still fails here, set TESSERACT_PATH in .env to the full '
                    . 'path of tesseract.exe.'
                : 'sudo apt-get install -y tesseract-ocr tesseract-ocr-eng',
        ];

        $pdftoppmBin = $this->pdftoppmBin();
        $pOk = $this->binaryExists($pdftoppmBin);
        $checks['pdftoppm'] = [
            'ok' => $pOk,
            'version' => $pOk ? 'found' : 'not found',
            'fix' => $this->isWindows()
                ? 'Install Poppler for Windows, add its bin folder to PATH, then restart your '
                    . 'terminal/IDE. If it still fails here, set PDFTOPPM_PATH in .env to the full '
                    . 'path of pdftoppm.exe.'
                : 'sudo apt-get install -y poppler-utils',
        ];

        // On Windows, `convert` can silently resolve to the OS's own
        // C:\Windows\System32\convert.exe (a FAT->NTFS disk tool) instead of
        // ImageMagick — that mismatch is what produces "Invalid drive
        // specification". imageMagickBin() prefers `magick` to avoid that,
        // but we still confirm the output actually says "ImageMagick" so a
        // wrong binary is reported as missing rather than a false "ok".
        $imBin = $this->imageMagickBin();
        exec(sprintf('%s --version 2>&1', escapeshellarg($imBin)), $iOut, $iCode);
        $imVersionLine = $iOut[0] ?? 'not found';
        $checks['imagemagick'] = [
            'ok' => $iCode === 0 && stripos(implode(' ', $iOut), 'imagemagick') !== false,
            'version' => $imVersionLine,
            'fix' => $this->isWindows()
                ? 'Install ImageMagick, tick "Add to PATH" and "Install legacy utilities" during setup, '
                    . 'then restart your terminal/IDE. If `convert --version` prints something other '
                    . 'than ImageMagick (Windows has its own convert.exe), set IMAGEMAGICK_PATH in .env '
                    . 'to the full path of magick.exe instead.'
                : 'sudo apt-get install -y imagemagick',
        ];

               $geminiKeys = config('services.gemini.keys', []);
        $checks['gemini_api_key'] = [
            'ok' => !empty($geminiKeys),
            'version' => !empty($geminiKeys) ? count($geminiKeys) . ' key(s) configured' : 'not set',
            'fix' => 'Add GEMINI_API_KEYS=key1,key2,... to your .env file',
        ];

        $allOk = collect($checks)->every(fn($c) => $c['ok']);

        return response()->json([
            'status' => $allOk ? 'ALL OK — ready to scan!' : 'Some dependencies missing',
            'checks' => $checks,
        ], $allOk ? 200 : 500);
    }

    // -----------------------------------------------------------------------
    // CROSS-PLATFORM BINARY RESOLUTION
    // -----------------------------------------------------------------------
    // Windows has no `which` command, and its own built-in convert.exe (a
    // FAT->NTFS disk-conversion tool, unrelated to ImageMagick) normally
    // sits on PATH ahead of ImageMagick's copy — which is exactly why
    // `convert --version` returned "Invalid drive specification" for this
    // install. These helpers pick the right binary per-OS and let the
    // optional TESSERACT_PATH / PDFTOPPM_PATH / IMAGEMAGICK_PATH .env
    // overrides (config/services.php -> 'ocr') win when PATH detection
    // still isn't reliable (common when the server process doesn't inherit
    // the same PATH as your terminal).

    private function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /**
     * True if $binary runs from PATH. Uses `where` on Windows and
     * `command -v` elsewhere (more portable than `which`, which isn't
     * guaranteed to exist on minimal Linux images).
     */
    private function binaryExists(string $binary): bool
    {
        $probe = $this->isWindows() ? 'where' : 'command -v';
        exec(sprintf('%s %s 2>&1', $probe, escapeshellarg($binary)), $out, $code);
        return $code === 0;
    }

    /** Tesseract executable — override with TESSERACT_PATH in .env. */
    private function tesseractBin(): string
    {
        return config('services.ocr.tesseract_path') ?: 'tesseract';
    }

    /** Poppler's pdftoppm — override with PDFTOPPM_PATH in .env. */
    private function pdftoppmBin(): string
    {
        return config('services.ocr.pdftoppm_path') ?: 'pdftoppm';
    }

    /**
     * ImageMagick's binary. Prefers `magick` (the ImageMagick v7 command)
     * over `convert` whenever it's available, since `convert` is the name
     * Windows' own disk-conversion tool also uses. Override with
     * IMAGEMAGICK_PATH in .env to force a specific executable.
     */
    private function imageMagickBin(): string
    {
        if ($override = config('services.ocr.imagemagick_path')) {
            return $override;
        }

        return $this->binaryExists('magick') ? 'magick' : 'convert';
    }

    // ═══════════════════════════════════════════════════════════════════════
    // OCR PIPELINE
    // ═══════════════════════════════════════════════════════════════════════

    private function extractFromPdf(string $pdfPath, ?\Closure $mark = null): array
    {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($pdfPath);
        $rawText = $pdf->getText();

        if ($this->hasSubstantiveText($rawText)) {
            return $this->parseOcrText($rawText);
        }

        // No real text layer — this PDF is a photo/scan wrapped in a PDF
        // container. Rasterize each page and run it through the same
        // Tesseract -> AI vision fallback pipeline used for photo uploads.
        $pages = $this->rasterizePdfPages($pdfPath);

        if (empty($pages)) {
            throw new \RuntimeException(
                'No readable text could be found in this PDF, and it could not be converted to an '
                . 'image for OCR either (the server is missing the pdftoppm/poppler-utils tool). '
                . 'Please re-save it as a JPG/PNG and upload using "Hardcopy Photo / Scan" mode instead.'
            );
        }

        $combined = ['sheet_meta' => $this->extractMeta([]), 'entries' => []];
        $usedAiVision = false;

        foreach ($pages as $pagePath) {
            $pageResult = $this->extractFromImage($pagePath, $mark ?? null);
            @unlink($pagePath);

            if (!empty($pageResult['notice'])) {
                $usedAiVision = true;
            }
            if (empty($combined['sheet_meta']['school_name']) && !empty($pageResult['sheet_meta']['school_name'])) {
                $combined['sheet_meta'] = $pageResult['sheet_meta'];
            }
            foreach ($pageResult['entries'] as $entry) {
                $combined['entries'][] = $entry;
            }
        }

        if ($usedAiVision) {
            $combined['notice'] = 'This PDF had no embedded text layer, so the rows below were read '
                . 'using AI vision instead of standard OCR. Please double‑check them carefully against '
                . 'the original document.';
        } elseif (empty($combined['entries'])) {
            $combined['notice'] = 'This PDF has no embedded text — it looks like a photo or scan saved '
                . 'as a PDF rather than a typed document, and automatic OCR/AI vision could not '
                . 'confidently read any rows from it either. Please add the candidate rows manually below.';
        }

        return $combined;
    }

    private function hasSubstantiveText(string $text): bool
    {
        $clean = trim(preg_replace('/\bCamScanner\b/i', '', $text));
        return strlen($clean) >= 30 && preg_match('/\d/', $clean) === 1;
    }

    private function rasterizePdfPages(string $pdfPath): array
    {
        $pdftoppmBin = $this->pdftoppmBin();
        if (!$this->binaryExists($pdftoppmBin)) {
            return [];
        }

        $prefix = sys_get_temp_dir() . '/kamssa_pdfimg_' . uniqid();
        exec(sprintf(
            '%s -r 300 -png %s %s 2>&1',
            escapeshellarg($pdftoppmBin),
            escapeshellarg($pdfPath),
            escapeshellarg($prefix)
        ), $out, $code);

        $pages = glob($prefix . '*.png');
        sort($pages);
        return $pages;
    }

    /**
     * Image upload -> preprocess -> Tesseract (free, fast, local).
     * If that finds too few rows — the telltale sign of handwriting, which
     * Tesseract cannot read — automatically fall back to Gemini's vision API
     * (see GEMINI_API_KEY in .env) for a proper read.
     */
    private function extractFromImage(string $imgPath, ?\Closure $mark = null): array
    {
        $mark ??= function (string $label) {
            Log::info('[SCAN TIMING] ' . $label);
        };

        $tesseractResult = ['sheet_meta' => $this->extractMeta([]), 'entries' => []];

        try {
            $mark('ensureTesseract() start');
            $this->ensureTesseract();
            $mark('ensureTesseract() done');

            $processed = $this->preprocessImage($imgPath);
            $mark('preprocessImage() (ImageMagick) done');

            $text = $this->runTesseract($processed);
            $mark('runTesseract() done');

            if ($processed !== $imgPath) {
                @unlink($processed);
            }
            $tesseractResult = $this->parseOcrText($text);
        } catch (\Throwable $e) {
            $mark('Tesseract stage threw: ' . $e->getMessage());
            Log::warning('Tesseract OCR unavailable/failed, relying on AI vision fallback: ' . $e->getMessage());
        }

        if (count($tesseractResult['entries']) >= 3) {
            $mark('Tesseract found >=3 rows, skipping Gemini entirely');
            return $tesseractResult;
        }

        $mark('calling extractWithGeminiVision() now');
        $gemini = $this->extractWithGeminiVision($imgPath);
        $mark('extractWithGeminiVision() returned');

        if ($gemini !== null && count($gemini['entries']) > count($tesseractResult['entries'])) {
            $gemini['notice'] = count($tesseractResult['entries']) === 0
                ? 'Standard OCR could not confidently read this image (common with handwriting), so it '
                . 'was automatically re-processed using AI vision instead. Please double‑check the rows '
                . 'below carefully.'
                : 'Standard OCR only found a few rows on this image, so it was automatically '
                . 're-processed using AI vision for a more complete read. Please double‑check the rows '
                . 'below carefully.';
            return $gemini;
        }

        if (empty($tesseractResult['entries']) && empty($gemini)) {
            $tesseractResult['notice'] = 'We couldn\'t automatically read any rows from this file. This '
                . 'usually means the image is too blurry, dark, or at too sharp an angle. Please try '
                . 'retaking the photo in good lighting with the sheet flat, or add the rows manually below.';
        }

        return $tesseractResult;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GEMINI VISION FALLBACK (for handwritten / low-quality scans)
    // ═══════════════════════════════════════════════════════════════════════
        private function extractWithGeminiVision(string $imagePath): ?array
    {
        if (empty(config('services.gemini.keys'))) {
            return null;
        }

        $mimeType = match (strtolower(pathinfo($imagePath, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        $imageData = base64_encode(file_get_contents($imagePath));

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'school_name' => ['type' => 'STRING', 'nullable' => true],
                'zone' => ['type' => 'STRING', 'nullable' => true],
                'ref_no' => ['type' => 'STRING', 'nullable' => true],
                'subject' => ['type' => 'STRING', 'nullable' => true],
                'exam_year' => ['type' => 'STRING', 'nullable' => true],
                'entries' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'serial_no' => ['type' => 'INTEGER'],
                            'candidate_name' => ['type' => 'STRING'],
                            'p1' => ['type' => 'NUMBER', 'nullable' => true],
                            'p2' => ['type' => 'NUMBER', 'nullable' => true],
                            'p3' => ['type' => 'NUMBER', 'nullable' => true],
                            'p4' => ['type' => 'NUMBER', 'nullable' => true],
                            'average' => ['type' => 'NUMBER', 'nullable' => true],
                            'grade' => ['type' => 'STRING', 'nullable' => true],
                        ],
                        'required' => ['serial_no', 'candidate_name'],
                    ],
                ],
            ],
            'required' => ['entries'],
        ];

        $prompt = 'You are reading a Ugandan school exam score sheet (printed or handwritten). Read every '
            . 'row of the candidate table carefully, including handwritten entries. Rules: "S/N" or "S/H" '
            . 'is the serial number column. Read each candidate\'s full name exactly as written, in '
            . 'uppercase. Each P1, P2, P3, P4 column holds a numeric score, only if that column actually '
            . 'exists on the sheet — if a column such as AVERAGE or GRADE does not appear on the sheet at '
            . 'all, leave it null for every row rather than guessing a value. If a digit is ambiguous, use '
            . 'the most visually likely digit rather than skipping the row. Do not skip any row in the '
            . 'table, even if the handwriting is messy. Also extract header info if present: school name, '
            . 'zone, REF number, subject, exam year. Return only the structured data described by the '
            . 'schema — do not invent rows or scores that are not on the sheet.';

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inline_data' => ['mime_type' => $mimeType, 'data' => $imageData]],
                ],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
                'temperature' => 0,
            ],
        ];

        $geminiT0 = microtime(true);
        Log::info(sprintf(
            '[GEMINI TIMING] request starting, image size = %.1f KB',
            strlen($imageData) / 1024
        ));

        try {
            $json = app(\App\Services\GeminiKeyRotator::class)->request('gemini-3.6-flash', $payload);

            Log::info(sprintf(
                '[GEMINI TIMING] response received after %.2fs',
                microtime(true) - $geminiT0
            ));

            $text = data_get($json, 'candidates.0.content.parts.0.text');
            if (!$text) {
                return null;
            }

            $decoded = json_decode($text, true);
            if (!is_array($decoded) || !isset($decoded['entries'])) {
                return null;
            }

            return [
                'sheet_meta' => [
                    'school_name' => $decoded['school_name'] ?? null,
                    'zone' => $decoded['zone'] ?? null,
                    'ref_no' => $decoded['ref_no'] ?? null,
                    'subject' => $decoded['subject'] ?? null,
                    'exam_year' => $decoded['exam_year'] ?? null,
                ],
                'entries' => array_map(function ($e) {
                    return [
                        'serial_no' => (int) ($e['serial_no'] ?? 0),
                        'candidate_name' => strtoupper(trim($e['candidate_name'] ?? '')),
                        'p1' => isset($e['p1']) ? (float) $e['p1'] : null,
                        'p2' => isset($e['p2']) ? (float) $e['p2'] : null,
                        'p3' => isset($e['p3']) ? (float) $e['p3'] : null,
                        'p4' => isset($e['p4']) ? (float) $e['p4'] : null,
                        'average' => isset($e['average']) ? (float) $e['average'] : null,
                        'grade' => $e['grade'] ?? null,
                    ];
                }, $decoded['entries']),
            ];
        } catch (\App\Exceptions\GeminiAllKeysFailedException $e) {
            // Every key in the rotation failed — log the real reason, but
            // don't blow up the request: extractFromImage() will just fall
            // back to whatever Tesseract already found, same as before.
            Log::error(sprintf(
                '[GEMINI TIMING] all keys exhausted after %.2fs: %s',
                microtime(true) - $geminiT0,
                $e->getMessage()
            ));
            return null;
        } catch (\Throwable $e) {
            Log::warning(sprintf(
                '[GEMINI TIMING] call threw after %.2fs: %s',
                microtime(true) - $geminiT0,
                $e->getMessage()
            ));
            return null;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════
    // IMAGE PREPROCESSING (ImageMagick)
    // ═══════════════════════════════════════════════════════════════════════
    private function preprocessImage(string $srcPath): string
    {
        $outPath = sys_get_temp_dir() . '/kamssa_pp_' . uniqid() . '.png';
        $imBin = $this->imageMagickBin();

        $cmd = sprintf(
            '%s %s '
            . '-colorspace Gray '
            . '-normalize '
            . '-resize 200%% '
            . '-sharpen 0x1.5 '
            . '-level 15%%,85%% '
            . '-deskew 40%% '
            . '%s 2>&1',
            escapeshellarg($imBin),
            escapeshellarg($srcPath),
            escapeshellarg($outPath)
        );

        exec($cmd, $output, $code);

        if ($code !== 0 || !file_exists($outPath)) {
            Log::warning('ImageMagick preprocessing failed, using original. Output: ' . implode(' ', $output));
            return $srcPath;
        }

        return $outPath;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TESSERACT OCR
    // ═══════════════════════════════════════════════════════════════════════
    private function runTesseract(string $imagePath): string
    {
        $outBase = sys_get_temp_dir() . '/kamssa_ocr_' . uniqid();
        $tesseractBin = $this->tesseractBin();

        $cmd = sprintf(
            '%s %s %s --oem 1 --psm 4 -l eng 2>&1',
            escapeshellarg($tesseractBin),
            escapeshellarg($imagePath),
            escapeshellarg($outBase)
        );

        exec($cmd, $output, $code);

        $txtFile = $outBase . '.txt';
        if (!file_exists($txtFile)) {
            throw new \RuntimeException(
                'Tesseract OCR failed (exit ' . $code . '). ' . implode(' ', $output)
            );
        }

        $text = file_get_contents($txtFile);
        @unlink($txtFile);
        return $text;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TEXT PARSER — converts raw OCR text -> structured array
    // ═══════════════════════════════════════════════════════════════════════
    private function parseOcrText(string $rawText): array
    {
        $lines = preg_split('/\r?\n/', $rawText);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines, fn($l) => $l !== '');
        $lines = array_values($lines);

        return [
            'sheet_meta' => $this->extractMeta($lines),
            'entries' => $this->extractEntries($lines),
        ];
    }

    private function extractMeta(array $lines): array
    {
        $meta = ['school_name' => null, 'zone' => null, 'ref_no' => null, 'subject' => null, 'exam_year' => null];
        $fullText = implode(' ', $lines);

        if (preg_match('/NAME\s+OF\s+SCHOOL\s*:\s*(.+?)\s+ZONE\s*:/i', $fullText, $m))
            $meta['school_name'] = trim($m[1]);

        if (preg_match('/ZONE\s*:\s*([A-Za-z0-9\s\-]+?)\s+REF/i', $fullText, $m))
            $meta['zone'] = trim($m[1]);

        if (preg_match('/REF\s*No\.?\s*(\d+)/i', $fullText, $m))
            $meta['ref_no'] = trim($m[1]);

        if (preg_match('/SUBJECT\s*[:\-_]*\s*([A-Za-z][A-Za-z\s]{2,40})/i', $fullText, $m)) {
            $subj = trim($m[1]);
            if (!preg_match('/^(S\/N|NAME|CANDIDATE|P1|GRADE|_)/i', $subj))
                $meta['subject'] = $subj;
        }

        if (preg_match('/\b(20\d{2})\b/', implode(' ', array_slice($lines, 0, 8)), $m))
            $meta['exam_year'] = $m[1];

        return $meta;
    }

    private function extractEntries(array $lines): array
    {
        $entries = [];

        foreach ($lines as $line) {
            if (preg_match('/^[\|\-\s_=]+$/', $line))
                continue;

            $entry = $this->parseCandidateLine($line);
            if ($entry)
                $entries[] = $entry;
        }

        $seen = [];
        $unique = [];
        foreach ($entries as $e) {
            if (!isset($seen[$e['serial_no']])) {
                $seen[$e['serial_no']] = true;
                $unique[] = $e;
            }
        }
        usort($unique, fn($a, $b) => $a['serial_no'] <=> $b['serial_no']);

        return $unique;
    }

    private function parseCandidateLine(string $line): ?array
    {
        if (
            preg_match(
                '/^(\d{1,3})[.\)]\s+([A-Z][A-Z\s\'\-\.]{3,60})\s+([\d][\d\s\.]{0,80})$/',
                $line,
                $m
            )
        ) {
            $entry = $this->buildEntry((int) $m[1], trim($m[2]), $m[3]);

            if ($entry['p1'] !== null) {
                return $entry;
            }
        }

        if (
            preg_match(
                '/^(\d{1,3})[.\)]\s+(.{4,50}?)\s+((?:\d+\.?\d*\s*){1,})$/',
                $line,
                $m
            )
        ) {
            $name = strtoupper(
                trim(
                    preg_replace('/[^A-Za-z\s\'\-\.]/', '', $m[2])
                )
            );

            if (strlen($name) >= 4) {
                return $this->buildEntry((int) $m[1], $name, $m[3]);
            }
        }

        return null;
    }

    private function buildEntry(int $serial, string $name, string $numString): array
    {
        $tokens = preg_split('/\s+/', trim($numString));
        $scores = [];
        $grade = null;

        foreach ($tokens as $token) {
            if (is_numeric($token))
                $scores[] = (float) $token;
            elseif (preg_match('/^[A-Fa-f][1-9]$/', $token))
                $grade = strtoupper($token);
        }

        if ($grade === null && count($scores) >= 6) {
            $grade = (string) (int) array_splice($scores, 5, 1)[0];
        }

        return [
            'serial_no' => $serial,
            'candidate_name' => $name,
            'p1' => $scores[0] ?? null,
            'p2' => $scores[1] ?? null,
            'p3' => $scores[2] ?? null,
            'p4' => $scores[3] ?? null,
            'average' => $scores[4] ?? null,
            'grade' => $grade,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GUARDS
    // ═══════════════════════════════════════════════════════════════════════
    private function ensureTesseract(): void
    {
        $bin = $this->tesseractBin();
        if (!$this->binaryExists($bin)) {
            throw new \RuntimeException(
                'Tesseract OCR is not installed or not in PATH. ' . (
                    $this->isWindows()
                        ? 'Install it (UB-Mannheim build), add its folder to PATH and restart your '
                            . 'terminal/IDE, or set TESSERACT_PATH in .env to the full path of tesseract.exe.'
                        : 'Run: sudo apt-get install -y tesseract-ocr tesseract-ocr-eng'
                )
            );
        }
    }
}