<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProfileImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'original_path',
        'profile_path',
        'thumbnail_path',
        'file_size',
        'mime_type',
        'width',
        'height',
        'is_primary',
        'is_approved',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'metadata'
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Get the user that owns the profile image
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved the image
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected the image
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the original image URL
     */
    public function getOriginalUrlAttribute(): string
    {
        return Storage::url($this->original_path);
    }

    /**
     * Get the profile image URL
     */
    public function getProfileUrlAttribute(): string
    {
        return Storage::url($this->profile_path);
    }

    /**
     * Get the thumbnail image URL
     */
    public function getThumbnailUrlAttribute(): string
    {
        return Storage::url($this->thumbnail_path);
    }

    /**
     * Get human readable file size
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get image dimensions as string
     */
    public function getDimensionsAttribute(): string
    {
        if ($this->width && $this->height) {
            return $this->width . ' × ' . $this->height . ' px';
        }
        
        return 'Unknown';
    }

    /**
     * Get approval status label
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_approved) {
            return 'Approved';
        }
        
        if ($this->rejected_at) {
            return 'Rejected';
        }
        
        return 'Pending Review';
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_approved) {
            return 'success';
        }
        
        if ($this->rejected_at) {
            return 'danger';
        }
        
        return 'warning';
    }

    /**
     * Check if image is pending approval
     */
    public function isPending(): bool
    {
        return !$this->is_approved && !$this->rejected_at;
    }

    /**
     * Check if image is rejected
     */
    public function isRejected(): bool
    {
        return !is_null($this->rejected_at);
    }

    /**
     * Scope for approved images
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for pending images
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false)->whereNull('rejected_at');
    }

    /**
     * Scope for rejected images
     */
    public function scopeRejected($query)
    {
        return $query->whereNotNull('rejected_at');
    }

    /**
     * Scope for primary images
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get crop data from metadata
     */
    public function getCropDataAttribute(): ?array
    {
        return $this->metadata['crop_data'] ?? null;
    }

    /**
     * Get upload timestamp from metadata
     */
    public function getUploadedAtAttribute(): ?string
    {
        return $this->metadata['uploaded_at'] ?? null;
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // When deleting, clean up files
        static::deleting(function ($image) {
            Storage::disk('public')->delete([
                $image->original_path,
                $image->profile_path,
                $image->thumbnail_path
            ]);
        });
    }
}