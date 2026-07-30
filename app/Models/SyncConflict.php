<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncConflict extends Model
{
    protected $table = 'sync_conflicts';

    protected $fillable = [
        'model_type',
        'model_key',
        'incoming_payload',
        'current_payload',
        'school_number',
        'device_name',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function decodedKey(): array
    {
        return json_decode($this->model_key, true) ?? [];
    }

    public function decodedIncoming(): array
    {
        return json_decode($this->incoming_payload, true) ?? [];
    }

    public function decodedCurrent(): array
    {
        return json_decode($this->current_payload, true) ?? [];
    }
}
