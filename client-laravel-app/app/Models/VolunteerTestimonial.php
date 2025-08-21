<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerTestimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'volunteer_id',
        'author_id',
        'organization_id',
        'assignment_id',
        'title',
        'content',
        'rating',
        'author_type',
        'author_position',
        'is_featured',
        'is_public',
        'is_approved',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_featured' => 'boolean',
        'is_public' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the volunteer this testimonial is about
     */
    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'volunteer_id');
    }

    /**
     * Get the author of this testimonial
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the organization this testimonial is associated with
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the assignment this testimonial is about
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(VolunteerAssignment::class);
    }

    /**
     * Get the user who approved this testimonial
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope for public testimonials
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for approved testimonials
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for featured testimonials
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for testimonials by author type
     */
    public function scopeByAuthorType($query, string $authorType)
    {
        return $query->where('author_type', $authorType);
    }

    /**
     * Scope for testimonials with rating
     */
    public function scopeWithRating($query, int $minRating = 1)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope for recent testimonials
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get the author type display name
     */
    public function getAuthorTypeDisplayAttribute(): string
    {
        return match ($this->author_type) {
            'supervisor' => 'Supervisor',
            'colleague' => 'Fellow Volunteer',
            'beneficiary' => 'Beneficiary',
            'organization' => 'Organization Representative',
            default => 'Unknown',
        };
    }

    /**
     * Get the rating stars HTML
     */
    public function getRatingStarsAttribute(): string
    {
        if (!$this->rating) {
            return '';
        }

        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $stars .= '<i class="fas fa-star text-yellow-400"></i>';
            } else {
                $stars .= '<i class="far fa-star text-gray-300"></i>';
            }
        }

        return $stars;
    }

    /**
     * Get excerpt of content
     */
    public function getExcerptAttribute(): string
    {
        return \Str::limit($this->content, 150);
    }

    /**
     * Check if testimonial is recently created
     */
    public function isRecentlyCreated(int $days = 7): bool
    {
        return $this->created_at->isAfter(now()->subDays($days));
    }

    /**
     * Check if testimonial needs approval
     */
    public function needsApproval(): bool
    {
        return !$this->is_approved && $this->is_public;
    }

    /**
     * Approve the testimonial
     */
    public function approve(User $approver): bool
    {
        return $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => $approver->id,
        ]);
    }

    /**
     * Reject the testimonial
     */
    public function reject(): bool
    {
        return $this->update([
            'is_approved' => false,
            'is_public' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(): bool
    {
        return $this->update([
            'is_featured' => !$this->is_featured
        ]);
    }
}