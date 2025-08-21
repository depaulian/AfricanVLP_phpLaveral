<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'content',
        'start_date',
        'end_date',
        'location',
        'city_id',
        'country_id',
        'region_id',
        'latitude',
        'longitude',
        'image',
        'status',
        'max_participants',
        'current_participants',
        'is_featured',
        'contact_email',
        'contact_phone',
        'registration_fee',
        'registration_deadline',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_deadline' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'registration_fee' => 'decimal:2',
        'max_participants' => 'integer',
        'current_participants' => 'integer',
        'is_featured' => 'boolean',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    /**
     * Get the organization that owns the event.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the city where the event takes place.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the country where the event takes place.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the region where the event takes place.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the volunteering opportunities for this event.
     */
    public function volunteeringOpportunities(): HasMany
    {
        return $this->hasMany(VolunteeringOpportunity::class);
    }

    /**
     * Check if the event is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the event is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->start_date > now();
    }

    /**
     * Check if the event is ongoing.
     */
    public function isOngoing(): bool
    {
        return $this->start_date <= now() && $this->end_date >= now();
    }

    /**
     * Check if the event has ended.
     */
    public function hasEnded(): bool
    {
        return $this->end_date < now();
    }

    /**
     * Check if registration is open.
     */
    public function isRegistrationOpen(): bool
    {
        return $this->registration_deadline > now() && 
               $this->current_participants < $this->max_participants;
    }

    /**
     * Get available spots.
     */
    public function getAvailableSpots(): int
    {
        return max(0, $this->max_participants - $this->current_participants);
    }

    /**
     * Get the event image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/events/' . $this->image) : null;
    }

    /**
     * Check if the event has volunteering opportunities.
     */
    public function hasVolunteeringOpportunities(): bool
    {
        return $this->volunteeringOpportunities()->where('status', 'active')->exists();
    }
}