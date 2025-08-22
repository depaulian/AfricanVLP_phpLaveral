<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class SupportTicketResponse extends Model
{
    use HasFactory, Auditable;



    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'is_internal',
        'is_solution',
        'response_time_minutes',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'is_solution' => 'boolean',
        'response_time_minutes' => 'integer',
    ];

    /**
     * Get the support ticket this response belongs to
     */
    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class);
    }

    /**
     * Get the user who created this response
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attachments for this response
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }

    /**
     * Check if response is from admin
     */
    public function isFromAdmin(): bool
    {
        return $this->user && $this->user->isAdmin();
    }

    /**
     * Check if response is from customer
     */
    public function isFromCustomer(): bool
    {
        return !$this->isFromAdmin();
    }

    /**
     * Scope for public responses (not internal)
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    /**
     * Scope for internal responses
     */
    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope for solution responses
     */
    public function scopeSolutions($query)
    {
        return $query->where('is_solution', true);
    }
}
