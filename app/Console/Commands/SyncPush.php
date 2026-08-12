<?php

namespace App\Console\Commands;

use App\Models\SyncOutbox;
use App\Models\SyncState;
use App\Services\Sync\SyncClient;
use Illuminate\Console\Command;

class SyncPush extends Command
{
    protected $signature = 'sync:push';

    protected $description = 'Push queued offline changes (marks, etc.) to the central WAKATA server';

    public function handle(SyncClient $client): int
    {
        if (config('sync.role') !== 'school') {
            $this->info('This install is not configured as a "school" (SYNC_ROLE != school). Nothing to push.');
            return self::SUCCESS;
        }

        if (!$client->isConfigured()) {
            $this->error('SYNC_CENTRAL_URL / SYNC_TOKEN are not set in .env — cannot push.');
            return self::FAILURE;
        }

        $chunkSize = (int) config('sync.push_chunk_size', 200);
        $pending = SyncOutbox::whereNull('synced_at')->orderBy('created_at')->count();

        if ($pending === 0) {
            $this->info('Nothing queued to push.');
            return self::SUCCESS;
        }

        $this->info("Pushing {$pending} queued change(s)...");
        $pushedTotal = 0;
        $conflictTotal = 0;

        SyncOutbox::whereNull('synced_at')
            ->orderBy('created_at')
            ->chunkById($chunkSize, function ($batch) use ($client, &$pushedTotal, &$conflictTotal) {
                $items = $batch->map(fn (SyncOutbox $row) => [
                    'uuid' => $row->uuid,
                    'model_type' => $row->model_type,
                    'model_key' => $row->decodedKey(),
                    'operation' => $row->operation,
                    'payload' => $row->decodedPayload(),
                    'edited_at' => $row->created_at?->toIso8601String(),
                ])->values()->all();

                $response = $client->push($items);

                if ($response === null) {
                    $this->warn('Central server unreachable — will retry next time. Anything already pushed stays queued.');
                    return false; // stop chunking, no internet right now
                }

                $resultsByUuid = collect($response['results'] ?? [])->keyBy('uuid');

                foreach ($batch as $row) {
                    $result = $resultsByUuid->get($row->uuid);

                    if (!$result) {
                        continue; // central didn't return a result for this item; leave queued, retry later
                    }

                    if ($result['status'] === 'applied') {
                        $row->update(['synced_at' => now(), 'last_error' => null]);
                        $pushedTotal++;
                    } elseif ($result['status'] === 'conflict') {
                        // Logged centrally for a human to resolve. Mark as
                        // synced locally so we stop resending it — the
                        // resolution now lives on the central conflicts
                        // screen, not in this school's outbox.
                        $row->update([
                            'synced_at' => now(),
                            'last_error' => 'Conflict: ' . ($result['message'] ?? 'flagged for review centrally'),
                        ]);
                        $conflictTotal++;
                    } else {
                        $row->increment('attempts');
                        $row->update(['last_error' => $result['message'] ?? $result['status']]);
                    }
                }
            });

        SyncState::markPushed('outbox');

        $this->info("Pushed {$pushedTotal} change(s).");
        if ($conflictTotal > 0) {
            $this->warn("{$conflictTotal} change(s) were flagged as conflicts and need review on the central server (/sync/conflicts).");
        }

        return self::SUCCESS;
    }
}
