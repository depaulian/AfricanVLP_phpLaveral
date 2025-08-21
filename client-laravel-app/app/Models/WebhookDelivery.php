<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event',
        'payload',
        'context',
        'status',
        'attempts',
        'response_status',
        'response_body',
        'delivered_at',
        'failed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'context' => 'array',
        'attempts' => 'integer',
        'response_status' => 'integer',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime'
    ];

    /**
     * Get the webhook that owns this delivery
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Check if delivery was successful
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if delivery failed
     */
    public function hasFailed(): bool
    {
        return in_array($this->status, ['failed', 'exhausted']);
    }

    /**
     * Check if delivery is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get response time in milliseconds
     */
    public function getResponseTimeAttribute(): ?int
    {
        if (!$this->delivered_at) {
            return null;
        }

        return $this->created_at->diffInMilliseconds($this->delivered_at);
    }

    /**
     * Scope for successful deliveries
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope for failed deliveries
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'exhausted']);
    }

    /**
     * Scope for pending deliveries
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for specific event
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }
}