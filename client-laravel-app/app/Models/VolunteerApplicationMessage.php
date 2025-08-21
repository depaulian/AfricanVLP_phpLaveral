<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerApplicationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'content',
        'from_admin',
        'sender_id'
    ];

    protected $casts = [
        'from_admin' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the application this message belongs to
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(VolunteerApplication::class, 'application_id');
    }

    /**
     * Get the user who sent this message
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Scope to get messages from admin
     */
    public function scopeFromAdmin($query)
    {
        return $query->where('from_admin', true);
    }

    /**
     * Scope to get messages from volunteer
     */
    public function scopeFromVolunteer($query)
    {
        return $query->where('from_admin', false);
    }

    /**
     * Get the sender type for display
     */
    public function getSenderTypeAttribute(): string
    {
        return $this->from_admin ? 'Organization' : 'Volunteer';
    }
}