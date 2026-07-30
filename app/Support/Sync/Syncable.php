<?php

namespace App\Support\Sync;

use App\Models\SyncOutbox;

/**
 * Add `use Syncable;` to any Eloquent model that should be tracked for
 * offline sync. Two things a model can override:
 *
 *   - syncKey(): array     the natural/business key that identifies this
 *                           row centrally (NOT the auto-increment id,
 *                           since two offline installs can both create
 *                           row #501). Defaults to ['id' => $this->id].
 *
 *   - syncPayload(): array the attributes that should travel in the sync
 *                           payload. Defaults to all fillable attributes.
 */
trait Syncable
{
    public static function bootSyncable(): void
    {
        static::creating(function ($model) {
            // Any syncable model that has a 'uuid' fillable attribute gets
            // one generated automatically if it doesn't already have one.
            // This is what makes rows CREATED on an offline install safe
            // to merge centrally without id collisions.
            if (in_array('uuid', $model->getFillable(), true) && empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::saved(function ($model) {
            $model->queueSyncChange($model->wasRecentlyCreated ? 'created' : 'updated');
        });

        static::deleted(function ($model) {
            $model->queueSyncChange('deleted');
        });
    }

    public function syncKey(): array
    {
        return ['id' => $this->getKey()];
    }

    public function syncPayload(): array
    {
        return $this->only($this->getFillable());
    }

    /**
     * Runs on the RECEIVING side, right before the payload is persisted
     * via updateOrCreate() — whether that's the central server applying
     * a school's push, or a school applying a pulled row. Override this
     * to do anything beyond a plain column copy: writing an uploaded
     * file to disk, resolving a foreign key that was sent as a uuid into
     * this install's local auto-increment id, etc. Must strip out any
     * transport-only keys (like a raw file blob) before returning.
     */
    public static function syncMaterializePayload(array $payload): array
    {
        return $payload;
    }

    protected function queueSyncChange(string $operation): void
    {
        // Changes applied BECAUSE of a sync pull/push must never be
        // re-queued — that would create an endless sync loop.
        if (SyncContext::isApplyingRemoteChange()) {
            return;
        }

        // Only "school" installs generate outgoing changes. The central
        // server records data too, of course, but it doesn't need to
        // push anywhere in this star topology.
        if (config('sync.role') !== 'school') {
            return;
        }

        SyncOutbox::recordChange(
            static::class,
            $this->syncKey(),
            $operation,
            $operation === 'deleted' ? [] : $this->syncPayload()
        );
    }
}
