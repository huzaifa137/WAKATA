<?php

namespace App\Models;

use App\Support\Sync\Syncable;
use Illuminate\Database\Eloquent\Model;

class MarkPaper extends Model
{
    use Syncable;

    protected $table = 'mark_papers';

    protected $fillable = [
        'student_id',
        'subject_id',
        'paper_number',
        'raw_mark',
        'max_score',
        'mark',
        'year',
        'category',
        'school_number',
    ];

    public function syncKey(): array
    {
        return [
            'student_id' => $this->student_id,
            'subject_id' => $this->subject_id,
            'paper_number' => $this->paper_number,
        ];
    }
}