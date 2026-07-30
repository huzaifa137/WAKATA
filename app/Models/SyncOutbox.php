<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SyncOutbox extends Model
{
    protected $table = 'sync_outbox';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'model_type',
        'model_key',
        'operation',
        'payload',
        'school_number',
        'device_name',
        'attempts',
        'last_error',
        'created_at',
        'synced_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    /**
     * Canonical JSON encoding of a natural key, so the same key always
     * produces the same string regardless of the order its columns were
     * built in.
     */
    public static function canonicalKey(array $key): string
    {
        ksort($key);

        return json_encode($key);
    }

    /**
     * Queue a change. If there is already an un-synced queued change for
     * this exact model+key, update it in place instead of stacking up a
     * new row — a mark entrant correcting the same mark five times while
     * offline should only ever produce one outbox entry.
     */
    public static function recordChange(string $modelType, array $key, string $operation, array $payload): void
    {
        $canonicalKey = static::canonicalKey($key);

        $existing = static::where('model_type', $modelType)
            ->where('model_key', $canonicalKey)
            ->whereNull('synced_at')
            ->first();

        if ($existing) {
            // A create followed by further edits is still, on net, a
            // "created" row as far as the central server needs to know.
            // Anything followed by a delete is just a delete.
            $resolvedOperation = $operation === 'deleted'
                ? 'deleted'
                : ($existing->operation === 'created' ? 'created' : $operation);

            $existing->update([
                'operation' => $resolvedOperation,
                'payload' => json_encode($payload),
                'created_at' => now(),
            ]);

            return;
        }

        static::create([
            'uuid' => (string) Str::uuid(),
            'model_type' => $modelType,
            'model_key' => $canonicalKey,
            'operation' => $operation,
            'payload' => json_encode($payload),
            'school_number' => config('sync.school_number'),
            'device_name' => config('sync.device_name'),
            'attempts' => 0,
            'created_at' => now(),
        ]);
    }

    public function decodedKey(): array
    {
        return json_decode($this->model_key, true) ?? [];
    }

    public function decodedPayload(): array
    {
        return json_decode($this->payload, true) ?? [];
    }
}
