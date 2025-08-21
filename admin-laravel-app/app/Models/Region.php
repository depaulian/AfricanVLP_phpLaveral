<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the countries for the region.
     */
    public function countries()
    {
        return $this->hasMany(Country::class);
    }

    /**
     * Get active countries for the region.
     */
    public function activeCountries()
    {
        return $this->hasMany(Country::class)->where('status', 'active');
    }

    /**
     * Scope for active regions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive regions.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Get the total number of countries in this region.
     */
    public function getCountriesCountAttribute()
    {
        return $this->countries()->count();
    }

    /**
     * Get the total number of active countries in this region.
     */
    public function getActiveCountriesCountAttribute()
    {
        return $this->activeCountries()->count();
    }
}