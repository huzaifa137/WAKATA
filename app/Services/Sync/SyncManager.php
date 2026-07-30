<?php

namespace App\Services\Sync;

use App\Models\SyncConflict;
use App\Models\SyncOutbox;
use App\Support\Sync\SyncContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncManager
{
    /**
     * Is $modelType allowed to sync at all, per config/sync.php?
     */
    public function isSyncable(string $modelType): bool
    {
        return array_key_exists($modelType, config('sync.models', []));
    }

    public function modelConfig(string $modelType): array
    {
        return config('sync.models.' . $modelType, []);
    }

    /**
     * Apply one incoming pushed change centrally.
     *
     * Conflict rule (kept deliberately simple and explainable): if a row
     * already exists centrally for this key AND it was updated AFTER the
     * offline edit was made, we do NOT blindly overwrite exam data — we
     * log a conflict for a human to review. Otherwise the incoming push
     * is trusted and applied.
     *
     * @return array{status:string, message?:string}
     */
    public function applyIncoming(
        string $modelType,
        array $key,
        string $operation,
        array $payload,
        ?string $schoolNumber,
        ?string $deviceName,
        ?string $offlineEditedAt
    ): array {
        if (!$this->isSyncable($modelType)) {
            return ['status' => 'rejected', 'message' => "{$modelType} is not a syncable model"];
        }

        $config = $this->modelConfig($modelType);
        $ownershipError = $this->checkOwnership($config, $payload, $schoolNumber);
        if ($ownershipError) {
            return ['status' => 'rejected', 'message' => $ownershipError];
        }

        return SyncContext::withRemoteApply(function () use ($modelType, $key, $operation, $payload, $schoolNumber, $deviceName, $offlineEditedAt) {
            /** @var \Illuminate\Database\Eloquent\Model $modelType */
            $query = $modelType::query();
            foreach ($key as $column => $value) {
                $query->where($column, $value);
            }
            $existing = $query->first();

            if ($operation === 'deleted') {
                if ($existing) {
                    $existing->delete();
                }
                return ['status' => 'applied'];
            }

            if ($existing && $offlineEditedAt) {
                $editedAt = Carbon::parse($offlineEditedAt);
                $centralUpdatedAt = $existing->updated_at ? Carbon::parse($existing->updated_at) : null;

                if ($centralUpdatedAt && $centralUpdatedAt->gt($editedAt)) {
                    $this->logConflict($modelType, $key, $payload, $existing->toArray(), $schoolNumber, $deviceName);
                    return ['status' => 'conflict', 'message' => 'Central record changed after this offline edit was made'];
                }
            }

            $materialized = $modelType::syncMaterializePayload($payload);

            DB::transaction(function () use ($modelType, $key, $materialized) {
                $modelType::updateOrCreate($key, $materialized);
            });

            return ['status' => 'applied'];
        });
    }

    /**
     * Refuse a push that claims to belong to a different school than the
     * one this device's token is registered for — e.g. a compromised or
     * misconfigured token trying to write another school's data.
     * Returns an error message, or null if ownership checks out.
     */
    protected function checkOwnership(array $config, array $payload, ?string $schoolNumber): ?string
    {
        if (empty($config['school_scoped']) || !$schoolNumber) {
            return null;
        }

        $column = $config['school_column'] ?? 'school_number';
        if (!array_key_exists($column, $payload)) {
            return null; // nothing to check against
        }

        $expected = $this->resolveSchoolScopeValue($config, $schoolNumber);
        if ($expected === null) {
            return null; // couldn't resolve (e.g. unknown school) — let it through rather than blocking on a config gap
        }

        if ((string) $payload[$column] !== (string) $expected) {
            return "Payload's {$column} does not match the school this device is registered for";
        }

        return null;
    }

    /**
     * Translate this device's school_number into whatever value the
     * model's school-scoping column actually stores. Mark/MarkPaper
     * store the school_number string directly; StudentRegistration and
     * SubmissionDocument store houses.ID (an integer), so those need one
     * lookup via houses.Number = school_number.
     */
    protected function resolveSchoolScopeValue(array $config, ?string $schoolNumber): mixed
    {
        if (!$schoolNumber) {
            return null;
        }

        if (($config['school_resolution'] ?? 'direct') === 'house_id') {
            return \App\Models\House::where('Number', $schoolNumber)->value('ID');
        }

        return $schoolNumber;
    }

    protected function logConflict(
        string $modelType,
        array $key,
        array $incomingPayload,
        array $currentPayload,
        ?string $schoolNumber,
        ?string $deviceName
    ): void {
        SyncConflict::create([
            'model_type' => $modelType,
            'model_key' => SyncOutbox::canonicalKey($key),
            'incoming_payload' => json_encode($incomingPayload),
            'current_payload' => json_encode($currentPayload),
            'school_number' => $schoolNumber,
            'device_name' => $deviceName,
            'status' => 'pending',
        ]);
    }

    /**
     * Rows changed for $modelType since $since, scoped to $schoolNumber
     * when the model is school-scoped. Returns [rows, serverTimeIso].
     */
    public function rowsForPull(string $modelType, ?string $since, ?string $schoolNumber): array
    {
        $serverTime = now()->toIso8601String();

        if (!$this->isSyncable($modelType)) {
            return [[], $serverTime];
        }

        $config = $this->modelConfig($modelType);
        $query = $modelType::query();

        if ($since) {
            $query->where('updated_at', '>', Carbon::parse($since));
        }

        if (!empty($config['school_scoped']) && $schoolNumber) {
            $query->where($config['school_column'] ?? 'school_number', $schoolNumber);
        }

        $rows = $query->get()->map(fn ($row) => [
            'key' => $row->syncKey(),
            'payload' => $row->syncPayload(),
            'updated_at' => optional($row->updated_at)->toIso8601String(),
        ])->values()->all();

        return [$rows, $serverTime];
    }
}
