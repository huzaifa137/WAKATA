<?php

namespace App\Models;

use App\Support\Sync\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentRegistration extends Model
{
    use HasFactory;
    use Syncable;

    protected $table = 'student_registrations';

    protected $fillable = [
        'uuid',
        'school_id',
        'category',
        'admission_year',
        'student_id',
        'student_name',
        'student_name_ar',
        'date_of_birth',
        'student_sex',
        'student_nationality',
        'birth_place',
        'birth_place_ar',
        'class',
        'section',
        'house',
        'district',
        'district_ar',
        'entry_date',
        'submitted_at',
        'is_locked',
        'status',
        'admin_remarks',
    ];

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'entry_date' => 'date:Y-m-d',
        'submitted_at' => 'datetime',
        'is_locked' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(House::class, 'school_id', 'ID');
    }

    public function submissionDocument()
    {
        return $this->belongsTo(SubmissionDocument::class, 'submission_document_id');
    }

    /**
     * Check if this registration has a submission document.
     */
    public function hasSubmissionDocument()
    {
        return $this->submission_document_id !== null;
    }

    /**
     * Created offline (a school registering a new student while
     * disconnected) -> matched by uuid, never the local auto-increment
     * id, since two offline installs could both mint id 501.
     */
    public function syncKey(): array
    {
        return ['uuid' => $this->uuid];
    }

    /**
     * submission_document_id is a LOCAL auto-increment id pointing at a
     * row that may not exist yet (or exist under a different id) on the
     * other side. We send the document's own sync uuid instead, and
     * resolve it back to a local id in syncMaterializePayload() below.
     */
    public function syncPayload(): array
    {
        $payload = $this->only($this->getFillable());
        $payload['submission_document_uuid'] = $this->submissionDocument?->uuid;

        return $payload;
    }

    public static function syncMaterializePayload(array $payload): array
    {
        if (array_key_exists('submission_document_uuid', $payload)) {
            $documentUuid = $payload['submission_document_uuid'];
            unset($payload['submission_document_uuid']);

            // If the linked document hasn't synced across yet, leave the
            // link empty for now — it fills in on a later sync once that
            // document itself has come across (outbox entries push in
            // the order they were made, so normally the document arrives
            // first anyway).
            $payload['submission_document_id'] = $documentUuid
                ? SubmissionDocument::where('uuid', $documentUuid)->value('id')
                : null;
        }

        return $payload;
    }
}