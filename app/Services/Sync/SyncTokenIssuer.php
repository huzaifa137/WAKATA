<?php

namespace App\Services\Sync;

use App\Models\SyncDevice;
use Illuminate\Support\Str;

class SyncTokenIssuer
{
    /**
     * @return array{device: SyncDevice, plain_token: string}
     */
    public function issue(string $schoolNumber, string $deviceName, string $role = 'school'): array
    {
        $token = Str::random(48);

        $device = SyncDevice::create([
            'school_number' => $schoolNumber,
            'device_name' => $deviceName,
            'token_hash' => hash('sha256', $token),
            'role' => $role,
            'is_active' => true,
        ]);

        return ['device' => $device, 'plain_token' => $token];
    }
}
