<?php

namespace App\Services\Sync;

use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP wrapper a "school" install uses to reach the central server.
 * Every method returns null (rather than throwing) on any connection
 * failure, so callers can simply treat "no internet right now" as a
 * normal, expected outcome and try again later.
 */
class SyncClient
{
    protected function baseUrl(): ?string
    {
        return rtrim((string) config('sync.central_url'), '/') ?: null;
    }

    protected function request()
    {
        return Http::withToken((string) config('sync.token'))
            ->timeout((int) config('sync.http_timeout', 15))
            ->acceptJson();
    }

    /**
     * @return array|null  ['results' => [...]] on success, null if unreachable
     */
    public function push(array $items): ?array
    {
        $baseUrl = $this->baseUrl();
        if (!$baseUrl) {
            return null;
        }

        try {
            $response = $this->request()->post("{$baseUrl}/api/sync/push", ['items' => $items]);
        } catch (\Throwable $e) {
            return null;
        }

        return $response->successful() ? $response->json() : null;
    }

    /**
     * @return array|null  ['rows' => [...], 'server_time' => '...'] on success, null if unreachable
     */
    public function pull(string $modelType, ?string $since): ?array
    {
        $baseUrl = $this->baseUrl();
        if (!$baseUrl) {
            return null;
        }

        try {
            $response = $this->request()->get("{$baseUrl}/api/sync/pull", [
                'model_type' => $modelType,
                'since' => $since,
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        return $response->successful() ? $response->json() : null;
    }

    public function isConfigured(): bool
    {
        return (bool) $this->baseUrl() && (bool) config('sync.token');
    }
}
