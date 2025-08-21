<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'iso_code',
        'region_id',
        'flag_url',
        'currency_code',
        'phone_code',
        'latitude',
        'longitude',
        'status',
        'settings',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the region that owns the country.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the cities for the country.
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }

    /**
     * Get active cities for the country.
     */
    public function activeCities()
    {
        return $this->hasMany(City::class)->where('status', 'active');
    }

    /**
     * Get the users for the country.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the organizations for the country.
     */
    public function organizations()
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * Get the events for the country.
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Scope for active countries.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for inactive countries.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope for countries in a specific region.
     */
    public function scopeInRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Get the total number of cities in this country.
     */
    public function getCitiesCountAttribute()
    {
        return $this->cities()->count();
    }

    /**
     * Get the total number of active cities in this country.
     */
    public function getActiveCitiesCountAttribute()
    {
        return $this->activeCities()->count();
    }

    /**
     * Get the flag image URL with fallback.
     */
    public function getFlagImageAttribute()
    {
        return $this->flag_url ?: asset('img/flags/default.png');
    }

    /**
     * Get formatted phone code.
     */
    public function getFormattedPhoneCodeAttribute()
    {
        return $this->phone_code ? '+' . $this->phone_code : null;
    }
}