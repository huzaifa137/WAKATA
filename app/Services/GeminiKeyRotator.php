<?php

namespace App\Services;

use App\Models\GeminiKeyLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiKeyRotator
{
    protected array $keys;

    public function __construct()
    {
        $this->keys = config('services.gemini.keys');
    }

    public function request(string $model, array $payload): array
    {
        $lastException = null;

        foreach ($this->orderedKeys() as $index => $key) {
            $label = $this->labelFor($key, $index);
            $cacheKey = "gemini_key_dead:" . substr(md5($key), 0, 8);

            if (Cache::has($cacheKey)) {
                $this->logAttempt($label, 'skipped', null, 'temporarily disabled', 0);
                continue;
            }

            $start = microtime(true);

            try {
                $response = Http::timeout(30)
                    ->withHeaders(['x-goog-api-key' => $key])
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", $payload);

                $latency = (int) ((microtime(true) - $start) * 1000);

                if ($response->successful()) {
                    $this->logAttempt($label, 'success', $response->status(), null, $latency);
                    return $response->json();
                }

                $status = $response->status();
                $body = $response->json();
                $reason = data_get($body, 'error.status') ?? data_get($body, 'error.details.0.reason');

                $this->logAttempt($label, 'failed', $status, $reason, $latency);

                Log::warning('Gemini key failed', ['key_label' => $label, 'status' => $status, 'reason' => $reason]);

                if (in_array($status, [401, 403]) || $reason === 'ACCESS_TOKEN_TYPE_UNSUPPORTED') {
                    Cache::put($cacheKey, true, now()->addHours(6));
                }

                $lastException = new \RuntimeException("Gemini key {$label} failed: {$status} {$reason}");
                continue;

            } catch (\Throwable $e) {
                $latency = (int) ((microtime(true) - $start) * 1000);
                $this->logAttempt($label, 'failed', null, substr($e->getMessage(), 0, 255), $latency);
                Log::error('Gemini request exception', ['key_label' => $label, 'error' => $e->getMessage()]);
                $lastException = $e;
                continue;
            }
        }

        throw new \App\Exceptions\GeminiAllKeysFailedException('All Gemini API keys failed.', previous: $lastException);
    }

    /** Aggregate stats per key for the monitoring dashboard. */
    public function stats(int $sinceHours = 24): array
    {
        return GeminiKeyLog::query()
            ->where('created_at', '>=', now()->subHours($sinceHours))
            ->selectRaw("key_label,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successes,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failures,
                SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) as skipped,
                AVG(CASE WHEN status = 'success' THEN latency_ms END) as avg_latency_ms,
                MAX(created_at) as last_seen")
            ->groupBy('key_label')
            ->get()
            ->map(function ($row) {
                $row->success_rate = $row->total > 0
                    ? round(($row->successes / $row->total) * 100, 1)
                    : null;
                $row->currently_disabled = Cache::has(
                    "gemini_key_dead:" . substr(md5($this->keyFromLabel($row->key_label)), 0, 8)
                );
                return $row;
            })
            ->sortByDesc('total')
            ->values()
            ->toArray();
    }

    protected function orderedKeys(): array
    {
        $i = Cache::get('gemini_key_pointer', 0);
        Cache::put('gemini_key_pointer', ($i + 1) % max(count($this->keys), 1), now()->addDay());
        return array_merge(array_slice($this->keys, $i), array_slice($this->keys, 0, $i));
    }

    protected function labelFor(string $key, int $index): string
    {
        $realIndex = array_search($key, $this->keys, true);
        return "key_" . ($realIndex + 1) . " (..." . substr($key, -4) . ")";
    }

    protected function keyFromLabel(string $label): string
    {
        preg_match('/key_(\d+)/', $label, $m);
        return $this->keys[($m[1] ?? 1) - 1] ?? '';
    }

    protected function logAttempt(string $label, string $status, ?int $httpStatus, ?string $reason, int $latencyMs): void
    {
        try {
            GeminiKeyLog::create([
                'key_label' => $label,
                'status' => $status,
                'http_status' => $httpStatus,
                'error_reason' => $reason,
                'latency_ms' => $latencyMs,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // never let logging break the actual OCR request
            Log::error('Failed to write gemini_key_logs row', ['error' => $e->getMessage()]);
        }
    }
}