<?php

namespace App\Http\Middleware;

use App\Models\SyncDevice;
use Closure;
use Illuminate\Http\Request;

class SyncTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Missing sync token'], 401);
        }

        $device = SyncDevice::where('token_hash', hash('sha256', $token))
            ->where('is_active', true)
            ->first();

        if (!$device) {
            return response()->json(['message' => 'Invalid or revoked sync token'], 403);
        }

        $device->update(['last_used_at' => now()]);

        // Make the authenticated device available to the controller.
        $request->attributes->set('sync_device', $device);

        return $next($request);
    }
}
