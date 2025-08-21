<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'events',
        'secret',
        'active',
        'verify_ssl',
        'timeout',
        'max_retries',
        'metadata',
        'successful_deliveries',
        'failed_deliveries',
        'last_successful_delivery',
        'last_failed_delivery'
    ];

    protected $casts = [
        'events' => 'array',
        'metadata' => 'array',
        'active' => 'boolean',
        'verify_ssl' => 'boolean',
        'successful_deliveries' => 'integer',
        'failed_deliveries' => 'integer',
        'last_successful_delivery' => 'datetime',
        'last_failed_delivery' => 'datetime'
    ];

    protected $hidden = [
        'secret'
    ];

    /**
     * Get webhook deliveries
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    /**
     * Check if webhook is subscribed to an event
     */
    public function isSubscribedTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRateAttribute(): float
    {
        $total = $this->successful_deliveries + $this->failed_deliveries;
        
        if ($total === 0) {
            return 0;
        }

        return round(($this->successful_deliveries / $total) * 100, 2);
    }

    /**
     * Scope for active webhooks
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope for webhooks subscribed to specific event
     */
    public function scopeSubscribedTo($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }
}