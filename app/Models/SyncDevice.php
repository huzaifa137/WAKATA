<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncDevice extends Model
{
    protected $table = 'sync_devices';

    protected $fillable = [
        'school_number',
        'device_name',
        'token_hash',
        'role',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];
}
