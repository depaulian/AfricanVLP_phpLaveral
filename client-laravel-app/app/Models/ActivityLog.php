<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subject model that the activity was performed on
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by action type
     */
    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by subject type
     */
    public function scopeForSubjectType($query, string $subjectType)
    {
        return $query->where('subject_type', $subjectType);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted properties for display
     */
    public function getFormattedPropertiesAttribute(): string
    {
        if (empty($this->properties)) {
            return 'No additional data';
        }

        $formatted = [];
        foreach ($this->properties as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
        }

        return implode(', ', $formatted);
    }

    /**
     * Get the activity description with subject information
     */
    public function getFullDescriptionAttribute(): string
    {
        $description = $this->description;
        
        if ($this->subject) {
            $subjectName = $this->getSubjectName();
            $description .= " on {$subjectName}";
        }

        return $description;
    }

    /**
     * Get a human-readable name for the subject
     */
    private function getSubjectName(): string
    {
        if (!$this->subject) {
            return 'Unknown';
        }

        // Try common name attributes
        $nameAttributes = ['name', 'title', 'email', 'username'];
        
        foreach ($nameAttributes as $attribute) {
            if (isset($this->subject->$attribute)) {
                return $this->subject->$attribute;
            }
        }

        // Fallback to model type and ID
        $modelName = class_basename($this->subject_type);
        return "{$modelName} #{$this->subject_id}";
    }

    /**
     * Common activity actions
     */
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_VIEWED = 'viewed';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';
    const ACTION_PUBLISHED = 'published';
    const ACTION_UNPUBLISHED = 'unpublished';
    const ACTION_EXPORTED = 'exported';
    const ACTION_IMPORTED = 'imported';
    const ACTION_SENT = 'sent';
    const ACTION_RECEIVED = 'received';
}
