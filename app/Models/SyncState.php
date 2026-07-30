<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncState extends Model
{
    protected $table = 'sync_state';

    protected $fillable = [
        'model_type',
        'last_pulled_at',
        'last_pushed_at',
    ];

    protected $casts = [
        'last_pulled_at' => 'datetime',
        'last_pushed_at' => 'datetime',
    ];

    public static function lastPulledAt(string $modelType): ?string
    {
        $row = static::where('model_type', $modelType)->first();

        return $row?->last_pulled_at?->toIso8601String();
    }

    public static function markPulled(string $modelType, string $serverTime): void
    {
        static::updateOrCreate(
            ['model_type' => $modelType],
            ['last_pulled_at' => $serverTime]
        );
    }

    public static function markPushed(string $modelType): void
    {
        static::updateOrCreate(
            ['model_type' => $modelType],
            ['last_pushed_at' => now()]
        );
    }
}
