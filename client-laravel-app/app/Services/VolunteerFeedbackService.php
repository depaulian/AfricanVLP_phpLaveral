<?php

namespace App\Services;

use App\Models\VolunteerFeedback;
use App\Models\FeedbackTemplate;
use App\Models\FeedbackAnalytics;
use App\Models\VolunteerAssignment;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class VolunteerFeedbackService
{
    /**
     * Create feedback from template
     */
    public function createFromTemplate(
        FeedbackTemplate $template,
        VolunteerAssignment $assignment,
        User $reviewer,
        User $reviewee,
        string $reviewerType
    ): VolunteerFeedback {
        $feedback = VolunteerFeedback::create([
            'assignment_id' => $assignment->id,
            'reviewer_id' => $reviewer->id,
            'reviewee_id' => $reviewee->id,
            'feedback_type' => $template->feedback_type,
            'reviewer_type' => $reviewerType,
            'status' => 'draft',
        ]);

        // Increment template usage
        $template->incrementUsage();

        return $feedback;
    }

    /**
     * Submit feedback with validation
     */
    public function submitFeedback(
        VolunteerFeedback $feedback,
        array $data,
        ?FeedbackTemplate $template = null
    ): VolunteerFeedback {
        // Validate required fields based on template
        if ($template) {
            $this->validateFeedbackData($data, $template);
        }

        // Update feedback with submitted data
        $feedback->update([
            'overall_rating' => $data['overall_rating'] ?? null,
            'communication_rating' => $data['communication_rating'] ?? null,
            'reliability_rating' => $data['reliability_rating'] ?? null,
            'skill_rating' => $data['skill_rating'] ?? null,
            'attitude_rating' => $data['attitude_rating'] ?? null,
            'impact_rating' => $data['impact_rating'] ?? null,
            'positive_feedback' => $data['positive_feedback'] ?? null,
            'improvement_feedback' => $data['improvement_feedback'] ?? null,
            'additional_comments' => $data['additional_comments'] ?? null,
            'structured_ratings' => $data['structured_ratings'] ?? null,
            'tags' => $data['tags'] ?? null,
            'is_anonymous' => $data['is_anonymous'] ?? false,
            'is_public' => $data['is_public'] ?? false,
        ]);

        // Submit the feedback
        $feedback->submit();

        // Send notifications
        $this->sendFeedbackNotifications($feedback);

        return $feedback;
    }

    /**
     * Validate feedback data against template
     */
    private function validateFeedbackData(array $data, FeedbackTemplate $template): void
    {
        $settings = $template->settings;
        $errors = [];

        // Check if ratings are required
        if ($settings['require_ratings'] ?? false) {
            if (empty($data['overall_rating'])) {
                $errors[] = 'Overall rating is required';
            }
        }

        // Check if written feedback is required
        if ($settings['require_written_feedback'] ?? false) {
            if (empty($data['positive_feedback']) && empty($data['improvement_feedback']) && empty($data['additional_comments'])) {
                $errors[] = 'Written feedback is required';
            }
        }

        // Validate structured ratings if present
        if (!empty($data['structured_ratings'])) {
            foreach ($template->rating_categories as $categoryKey => $category) {
                if (($category['required'] ?? false) && empty($data['structured_ratings'][$categoryKey])) {
                    $errors[] = "Rating for {$category['label']} is required";
                }
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
    }

    /**
     * Send feedback notifications
     */
    private function sendFeedbackNotifications(VolunteerFeedback $feedback): void
    {
        // Notify reviewee about new feedback
        if ($feedback->reviewee) {
            // Send notification to reviewee
            // This would integrate with your notification system
        }

        // Notify organization admins if needed
        if ($feedback->assignment && $feedback->assignment->organization) {
            // Send notification to organization admins
        }

        // Notify supervisors if applicable
        if ($feedback->feedback_type === 'volunteer_to_supervisor') {
            // Send notification to supervisor
        }
    }

    /**
     * Get feedback for user
     */
    public function getFeedbackForUser(
        User $user,
        string $type = 'received',
        array $filters = []
    ): LengthAwarePaginator {
        $query = VolunteerFeedback::query()->submitted();

        if ($type === 'received') {
            $query->where('reviewee_id', $user->id);
        } else {
            $query->where('reviewer_id', $user->id);
        }

        // Apply filters
        if (!empty($filters['feedback_type'])) {
            $query->where('feedback_type', $filters['feedback_type']);
        }

        if (!empty($filters['rating_min'])) {
            $query->where('overall_rating', '>=', $filters['rating_min']);
        }

        if (!empty($filters['rating_max'])) {
            $query->where('overall_rating', '<=', $filters['rating_max']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('submitted_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('submitted_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['has_response'])) {
            if ($filters['has_response']) {
                $query->whereNotNull('response');
            } else {
                $query->whereNull('response');
            }
        }

        return $query->with(['reviewer', 'reviewee', 'assignment'])
                    ->orderBy('submitted_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get feedback for organization
     */
    public function getFeedbackForOrganization(
        Organization $organization,
        array $filters = []
    ): LengthAwarePaginator {
        $query = VolunteerFeedback::query()
            ->submitted()
            ->whereHas('assignment', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            });

        // Apply filters
        if (!empty($filters['feedback_type'])) {
            $query->where('feedback_type', $filters['feedback_type']);
        }

        if (!empty($filters['reviewer_type'])) {
            $query->where('reviewer_type', $filters['reviewer_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['rating_min'])) {
            $query->where('overall_rating', '>=', $filters['rating_min']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('submitted_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('submitted_at', '<=', $filters['date_to']);
        }

        return $query->with(['reviewer', 'reviewee', 'assignment'])
                    ->orderBy('submitted_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get public feedback
     */
    public function getPublicFeedback(array $filters = []): LengthAwarePaginator
    {
        $query = VolunteerFeedback::query()->public();

        // Apply filters
        if (!empty($filters['feedback_type'])) {
            $query->where('feedback_type', $filters['feedback_type']);
        }

        if (!empty($filters['rating_min'])) {
            $query->where('overall_rating', '>=', $filters['rating_min']);
        }

        if (!empty($filters['organization_id'])) {
            $query->whereHas('assignment', function ($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        return $query->with(['reviewee', 'assignment.organization'])
                    ->orderBy('submitted_at', 'desc')
                    ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Add response to feedback
     */
    public function addResponse(VolunteerFeedback $feedback, string $response, User $responder): VolunteerFeedback
    {
        // Check if user can respond to this feedback
        if ($feedback->reviewee_id !== $responder->id) {
            throw new \UnauthorizedHttpException('You can only respond to feedback about yourself');
        }

        $feedback->addResponse($response);

        // Send notification about response
        if ($feedback->reviewer) {
            // Send notification to original reviewer
        }

        return $feedback;
    }

    /**
     * Request follow-up for feedback
     */
    public function requestFollowUp(
        VolunteerFeedback $feedback,
        User $requester,
        ?Carbon $scheduledAt = null
    ): VolunteerFeedback {
        // Check permissions
        if (!$this->canRequestFollowUp($feedback, $requester)) {
            throw new \UnauthorizedHttpException('You cannot request follow-up for this feedback');
        }

        $feedback->requestFollowUp($scheduledAt);

        // Send notification about follow-up request
        $this->sendFollowUpNotification($feedback);

        return $feedback;
    }

    /**
     * Check if user can request follow-up
     */
    private function canRequestFollowUp(VolunteerFeedback $feedback, User $user): bool
    {
        // Reviewee can request follow-up
        if ($feedback->reviewee_id === $user->id) {
            return true;
        }

        // Organization admins can request follow-up
        if ($feedback->assignment && $feedback->assignment->organization) {
            return $feedback->assignment->organization->users()
                ->where('user_id', $user->id)
                ->where('role', 'admin')
                ->exists();
        }

        return false;
    }

    /**
     * Send follow-up notification
     */
    private function sendFollowUpNotification(VolunteerFeedback $feedback): void
    {
        // Send notification to reviewer about follow-up request
        if ($feedback->reviewer) {
            // Send notification
        }
    }

    /**
     * Get feedback statistics for user
     */
    public function getUserFeedbackStats(User $user): array
    {
        return VolunteerFeedback::getUserStats($user);
    }

    /**
     * Get feedback statistics for organization
     */
    public function getOrganizationFeedbackStats(Organization $organization): array
    {
        return VolunteerFeedback::getOrganizationStats($organization);
    }

    /**
     * Generate feedback report
     */
    public function generateFeedbackReport(
        ?Organization $organization = null,
        array $filters = []
    ): array {
        $query = VolunteerFeedback::query()->submitted();

        if ($organization) {
            $query->whereHas('assignment', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            });
        }

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->where('submitted_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('submitted_at', '<=', $filters['date_to']);
        }

        $feedback = $query->get();

        return [
            'summary' => [
                'total_feedback' => $feedback->count(),
                'average_rating' => $feedback->whereNotNull('overall_rating')->avg('overall_rating'),
                'response_rate' => $feedback->whereNotNull('response')->count() / max($feedback->count(), 1) * 100,
                'public_feedback_count' => $feedback->where('is_public', true)->count(),
            ],
            'by_type' => $feedback->groupBy('feedback_type')->map(function ($items, $type) {
                return [
                    'count' => $items->count(),
                    'average_rating' => $items->whereNotNull('overall_rating')->avg('overall_rating'),
                ];
            }),
            'by_rating' => $feedback->whereNotNull('overall_rating')
                ->groupBy(function ($item) {
                    return floor($item->overall_rating);
                })
                ->map->count(),
            'top_tags' => $this->getTopTags($feedback),
            'improvement_areas' => $this->getImprovementAreas($feedback),
        ];
    }

    /**
     * Get top tags from feedback
     */
    private function getTopTags(Collection $feedback): array
    {
        $tagCounts = [];

        foreach ($feedback as $item) {
            if (is_array($item->tags)) {
                foreach ($item->tags as $tag) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }

        arsort($tagCounts);
        return array_slice($tagCounts, 0, 10, true);
    }

    /**
     * Get improvement areas from feedback
     */
    private function getImprovementAreas(Collection $feedback): array
    {
        $improvementFeedback = $feedback->whereNotNull('improvement_feedback');
        
        // This is a simplified version - in practice, you might use NLP
        // to extract common themes from improvement feedback
        $commonWords = [];
        
        foreach ($improvementFeedback as $item) {
            $words = str_word_count(strtolower($item->improvement_feedback), 1);
            foreach ($words as $word) {
                if (strlen($word) > 4) { // Only consider words longer than 4 characters
                    $commonWords[$word] = ($commonWords[$word] ?? 0) + 1;
                }
            }
        }

        arsort($commonWords);
        return array_slice($commonWords, 0, 10, true);
    }

    /**
     * Calculate analytics for period
     */
    public function calculateAnalytics(
        ?Organization $organization,
        string $periodType = 'monthly',
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): void {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfMonth();

        $feedbackTypes = ['all', 'volunteer_to_organization', 'organization_to_volunteer', 'supervisor_to_volunteer', 'beneficiary_to_volunteer'];

        foreach ($feedbackTypes as $feedbackType) {
            FeedbackAnalytics::calculateAllAnalytics(
                $organization,
                $feedbackType,
                $periodType,
                $startDate,
                $endDate
            );
        }
    }

    /**
     * Get analytics dashboard data
     */
    public function getAnalyticsDashboard(?Organization $organization, int $days = 30): array
    {
        return FeedbackAnalytics::getDashboardSummary($organization, $days);
    }

    /**
     * Get feedback recommendations
     */
    public function getFeedbackRecommendations(?Organization $organization): array
    {
        $stats = $organization 
            ? $this->getOrganizationFeedbackStats($organization)
            : [];

        $recommendations = [];

        // Low response rate recommendation
        if (($stats['response_rate'] ?? 0) < 50) {
            $recommendations[] = [
                'type' => 'response_rate',
                'priority' => 'high',
                'title' => 'Improve Response Rate',
                'description' => 'Your feedback response rate is below 50%. Consider following up with feedback recipients.',
                'actions' => [
                    'Send reminder notifications',
                    'Simplify feedback forms',
                    'Provide incentives for responses',
                ],
            ];
        }

        // Low average rating recommendation
        if (($stats['average_rating'] ?? 5) < 3.5) {
            $recommendations[] = [
                'type' => 'rating',
                'priority' => 'high',
                'title' => 'Address Low Ratings',
                'description' => 'Your average rating is below 3.5. Review feedback for improvement areas.',
                'actions' => [
                    'Analyze improvement feedback',
                    'Implement training programs',
                    'Review volunteer matching process',
                ],
            ];
        }

        // Low feedback volume recommendation
        if (($stats['total_feedback'] ?? 0) < 10) {
            $recommendations[] = [
                'type' => 'volume',
                'priority' => 'medium',
                'title' => 'Increase Feedback Collection',
                'description' => 'You have limited feedback data. Encourage more feedback collection.',
                'actions' => [
                    'Make feedback forms more accessible',
                    'Send automated feedback requests',
                    'Educate volunteers about feedback importance',
                ],
            ];
        }

        return $recommendations;
    }
}