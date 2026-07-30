<?php

namespace App\Models;

use App\Support\Sync\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use HasFactory;
    use Syncable;

    protected $fillable = [
        'student_id',
        'subject_id',
        'mark',
        'year',
        'category',
        'school_number'
    ];

    /**
     * Marks are matched by (student_id, subject_id), never by the local
     * auto-increment id — this is already how ItebController's
     * updateOrCreate() calls work, and it's exactly what makes this
     * table safe to sync between many offline installs without id
     * collisions.
     */
    public function syncKey(): array
    {
        return [
            'student_id' => $this->student_id,
            'subject_id' => $this->subject_id,
        ];
    }
}