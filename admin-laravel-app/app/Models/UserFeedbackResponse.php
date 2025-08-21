<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class UserFeedbackResponse extends Model
{
    use HasFactory, Auditable;

    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';

    protected $fillable = [
        'user_feedback_id',
        'admin_id',
        'message',
        'is_internal',
        'is_solution',
    ];

    protected $casts = [
        'created' => 'datetime',
        'modified' => 'datetime',
        'is_internal' => 'boolean',
        'is_solution' => 'boolean',
    ];

    /**
     * Get the feedback this response belongs to
     */
    public function userFeedback(): BelongsTo
    {
        return $this->belongsTo(UserFeedback::class);
    }

    /**
     * Get the admin who created this response
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Check if response is internal
     */
    public function isInternal(): bool
    {
        return $this->is_internal;
    }

    /**
     * Check if response is a solution
     */
    public function isSolution(): bool
    {
        return $this->is_solution;
    }

    /**
     * Scope for public responses
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
