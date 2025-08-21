<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SupportTicketAttachment extends Model
{
    use HasFactory;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'support_ticket_id',
        'support_ticket_response_id',
        'user_id',
        'filename',
        'original_filename',
        'file_path',
        'file_size',
        'mime_type',
        'file_type',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Get the support ticket this attachment belongs to
     */
    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class);
    }

    /**
     * Get the support ticket response this attachment belongs to
     */
    public function supportTicketResponse(): BelongsTo
    {
        return $this->belongsTo(SupportTicketResponse::class);
    }

    /**
     * Get the user who uploaded this attachment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the file URL
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
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
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv'
        ]);
    }

    /**
     * Get file icon based on type
     */
    public function getFileIconAttribute(): string
    {
        if ($this->isImage()) {
            return 'image';
        } elseif ($this->isDocument()) {
            return 'document';
        } elseif (str_starts_with($this->mime_type, 'video/')) {
            return 'video';
        } elseif (str_starts_with($this->mime_type, 'audio/')) {
            return 'audio';
        } else {
            return 'file';
        }
    }
}
