<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'organization_id',
        'feedback_type',
        'template_type',
        'rating_categories',
        'questions',
        'tags',
        'settings',
        'is_active',
        'is_default',
        'usage_count',
        'created_by',
    ];

    protected $casts = [
        'rating_categories' => 'array',
        'questions' => 'array',
        'tags' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Get the organization this template belongs to
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get feedback using this template
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(VolunteerFeedback::class, 'template_id');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for default templates
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for specific feedback type
     */
    public function scopeFeedbackType($query, string $type)
    {
        return $query->where('feedback_type', $type);
    }

    /**
     * Scope for specific template type
     */
    public function scopeTemplateType($query, string $type)
    {
        return $query->where('template_type', $type);
    }

    /**
     * Scope for organization templates
     */
    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope for global templates (no organization)
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('organization_id');
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Set as default template for feedback type
     */
    public function setAsDefault(): void
    {
        // Remove default status from other templates of same type
        static::where('feedback_type', $this->feedback_type)
            ->where('organization_id', $this->organization_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get feedback type display name
     */
    public function getFeedbackTypeDisplayAttribute(): string
    {
        return match ($this->feedback_type) {
            'volunteer_to_organization' => 'Volunteer to Organization',
            'organization_to_volunteer' => 'Organization to Volunteer',
            'supervisor_to_volunteer' => 'Supervisor to Volunteer',
            'volunteer_to_supervisor' => 'Volunteer to Supervisor',
            'beneficiary_to_volunteer' => 'Beneficiary to Volunteer',
            default => ucwords(str_replace('_', ' ', $this->feedback_type)),
        };
    }

    /**
     * Get template type display name
     */
    public function getTemplateTypeDisplayAttribute(): string
    {
        return match ($this->template_type) {
            'rating_form' => 'Rating Form',
            'questionnaire' => 'Questionnaire',
            'open_feedback' => 'Open Feedback',
            default => ucwords(str_replace('_', ' ', $this->template_type)),
        };
    }

    /**
     * Get default rating categories
     */
    public static function getDefaultRatingCategories(): array
    {
        return [
            'overall' => [
                'label' => 'Overall Experience',
                'description' => 'Overall satisfaction with the experience',
                'required' => true,
            ],
            'communication' => [
                'label' => 'Communication',
                'description' => 'Quality of communication and responsiveness',
                'required' => false,
            ],
            'reliability' => [
                'label' => 'Reliability',
                'description' => 'Punctuality and dependability',
                'required' => false,
            ],
            'skill' => [
                'label' => 'Skills & Competence',
                'description' => 'Technical skills and competence level',
                'required' => false,
            ],
            'attitude' => [
                'label' => 'Attitude & Behavior',
                'description' => 'Professional attitude and behavior',
                'required' => false,
            ],
            'impact' => [
                'label' => 'Impact & Contribution',
                'description' => 'Positive impact and contribution made',
                'required' => false,
            ],
        ];
    }

    /**
     * Get default questions for feedback type
     */
    public static function getDefaultQuestions(string $feedbackType): array
    {
        return match ($feedbackType) {
            'volunteer_to_organization' => [
                [
                    'id' => 'organization_support',
                    'question' => 'How well did the organization support you during your volunteer experience?',
                    'type' => 'rating',
                    'required' => true,
                ],
                [
                    'id' => 'training_quality',
                    'question' => 'How would you rate the quality of training provided?',
                    'type' => 'rating',
                    'required' => false,
                ],
                [
                    'id' => 'meaningful_work',
                    'question' => 'Did you find the volunteer work meaningful and impactful?',
                    'type' => 'rating',
                    'required' => true,
                ],
                [
                    'id' => 'improvements',
                    'question' => 'What improvements would you suggest for future volunteers?',
                    'type' => 'text',
                    'required' => false,
                ],
            ],
            'organization_to_volunteer' => [
                [
                    'id' => 'volunteer_dedication',
                    'question' => 'How dedicated was the volunteer to their assigned tasks?',
                    'type' => 'rating',
                    'required' => true,
                ],
                [
                    'id' => 'volunteer_skills',
                    'question' => 'How would you rate the volunteer\'s relevant skills?',
                    'type' => 'rating',
                    'required' => true,
                ],
                [
                    'id' => 'team_collaboration',
                    'question' => 'How well did the volunteer collaborate with the team?',
                    'type' => 'rating',
                    'required' => false,
                ],
                [
                    'id' => 'recommend',
                    'question' => 'Would you recommend this volunteer for future opportunities?',
                    'type' => 'boolean',
                    'required' => true,
                ],
            ],
            'supervisor_to_volunteer' => [
                [
                    'id' => 'task_completion',
                    'question' => 'How effectively did the volunteer complete assigned tasks?',
                    'type' => 'rating',
                    'required' => true,
                ],
                [
                    'id' => 'initiative',
                    'question' => 'How well did the volunteer show initiative and proactivity?',
                    'type' => 'rating',
                    'required' => false,
                ],
                [
                    'id' => 'learning_attitude',
                    'question' => 'How would you rate the volunteer\'s willingness to learn?',
                    'type' => 'rating',
                    'required' => false,
                ],
            ],
            'beneficiary_to_volunteer' => [
                [
                    'id' => 'helpfulness',
                    'question' => 'How helpful was the volunteer in addressing your needs?',
                    'type' => 'rating',
                    'required' => true,
                ],
                [
                    'id' => 'respectfulness',
                    'question' => 'How respectful and understanding was the volunteer?',
                    'type' => 'rating',
                    'required' => true,
                ],
                [
                    'id' => 'impact',
                    'question' => 'What positive impact did the volunteer have on you?',
                    'type' => 'text',
                    'required' => false,
                ],
            ],
            default => [],
        };
    }

    /**
     * Get default settings
     */
    public static function getDefaultSettings(): array
    {
        return [
            'allow_anonymous' => true,
            'require_written_feedback' => false,
            'require_ratings' => true,
            'allow_public_sharing' => false,
            'enable_response' => true,
            'auto_publish' => false,
            'notification_settings' => [
                'notify_on_submission' => true,
                'notify_on_response' => true,
            ],
        ];
    }

    /**
     * Create default templates for an organization
     */
    public static function createDefaultTemplates(Organization $organization, User $creator): void
    {
        $feedbackTypes = [
            'volunteer_to_organization',
            'organization_to_volunteer',
            'supervisor_to_volunteer',
            'beneficiary_to_volunteer',
        ];

        foreach ($feedbackTypes as $feedbackType) {
            static::create([
                'name' => 'Default ' . ucwords(str_replace('_', ' ', $feedbackType)) . ' Template',
                'description' => 'Default template for ' . str_replace('_', ' ', $feedbackType) . ' feedback',
                'organization_id' => $organization->id,
                'feedback_type' => $feedbackType,
                'template_type' => 'rating_form',
                'rating_categories' => static::getDefaultRatingCategories(),
                'questions' => static::getDefaultQuestions($feedbackType),
                'tags' => VolunteerFeedback::getAvailableTags($feedbackType),
                'settings' => static::getDefaultSettings(),
                'is_active' => true,
                'is_default' => true,
                'created_by' => $creator->id,
            ]);
        }
    }

    /**
     * Validate template configuration
     */
    public function validateConfiguration(): array
    {
        $errors = [];

        // Check rating categories
        if (empty($this->rating_categories)) {
            $errors[] = 'At least one rating category is required';
        }

        // Check questions for questionnaire type
        if ($this->template_type === 'questionnaire' && empty($this->questions)) {
            $errors[] = 'Questions are required for questionnaire templates';
        }

        // Check settings
        if (empty($this->settings)) {
            $errors[] = 'Template settings are required';
        }

        return $errors;
    }

    /**
     * Check if template is valid
     */
    public function isValid(): bool
    {
        return empty($this->validateConfiguration());
    }

    /**
     * Get template preview data
     */
    public function getPreviewData(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'feedback_type' => $this->feedback_type_display,
            'template_type' => $this->template_type_display,
            'rating_categories' => $this->rating_categories,
            'questions' => $this->questions,
            'available_tags' => $this->tags,
            'settings' => $this->settings,
            'usage_count' => $this->usage_count,
            'is_default' => $this->is_default,
        ];
    }
}