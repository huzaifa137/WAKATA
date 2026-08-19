<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeminiKeyLog extends Model
{
    public $timestamps = false; // we set created_at manually via useCurrent()

    protected $fillable = [
        'key_label', 'status', 'http_status', 'error_reason', 'latency_ms', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}