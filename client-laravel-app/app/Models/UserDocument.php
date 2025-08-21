<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'description',
        'document_type',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'verification_status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'expiry_date',
        'is_sensitive',
        'is_archived',
        'archived_at',
        'archive_reason',
        'metadata',
        'verification_notes',
        'verification_requested_at',
        'upload_ip',
        'download_count',
        'last_downloaded_at',
        'share_count',
        'last_shared_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'expiry_date' => 'date',
        'archived_at' => 'datetime',
        'verification_requested_at' => 'datetime',
        'last_downloaded_at' => 'datetime',
        'last_shared_at' => 'datetime',
        'file_size' => 'integer',
        'download_count' => 'integer',
        'share_count' => 'integer',
        'is_sensitive' => 'boolean',
        'is_archived' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Document types enum values.
     */
    const DOCUMENT_TYPES = [
        'resume' => 'Resume',
        'certificate' => 'Certificate',
        'id' => 'ID Document',
        'transcript' => 'Transcript',
        'other' => 'Other',
    ];

    /**
     * Verification status enum values.
     */
    const VERIFICATION_STATUSES = [
        'pending' => 'Pending',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ];

    /**
     * Get the user that owns the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who verified this document.
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Verify the document.
     */
    public function verify(User $verifier): void
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'rejection_reason' => null
        ]);
    }

    /**
     * Reject the document with a reason.
     */
    public function reject(User $verifier, string $reason): void
    {
        $this->update([
            'verification_status' => 'rejected',
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'rejection_reason' => $reason
        ]);
    }

    /**
     * Get the file size in human readable format.
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
     * Get the download URL for the document.
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('profile.documents.download', $this);
    }

    /**
     * Get the document type label.
     */
    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? ucfirst($this->document_type);
    }

    /**
     * Get the verification status label.
     */
    public function getVerificationStatusLabelAttribute(): string
    {
        return self::VERIFICATION_STATUSES[$this->verification_status] ?? ucfirst($this->verification_status);
    }

    /**
     * Check if the document is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if the document is pending verification.
     */
    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    /**
     * Check if the document is rejected.
     */
    public function isRejected(): bool
    {
        return $this->verification_status === 'rejected';
    }

    /**
     * Scope to get verified documents.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope to get pending documents.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('verification_status', 'pending');
    }

    /**
     * Scope to get rejected documents.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('verification_status', 'rejected');
    }

    /**
     * Scope to get documents by type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('document_type', $type);
    }

    /**
     * Check if the document is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the document is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Get the file extension.
     */
    public function getFileExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Check if the document is expired.
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Check if the document is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date && 
               $this->expiry_date->isFuture() && 
               $this->expiry_date->diffInDays(now()) <= $days;
    }

    /**
     * Get the category configuration.
     */
    public function getCategoryConfigAttribute(): array
    {
        return config("documents.categories.{$this->category}", []);
    }

    /**
     * Check if the document requires verification.
     */
    public function requiresVerification(): bool
    {
        $requireVerification = config('documents.verification.require_admin_verification', []);
        return in_array($this->category, $requireVerification);
    }

    /**
     * Increment download count.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_downloaded_at' => now()]);
    }

    /**
     * Increment share count.
     */
    public function incrementShareCount(): void
    {
        $this->increment('share_count');
        $this->update(['last_shared_at' => now()]);
    }

    /**
     * Archive the document.
     */
    public function archive(string $reason = null): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archive_reason' => $reason
        ]);
    }

    /**
     * Restore the document from archive.
     */
    public function restore(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
            'archive_reason' => null
        ]);
    }

    /**
     * Get the document's security level.
     */
    public function getSecurityLevelAttribute(): string
    {
        if ($this->is_sensitive) {
            return 'high';
        }

        $sensitiveCategories = ['identity', 'professional'];
        if (in_array($this->category, $sensitiveCategories)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Scope to get documents expiring within specified days.
     */
    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    /**
     * Scope to get expired documents.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Scope to get archived documents.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope to get active (non-archived) documents.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope to get sensitive documents.
     */
    public function scopeSensitive(Builder $query): Builder
    {
        return $query->where('is_sensitive', true);
    }

    /**
     * Get the document's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->file_name;
    }

    /**
     * Get the document's status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->verification_status) {
            'verified' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'secondary'
        };
    }

    /**
     * Get the document's priority based on age and category.
     */
    public function getPriorityAttribute(): string
    {
        $daysWaiting = $this->created_at->diffInDays(now());
        $highPriorityCategories = ['identity', 'professional'];
        
        if (in_array($this->category, $highPriorityCategories) && $daysWaiting > 3) {
            return 'high';
        }
        
        if ($daysWaiting > 7) {
            return 'high';
        }
        
        if ($daysWaiting > 3) {
            return 'medium';
        }
        
        return 'normal';
    }
}