<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'country_id',
        'state_province',
        'latitude',
        'longitude',
        'population',
        'timezone',
        'status',
        'settings',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'population' => 'integer',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the country that owns the city.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the users for the city.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the organizations for the city.
     */
    public function organizations()
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * Get the events for the city.
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the organization offices for the city.
     */
    public function organizationOffices()
    {
        return $this->hasMany(OrganizationOffice::class);
    }

    /**
     * Scope for active cities.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive cities.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope for cities in a specific country.
     */
    public function scopeInCountry($query, $countryId)
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Get the full location name (City, Country).
     */
    public function getFullLocationAttribute()
    {
        return $this->name . ', ' . $this->country->name;
    }

    /**
     * Get formatted population.
     */
    public function getFormattedPopulationAttribute()
    {
        if (!$this->population) {
            return 'N/A';
        }

        if ($this->population >= 1000000) {
            return number_format($this->population / 1000000, 1) . 'M';
        } elseif ($this->population >= 1000) {
            return number_format($this->population / 1000, 1) . 'K';
        }

        return number_format($this->population);
    }

    /**
     * Check if city has coordinates.
     */
    public function hasCoordinates()
    {
        return !is_null($this->latitude) && !is_null($this->longitude);
    }

    /**
     * Get coordinates as array.
     */
    public function getCoordinatesAttribute()
    {
        if (!$this->hasCoordinates()) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }
}