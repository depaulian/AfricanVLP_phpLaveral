<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'date_of_birth',
        'gender',
        'phone_number',
        'address',
        'city_id',
        'country_id',
        'linkedin_url',
        'twitter_url',
        'facebook_url',
        'website_url',
        'profile_image_url',
        'cover_image_url',
        'profile_completion_percentage',
        'is_public',
        'settings',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_public' => 'boolean',
        'settings' => 'array',
        'profile_completion_percentage' => 'integer',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the city associated with the profile.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country associated with the profile.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the user's age based on date of birth.
     */
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get the full address combining address, city, and country.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city?->name,
            $this->country?->name
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Calculate and update the profile completion percentage.
     */
    public function calculateCompletionPercentage(): int
    {
        $fields = [
            'bio', 'date_of_birth', 'phone_number', 'address',
            'city_id', 'profile_image_url'
        ];
        
        $completed = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $completed++;
            }
        }
        
        // Add points for related data
        if ($this->user->skills()->count() > 0) $completed++;
        if ($this->user->volunteeringInterests()->count() > 0) $completed++;
        if ($this->user->volunteeringHistory()->count() > 0) $completed++;
        
        $total = count($fields) + 3; // 3 for related data
        $percentage = round(($completed / $total) * 100);
        
        $this->update(['profile_completion_percentage' => $percentage]);
        
        return $percentage;
    }

    /**
     * Check if the profile is complete.
     */
    public function isComplete(): bool
    {
        return $this->profile_completion_percentage >= 80;
    }

    /**
     * Get missing profile fields.
     */
    public function getMissingFields(): array
    {
        $fields = [
            'bio' => 'Bio',
            'date_of_birth' => 'Date of Birth',
            'phone_number' => 'Phone Number',
            'address' => 'Address',
            'city_id' => 'City',
            'profile_image_url' => 'Profile Image'
        ];
        
        $missing = [];
        foreach ($fields as $field => $label) {
            if (empty($this->$field)) {
                $missing[] = $label;
            }
        }
        
        return $missing;
    }
}