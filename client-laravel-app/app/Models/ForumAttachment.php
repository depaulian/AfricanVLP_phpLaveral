<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ForumAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'download_count'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer'
    ];

    /**
     * Get the post that owns the attachment.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'post_id');
    }

    /**
     * Get the file size in human readable format.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the file extension.
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Check if the file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the file is a document.
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
            'text/plain'
        ];
        
        return in_array($this->mime_type, $documentTypes);
    }

    /**
     * Get the download URL for the attachment.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('forum.attachments.download', $this->id);
    }

    /**
     * Get the file URL if it's publicly accessible.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->url($this->file_path);
        }
        
        return null;
    }

    /**
     * Increment the download count.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get the file icon based on mime type.
     */
    public function getFileIconAttribute(): string
    {
        if ($this->isImage()) {
            return 'fas fa-image';
        }
        
        if ($this->isDocument()) {
            return match ($this->mime_type) {
                'application/pdf' => 'fas fa-file-pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fas fa-file-word',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fas fa-file-excel',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'fas fa-file-powerpoint',
                'text/plain' => 'fas fa-file-alt',
                'text/csv' => 'fas fa-file-csv',
                default => 'fas fa-file'
            };
        }
        
        // Check for archives
        if ($this->isArchive()) {
            return 'fas fa-file-archive';
        }
        
        // Check for code files
        if ($this->isCode()) {
            return 'fas fa-file-code';
        }
        
        return 'fas fa-file';
    }

    /**
     * Check if the file is an archive.
     */
    public function isArchive(): bool
    {
        $archiveTypes = [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ];
        
        return in_array($this->mime_type, $archiveTypes);
    }

    /**
     * Check if the file is a code file.
     */
    public function isCode(): bool
    {
        $codeTypes = [
            'text/html',
            'text/css',
            'text/javascript',
            'application/json',
            'application/xml',
        ];
        
        return in_array($this->mime_type, $codeTypes);
    }

    /**
     * Check if the file exists in storage.
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Delete the file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::delete($this->file_path);
        }
        
        return true;
    }
}