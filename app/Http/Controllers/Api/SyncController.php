<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncDevice;
use App\Services\Sync\SyncManager;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct(protected SyncManager $syncManager)
    {
    }

    /**
     * POST /api/sync/push
     *
     * Body:
     * {
     *   "items": [
     *     {
     *       "uuid": "...",
     *       "model_type": "App\\Models\\Mark",
     *       "model_key": {"student_id": "...", "subject_id": 5},
     *       "operation": "updated",
     *       "payload": {...},
     *       "edited_at": "2026-07-20T10:00:00+00:00"
     *     }
     *   ]
     * }
     */
    public function push(Request $request)
    {
        /** @var SyncDevice $device */
        $device = $request->attributes->get('sync_device');

        $validated = $request->validate([
            'items' => 'present|array',
            'items.*.uuid' => 'required|string',
            'items.*.model_type' => 'required|string',
            'items.*.model_key' => 'required|array',
            'items.*.operation' => 'required|in:created,updated,deleted',
            'items.*.payload' => 'nullable|array',
            'items.*.edited_at' => 'nullable|string',
        ]);

        $results = [];

        foreach ($validated['items'] as $item) {
            $result = $this->syncManager->applyIncoming(
                $item['model_type'],
                $item['model_key'],
                $item['operation'],
                $item['payload'] ?? [],
                $device->school_number,
                $device->device_name,
                $item['edited_at'] ?? null
            );

            $results[] = array_merge(['uuid' => $item['uuid']], $result);
        }

        return response()->json(['results' => $results]);
    }

    /**
     * GET /api/sync/pull?model_type=App\Models\Mark&since=2026-07-20T00:00:00Z
     */
    public function pull(Request $request)
    {
        /** @var SyncDevice $device */
        $device = $request->attributes->get('sync_device');

        $validated = $request->validate([
            'model_type' => 'required|string',
            'since' => 'nullable|string',
        ]);

        if (!$this->syncManager->isSyncable($validated['model_type'])) {
            return response()->json(['message' => 'Model is not syncable'], 422);
        }

        [$rows, $serverTime] = $this->syncManager->rowsForPull(
            $validated['model_type'],
            $validated['since'] ?? null,
            $device->school_number
        );

        return response()->json([
            'rows' => $rows,
            'server_time' => $serverTime,
        ]);
    }
}
