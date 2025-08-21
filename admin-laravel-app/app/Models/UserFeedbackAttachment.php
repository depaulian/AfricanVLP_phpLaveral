<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Traits\Auditable;

class UserFeedbackAttachment extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'user_feedback_id',
        'user_feedback_response_id',
        'filename',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
        'deleted_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Get the feedback this attachment belongs to
     */
    public function userFeedback(): BelongsTo
    {
        return $this->belongsTo(UserFeedback::class);
    }

    /**
     * Get the response this attachment belongs to (if any)
     */
    public function userFeedbackResponse(): BelongsTo
    {
        return $this->belongsTo(UserFeedbackResponse::class);
    }

    /**
     * Get the user who uploaded this attachment
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the file URL
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
        ];
        
        return in_array($this->mime_type, $documentTypes);
    }

    /**
     * Check if file is a video
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if file is audio
     */
    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    /**
     * Get file type icon
     */
    public function getFileTypeIcon(): string
    {
        if ($this->isImage()) {
            return 'image';
        } elseif ($this->isDocument()) {
            return 'file-text';
        } elseif ($this->isVideo()) {
            return 'video';
        } elseif ($this->isAudio()) {
            return 'music';
        } else {
            return 'file';
        }
    }

    /**
     * Get download URL
     */
    public function getDownloadUrl(): string
    {
        return route('admin.feedback.attachment.download', $this->id);
    }

    /**
     * Check if file exists in storage
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Delete file from storage
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::delete($this->file_path);
        }
        
        return true;
    }

    /**
     * Scope for feedback attachments
     */
    public function scopeForFeedback($query, $feedbackId)
    {
        return $query->where('user_feedback_id', $feedbackId);
    }

    /**
     * Scope for response attachments
     */
    public function scopeForResponse($query, $responseId)
    {
        return $query->where('user_feedback_response_id', $responseId);
    }

    /**
     * Scope for images
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope for documents
     */
    public function scopeDocuments($query)
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];
        
        return $query->whereIn('mime_type', $documentTypes);
    }
}
