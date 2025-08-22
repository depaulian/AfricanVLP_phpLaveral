<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\FileUploadService;

class ResourceFile extends Model
{


    protected $fillable = [
        'resource_id',
        'filename',
        'original_filename',
        'file_path',
        'file_size',
        'file_type',
        'file_category',
        'mime_type',
        'cloudinary_public_id',
        'cloudinary_url',
        'cloudinary_secure_url',
        'width',
        'height',
        'download_count',
        'status',
        'description'
    ];

    protected $casts = [
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'download_count' => 'integer'
    ];

    /**
     * Get the resource that owns this file.
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Get the file URL (secure URL from Cloudinary).
     */
    public function getFileUrl(): string
    {
        return $this->cloudinary_secure_url ?: $this->cloudinary_url;
    }

    /**
     * Get the thumbnail URL for images.
     */
    public function getThumbnailUrl(): ?string
    {
        if ($this->file_category !== 'images' || !$this->cloudinary_public_id) {
            return null;
        }

        $fileUploadService = app(FileUploadService::class);
        return $fileUploadService->getTransformedUrl($this->cloudinary_public_id, 'thumbnail');
    }

    /**
     * Get the medium-sized URL for images.
     */
    public function getMediumUrl(): ?string
    {
        if ($this->file_category !== 'images' || !$this->cloudinary_public_id) {
            return null;
        }

        $fileUploadService = app(FileUploadService::class);
        return $fileUploadService->getTransformedUrl($this->cloudinary_public_id, 'medium');
    }

    /**
     * Get the large-sized URL for images.
     */
    public function getLargeUrl(): ?string
    {
        if ($this->file_category !== 'images' || !$this->cloudinary_public_id) {
            return null;
        }

        $fileUploadService = app(FileUploadService::class);
        return $fileUploadService->getTransformedUrl($this->cloudinary_public_id, 'large');
    }

    /**
     * Get all transformation URLs for images.
     */
    public function getAllTransformations(): array
    {
        if ($this->file_category !== 'images' || !$this->cloudinary_public_id) {
            return [];
        }

        $fileUploadService = app(FileUploadService::class);
        return $fileUploadService->getAllTransformations($this->cloudinary_public_id);
    }

    /**
     * Check if the file is an image.
     */
    public function isImage(): bool
    {
        return $this->file_category === 'images';
    }

    /**
     * Check if the file is a document.
     */
    public function isDocument(): bool
    {
        return $this->file_category === 'documents';
    }

    /**
     * Check if the file is a video.
     */
    public function isVideo(): bool
    {
        return $this->file_category === 'videos';
    }

    /**
     * Check if the file is audio.
     */
    public function isAudio(): bool
    {
        return $this->file_category === 'audio';
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanReadableSize(): string
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
     * Increment the download count.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Delete the file from Cloudinary when model is deleted.
     */
    public function delete()
    {
        if ($this->cloudinary_public_id) {
            $fileUploadService = app(FileUploadService::class);
            $resourceType = $this->isImage() ? 'image' : ($this->isVideo() ? 'video' : 'raw');
            $fileUploadService->deleteFile($this->cloudinary_public_id, $resourceType);
        }

        return parent::delete();
    }

    /**
     * Scope for images only.
     */
    public function scopeImages($query)
    {
        return $query->where('file_category', 'images');
    }

    /**
     * Scope for documents only.
     */
    public function scopeDocuments($query)
    {
        return $query->where('file_category', 'documents');
    }

    /**
     * Scope for videos only.
     */
    public function scopeVideos($query)
    {
        return $query->where('file_category', 'videos');
    }

    /**
     * Scope for audio files only.
     */
    public function scopeAudio($query)
    {
        return $query->where('file_category', 'audio');
    }
}