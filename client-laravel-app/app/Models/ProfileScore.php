<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'completion_score',
        'quality_score',
        'engagement_score',
        'verification_score',
        'total_score',
        'rank_position',
        'last_calculated_at',
    ];

    protected $casts = [
        'completion_score' => 'integer',
        'quality_score' => 'integer',
        'engagement_score' => 'integer',
        'verification_score' => 'integer',
        'total_score' => 'integer',
        'rank_position' => 'integer',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the score.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the profile strength level based on total score.
     */
    public function getStrengthLevel(): string
    {
        if ($this->total_score >= 90) {
            return 'Excellent';
        } elseif ($this->total_score >= 75) {
            return 'Very Good';
        } elseif ($this->total_score >= 60) {
            return 'Good';
        } elseif ($this->total_score >= 40) {
            return 'Fair';
        } else {
            return 'Needs Improvement';
        }
    }

    /**
     * Get the strength level color for UI display.
     */
    public function getStrengthColor(): string
    {
        $level = $this->getStrengthLevel();
        
        return match ($level) {
            'Excellent' => 'green',
            'Very Good' => 'blue',
            'Good' => 'yellow',
            'Fair' => 'orange',
            'Needs Improvement' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get improvement recommendations based on scores.
     */
    public function getRecommendations(): array
    {
        $recommendations = [];

        if ($this->completion_score < 80) {
            $recommendations[] = [
                'type' => 'completion',
                'title' => 'Complete Your Profile',
                'description' => 'Add missing information to increase your profile completion score.',
                'action' => 'Complete Profile',
                'url' => route('profile.edit'),
            ];
        }

        if ($this->verification_score < 60) {
            $recommendations[] = [
                'type' => 'verification',
                'title' => 'Verify Your Information',
                'description' => 'Upload documents and verify your skills to build trust.',
                'action' => 'Upload Documents',
                'url' => route('profile.documents'),
            ];
        }

        if ($this->engagement_score < 50) {
            $recommendations[] = [
                'type' => 'engagement',
                'title' => 'Increase Platform Activity',
                'description' => 'Apply for opportunities and engage with the community.',
                'action' => 'Browse Opportunities',
                'url' => route('volunteering.index'),
            ];
        }

        if ($this->quality_score < 70) {
            $recommendations[] = [
                'type' => 'quality',
                'title' => 'Improve Profile Quality',
                'description' => 'Add detailed descriptions and showcase your experience.',
                'action' => 'Edit Profile',
                'url' => route('profile.edit'),
            ];
        }

        return $recommendations;
    }

    /**
     * Check if score needs recalculation (older than 24 hours).
     */
    public function needsRecalculation(): bool
    {
        return !$this->last_calculated_at || 
               $this->last_calculated_at->isBefore(now()->subHours(24));
    }
}