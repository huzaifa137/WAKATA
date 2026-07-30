<?php

namespace App\Console\Commands;

use App\Models\SyncState;
use App\Services\Sync\SyncClient;
use App\Support\Sync\SyncContext;
use Illuminate\Console\Command;

class SyncPull extends Command
{
    protected $signature = 'sync:pull';

    protected $description = 'Pull updates (from other schools / central corrections) into this local install';

    public function handle(SyncClient $client): int
    {
        if (config('sync.role') !== 'school') {
            $this->info('This install is not configured as a "school" (SYNC_ROLE != school). Nothing to pull.');
            return self::SUCCESS;
        }

        if (!$client->isConfigured()) {
            $this->error('SYNC_CENTRAL_URL / SYNC_TOKEN are not set in .env — cannot pull.');
            return self::FAILURE;
        }

        $models = array_keys(config('sync.models', []));

        if (empty($models)) {
            $this->info('No syncable models configured.');
            return self::SUCCESS;
        }

        $totalApplied = 0;

        foreach ($models as $modelType) {
            $since = SyncState::lastPulledAt($modelType);

            $response = $client->pull($modelType, $since);

            if ($response === null) {
                $this->warn("Central server unreachable while pulling {$modelType} — will retry next time.");
                continue;
            }

            $rows = $response['rows'] ?? [];

            SyncContext::withRemoteApply(function () use ($modelType, $rows, &$totalApplied) {
                foreach ($rows as $row) {
                    $materialized = $modelType::syncMaterializePayload($row['payload']);
                    $modelType::updateOrCreate($row['key'], $materialized);
                    $totalApplied++;
                }
            });

            SyncState::markPulled($modelType, $response['server_time']);

            $this->line(($this->modelLabel($modelType)) . ': pulled ' . count($rows) . ' change(s).');
        }

        $this->info("Done. {$totalApplied} row(s) updated locally.");

        return self::SUCCESS;
    }

    protected function modelLabel(string $modelType): string
    {
        return class_basename($modelType);
    }
}
