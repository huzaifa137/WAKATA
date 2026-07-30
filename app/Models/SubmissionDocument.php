<?php

namespace App\Models;

use App\Support\Sync\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionDocument extends Model
{
    use HasFactory;
    use Syncable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'submission_documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'submission_batch_id',
        'file_name',
        'original_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'student_ids',
        'school_id',
        'submitted_by',
        'storage_disk',
        'uploaded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'student_ids' => 'array',
        'file_size' => 'integer',
        'uploaded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'formatted_file_size',
        'file_extension',
        'file_icon_class',
        'can_preview',
        'url',
    ];

    /**
     * Get the school that owns the document.
     */
    public function school()
    {
        return $this->belongsTo(House::class, 'school_id', 'ID');
    }

    /**
     * Get the student registrations associated with this document.
     */
    public function studentRegistrations()
    {
        return $this->hasMany(StudentRegistration::class, 'submission_document_id');
    }

    /**
     * Get the user who submitted this document.
     */
    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Get all student IDs as a collection.
     */
    public function getStudentIdsListAttribute()
    {
        return collect($this->student_ids ?? []);
    }

    /**
     * Get the count of students associated with this document.
     */
    public function getStudentCountAttribute()
    {
        return count($this->student_ids ?? []);
    }

    /**
     * Get formatted file size (e.g., "2.5 MB").
     */
    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file extension.
     */
    public function getFileExtensionAttribute()
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    /**
     * Get appropriate Font Awesome icon class for the file type.
     */
    public function getFileIconClassAttribute()
    {
        $extension = $this->file_extension;
        
        // Image files
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'])) {
            return 'fas fa-file-image';
        }
        
        // PDF files
        if ($extension === 'pdf') {
            return 'fas fa-file-pdf';
        }
        
        // Word documents
        if (in_array($extension, ['doc', 'docx'])) {
            return 'fas fa-file-word';
        }
        
        // Excel documents
        if (in_array($extension, ['xls', 'xlsx', 'csv'])) {
            return 'fas fa-file-excel';
        }
        
        // PowerPoint documents
        if (in_array($extension, ['ppt', 'pptx'])) {
            return 'fas fa-file-powerpoint';
        }
        
        // Text files
        if (in_array($extension, ['txt', 'rtf'])) {
            return 'fas fa-file-alt';
        }
        
        // Archives
        if (in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'])) {
            return 'fas fa-file-archive';
        }
        
        // Default
        return 'fas fa-file';
    }

    /**
     * Get appropriate color class for the file type badge.
     */
    public function getFileColorClassAttribute()
    {
        $extension = $this->file_extension;
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return 'info';
        }
        if ($extension === 'pdf') {
            return 'danger';
        }
        if (in_array($extension, ['doc', 'docx'])) {
            return 'primary';
        }
        if (in_array($extension, ['xls', 'xlsx'])) {
            return 'success';
        }
        
        return 'secondary';
    }

    /**
     * Check if the file can be previewed in browser.
     */
    public function getCanPreviewAttribute()
    {
        $extension = $this->file_extension;
        
        // Images and PDFs can be previewed
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']);
    }

    /**
     * Get full URL to the document.
     */
    public function getUrlAttribute()
    {
        return asset($this->file_path);
    }

    /**
     * Get document details for API responses.
     */
    public function getDetailsAttribute()
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'original_name' => $this->original_name ?? $this->file_name,
            'file_size' => $this->file_size,
            'formatted_size' => $this->formatted_file_size,
            'file_type' => $this->file_type,
            'extension' => $this->file_extension,
            'icon_class' => $this->file_icon_class,
            'color_class' => $this->file_color_class,
            'can_preview' => $this->can_preview,
            'url' => $this->url,
            'student_count' => $this->student_count,
            'uploaded_at' => $this->uploaded_at ? $this->uploaded_at->format('Y-m-d H:i:s') : $this->created_at->format('Y-m-d H:i:s'),
            'uploaded_human' => $this->uploaded_at ? $this->uploaded_at->diffForHumans() : $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Scope a query to only include documents of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        if ($type === 'image') {
            return $query->whereIn('file_extension', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        }
        
        if ($type === 'pdf') {
            return $query->where('file_extension', 'pdf');
        }
        
        if ($type === 'document') {
            return $query->whereIn('file_extension', ['doc', 'docx', 'txt', 'rtf']);
        }
        
        return $query;
    }

    /**
     * Scope a query to only include documents by school.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Created offline (a school uploading a document while disconnected)
     * -> matched by uuid, never the local auto-increment id.
     */
    public function syncKey(): array
    {
        return ['uuid' => $this->uuid];
    }

    /**
     * The actual file lives on disk, not in the database — so besides
     * the normal columns, embed the file's bytes (base64) so the
     * receiving side can write the same file to its own disk. Fine for
     * typical scanned submission documents; very large files should be
     * handled with a dedicated upload step instead (see SYNC_SETUP.md).
     */
    public function syncPayload(): array
    {
        $payload = $this->only($this->getFillable());

        if ($this->file_path) {
            $absolutePath = public_path($this->file_path);
            if (file_exists($absolutePath)) {
                $payload['_file_base64'] = base64_encode(file_get_contents($absolutePath));
            }
        }

        return $payload;
    }

    /**
     * On the receiving side: write the embedded file to disk at the
     * same relative path it was uploaded to, then strip the
     * transport-only key before the row itself is saved.
     *
     * Note: `submitted_by` travels as-is (a raw user id). If the school
     * and central installs have different local ids for the same staff
     * member, this can end up pointing at the wrong (or no) user
     * centrally — reconciling users across installs is out of scope for
     * this pilot; see SYNC_SETUP.md.
     */
    public static function syncMaterializePayload(array $payload): array
    {
        if (array_key_exists('_file_base64', $payload)) {
            $base64 = $payload['_file_base64'];
            unset($payload['_file_base64']);

            if ($base64 && !empty($payload['file_path'])) {
                $destination = public_path($payload['file_path']);
                $directory = dirname($destination);

                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                file_put_contents($destination, base64_decode($base64));
            }
        }

        return $payload;
    }

    /**
     * Delete the actual file when the model is deleted.
     */
    protected static function booted()
    {
        static::deleting(function ($document) {
            // Delete the physical file when the record is deleted
            $filePath = public_path($document->file_path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        });
    }
}