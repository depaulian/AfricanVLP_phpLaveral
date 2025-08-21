<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class OrganizationRegistrationStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'step_name',
        'step_data',
        'is_completed',
        'completed_at',
        'organization_id',
        'user_id',
    ];

    protected $casts = [
        'step_data' => 'array',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Organization registration step names.
     */
    const STEPS = [
        'organization_details' => 'Organization Details',
        'document_upload' => 'Document Upload',
        'admin_user' => 'Admin User Creation',
        'verification' => 'Email Verification',
        'completion' => 'Registration Complete',
    ];

    /**
     * Get the organization that owns the registration step.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user that owns the registration step.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark the step as completed.
     */
    public function complete(array $data = []): void
    {
        $this->update([
            'step_data' => array_merge($this->step_data ?? [], $data),
            'is_completed' => true,
            'completed_at' => now()
        ]);
    }

    /**
     * Mark the step as incomplete.
     */
    public function markIncomplete(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null
        ]);
    }

    /**
     * Update step data without completing the step.
     */
    public function updateData(array $data): void
    {
        $this->update([
            'step_data' => array_merge($this->step_data ?? [], $data)
        ]);
    }

    /**
     * Get the step name label.
     */
    public function getStepNameLabelAttribute(): string
    {
        return self::STEPS[$this->step_name] ?? ucfirst(str_replace('_', ' ', $this->step_name));
    }

    /**
     * Get a specific data value from step_data.
     */
    public function getStepDataValue(string $key, $default = null)
    {
        return data_get($this->step_data, $key, $default);
    }

    /**
     * Check if step data has a specific key.
     */
    public function hasStepDataKey(string $key): bool
    {
        return array_key_exists($key, $this->step_data ?? []);
    }

    /**
     * Get the completion percentage for this step.
     */
    public function getCompletionPercentageAttribute(): int
    {
        if ($this->is_completed) {
            return 100;
        }

        // Calculate based on step data completeness
        $requiredFields = $this->getRequiredFieldsForStep();
        if (empty($requiredFields)) {
            return $this->is_completed ? 100 : 0;
        }

        $completedFields = 0;
        foreach ($requiredFields as $field) {
            if ($this->hasStepDataKey($field) && !empty($this->getStepDataValue($field))) {
                $completedFields++;
            }
        }

        return round(($completedFields / count($requiredFields)) * 100);
    }

    /**
     * Get required fields for each step.
     */
    protected function getRequiredFieldsForStep(): array
    {
        return match ($this->step_name) {
            'organization_details' => [
                'name', 'about', 'address', 'country_id', 'city_id', 
                'phone_number', 'organization_type_id', 'date_of_establishment'
            ],
            'document_upload' => ['registration_document', 'organization_sector'],
            'admin_user' => [
                'first_name', 'last_name', 'email', 'password', 
                'gender', 'date_of_birth', 'preferred_language'
            ],
            'verification' => ['email_verified'],
            default => [],
        };
    }

    /**
     * Scope to get completed steps.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope to get pending steps.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope to get steps by name.
     */
    public function scopeByStepName(Builder $query, string $stepName): Builder
    {
        return $query->where('step_name', $stepName);
    }

    /**
     * Scope to get steps by session ID.
     */
    public function scopeBySession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to order by step completion order.
     */
    public function scopeOrderByStepOrder(Builder $query): Builder
    {
        $stepOrder = array_keys(self::STEPS);
        $orderCases = [];
        
        foreach ($stepOrder as $index => $step) {
            $orderCases[] = "WHEN step_name = '{$step}' THEN {$index}";
        }
        
        $orderBy = 'CASE ' . implode(' ', $orderCases) . ' ELSE 999 END';
        
        return $query->orderByRaw($orderBy);
    }

    /**
     * Get the next step in the registration process.
     */
    public function getNextStep(): ?string
    {
        $steps = array_keys(self::STEPS);
        $currentIndex = array_search($this->step_name, $steps);
        
        if ($currentIndex !== false && isset($steps[$currentIndex + 1])) {
            return $steps[$currentIndex + 1];
        }
        
        return null;
    }

    /**
     * Get the previous step in the registration process.
     */
    public function getPreviousStep(): ?string
    {
        $steps = array_keys(self::STEPS);
        $currentIndex = array_search($this->step_name, $steps);
        
        if ($currentIndex !== false && $currentIndex > 0) {
            return $steps[$currentIndex - 1];
        }
        
        return null;
    }

    /**
     * Check if this is the first step.
     */
    public function isFirstStep(): bool
    {
        return $this->step_name === array_key_first(self::STEPS);
    }

    /**
     * Check if this is the last step.
     */
    public function isLastStep(): bool
    {
        return $this->step_name === array_key_last(self::STEPS);
    }

    /**
     * Get step progress for display.
     */
    public function getStepProgressAttribute(): array
    {
        $steps = array_keys(self::STEPS);
        $currentIndex = array_search($this->step_name, $steps);
        $totalSteps = count($steps);
        
        return [
            'current_step' => $currentIndex + 1,
            'total_steps' => $totalSteps,
            'percentage' => round((($currentIndex + 1) / $totalSteps) * 100),
            'step_name' => $this->step_name,
            'step_label' => $this->step_name_label,
        ];
    }
}
