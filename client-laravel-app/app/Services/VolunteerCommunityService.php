<?php

namespace App\Services;

use App\Models\User;
use App\Models\VolunteerConnection;
use App\Models\VolunteerMentorship;
use App\Models\CommunityEvent;
use App\Models\EventRegistration;
use App\Models\VolunteerResource;
use App\Models\VolunteerAward;
use App\Models\UserAward;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

class VolunteerCommunityService
{
    /**
     * Get community dashboard data for a user
     */
    public function getCommunityDashboard(User $user): array
    {
        return [
            'connections' => $this->getConnectionsSummary($user),
            'mentorships' => $this->getMentorshipsSummary($user),
            'events' => $this->getEventsSummary($user),
            'resources' => $this->getResourcesSummary($user),
            'awards' => $this->getAwardsSummary($user),
            'activity_feed' => $this->getActivityFeed($user),
            'recommendations' => $this->getRecommendations($user),
        ];
    }

    /**
     * Get connections summary for user
     */
    public function getConnectionsSummary(User $user): array
    {
        $stats = VolunteerConnection::getUserConnectionStats($user);
        $recentConnections = VolunteerConnection::forUser($user->id)
            ->accepted()
            ->with(['requester', 'recipient'])
            ->orderByDesc('connected_at')
            ->limit(5)
            ->get();

        return [
            'stats' => $stats,
            'recent_connections' => $recentConnections,
            'suggested_connections' => VolunteerConnection::getSuggestedConnections($user, 3),
            'pending_requests' => VolunteerConnection::where('recipient_id', $user->id)
                ->pending()
                ->with('requester')
                ->count(),
        ];
    }

    /**
     * Get mentorships summary for user
     */
    public function getMentorshipsSummary(User $user): array
    {
        $stats = VolunteerMentorship::getUserMentorshipStats($user);
        $activeMentorships = VolunteerMentorship::forUser($user->id)
            ->active()
            ->with(['mentor', 'mentee'])
            ->get();

        return [
            'stats' => $stats,
            'active_mentorships' => $activeMentorships,
            'potential_mentors' => VolunteerMentorship::findPotentialMentors($user, 3),
            'overdue_meetings' => $activeMentorships->filter(fn($m) => $m->isMeetingOverdue()),
        ];
    }

    /**
     * Get events summary for user
     */
    public function getEventsSummary(User $user): array
    {
        $upcomingEvents = EventRegistration::where('user_id', $user->id)
            ->whereIn('status', ['registered', 'approved'])
            ->whereHas('event', function ($query) {
                $query->where('start_datetime', '>', now());
            })
            ->with('event')
            ->orderBy('event.start_datetime')
            ->limit(3)
            ->get();

        $eventHistory = EventRegistration::getUserEventHistory($user);

        return [
            'upcoming_events' => $upcomingEvents,
            'event_history' => $eventHistory,
            'recommended_events' => $this->getRecommendedEvents($user, 3),
        ];
    }

    /**
     * Get resources summary for user
     */
    public function getResourcesSummary(User $user): array
    {
        $contributedResources = VolunteerResource::where('contributor_id', $user->id)
            ->approved()
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $bookmarkedResources = VolunteerResource::whereHas('interactions', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('interaction_type', 'bookmark');
            })
            ->approved()
            ->public()
            ->limit(3)
            ->get();

        return [
            'contributed_resources' => $contributedResources,
            'bookmarked_resources' => $bookmarkedResources,
            'recommended_resources' => $this->getRecommendedResources($user, 3),
            'contribution_stats' => [
                'total_contributed' => VolunteerResource::where('contributor_id', $user->id)->approved()->count(),
                'total_downloads' => VolunteerResource::where('contributor_id', $user->id)->sum('download_count'),
                'average_rating' => VolunteerResource::where('contributor_id', $user->id)->avg('average_rating'),
            ],
        ];
    }

    /**
     * Get awards summary for user
     */
    public function getAwardsSummary(User $user): array
    {
        return UserAward::getUserAwardsSummary($user);
    }

    /**
     * Get activity feed for user
     */
    public function getActivityFeed(User $user, int $limit = 10): array
    {
        $activities = [];

        // Recent connections
        $recentConnections = VolunteerConnection::forUser($user->id)
            ->accepted()
            ->with(['requester', 'recipient'])
            ->where('connected_at', '>=', now()->subWeeks(2))
            ->get();

        foreach ($recentConnections as $connection) {
            $otherUser = $connection->getOtherUser($user);
            $activities[] = [
                'type' => 'connection',
                'title' => 'Connected with ' . $otherUser->name,
                'description' => 'You are now connected with ' . $otherUser->name,
                'timestamp' => $connection->connected_at,
                'user' => $otherUser,
                'icon' => 'users',
                'color' => 'blue',
            ];
        }

        // Recent awards
        $recentAwards = UserAward::forUser($user->id)
            ->active()
            ->with('award')
            ->where('earned_date', '>=', now()->subWeeks(2))
            ->get();

        foreach ($recentAwards as $userAward) {
            $activities[] = [
                'type' => 'award',
                'title' => 'Earned ' . $userAward->award->name,
                'description' => $userAward->award->description,
                'timestamp' => $userAward->earned_date,
                'award' => $userAward->award,
                'icon' => 'trophy',
                'color' => 'yellow',
            ];
        }

        // Recent event registrations
        $recentRegistrations = EventRegistration::where('user_id', $user->id)
            ->with('event')
            ->where('registered_at', '>=', now()->subWeeks(2))
            ->get();

        foreach ($recentRegistrations as $registration) {
            $activities[] = [
                'type' => 'event_registration',
                'title' => 'Registered for ' . $registration->event->title,
                'description' => 'Event on ' . $registration->event->start_datetime->format('M d, Y'),
                'timestamp' => $registration->registered_at,
                'event' => $registration->event,
                'icon' => 'calendar',
                'color' => 'green',
            ];
        }

        // Recent resource contributions
        $recentResources = VolunteerResource::where('contributor_id', $user->id)
            ->approved()
            ->where('created_at', '>=', now()->subWeeks(2))
            ->get();

        foreach ($recentResources as $resource) {
            $activities[] = [
                'type' => 'resource_contribution',
                'title' => 'Contributed ' . $resource->title,
                'description' => 'Shared a new ' . $resource->type_display,
                'timestamp' => $resource->created_at,
                'resource' => $resource,
                'icon' => 'document',
                'color' => 'purple',
            ];
        }

        // Sort by timestamp and limit
        usort($activities, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($activities, 0, $limit);
    }

    /**
     * Get recommendations for user
     */
    public function getRecommendations(User $user): array
    {
        return [
            'connections' => VolunteerConnection::getSuggestedConnections($user, 5),
            'mentors' => VolunteerMentorship::findPotentialMentors($user, 5),
            'events' => $this->getRecommendedEvents($user, 5),
            'resources' => $this->getRecommendedResources($user, 5),
        ];
    }

    /**
     * Get recommended events for user
     */
    public function getRecommendedEvents(User $user, int $limit = 5): Collection
    {
        $userInterests = $user->volunteeringInterests()->pluck('category_id')->toArray();
        $userLocation = $user->city;

        $query = CommunityEvent::published()
            ->upcoming()
            ->public();

        // Prioritize events based on user interests and location
        if (!empty($userInterests)) {
            $query->where(function ($q) use ($userInterests, $userLocation) {
                // Events in same location
                if ($userLocation) {
                    $q->where('city', 'like', "%{$userLocation}%");
                }
                
                // Events matching interests (through tags or target audience)
                foreach ($userInterests as $interest) {
                    $q->orWhereJsonContains('target_audience', $interest);
                }
            });
        }

        // Exclude events user is already registered for
        $registeredEventIds = EventRegistration::where('user_id', $user->id)
            ->pluck('event_id')
            ->toArray();

        if (!empty($registeredEventIds)) {
            $query->whereNotIn('id', $registeredEventIds);
        }

        return $query->orderByDesc('is_featured')
            ->orderBy('start_datetime')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recommended resources for user
     */
    public function getRecommendedResources(User $user, int $limit = 5): Collection
    {
        $userInterests = $user->volunteeringInterests()->pluck('category_id')->toArray();
        $userSkills = $user->skills()->pluck('name')->toArray();

        $query = VolunteerResource::approved()
            ->public();

        // Prioritize resources based on user interests and skills
        if (!empty($userInterests) || !empty($userSkills)) {
            $query->where(function ($q) use ($userInterests, $userSkills) {
                // Resources matching interests
                foreach ($userInterests as $interest) {
                    $q->orWhereJsonContains('categories', $interest);
                }
                
                // Resources matching skills
                foreach ($userSkills as $skill) {
                    $q->orWhereJsonContains('tags', $skill);
                }
            });
        }

        // Exclude resources user has already interacted with
        $interactedResourceIds = $user->resourceInteractions()
            ->pluck('resource_id')
            ->toArray();

        if (!empty($interactedResourceIds)) {
            $query->whereNotIn('id', $interactedResourceIds);
        }

        return $query->orderByDesc('is_featured')
            ->orderByDesc('popularity_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Create a volunteer connection request
     */
    public function createConnectionRequest(User $requester, User $recipient, string $message = null, array $reasons = []): VolunteerConnection
    {
        return VolunteerConnection::createRequest($requester, $recipient, $message, $reasons);
    }

    /**
     * Create a mentorship request
     */
    public function createMentorshipRequest(
        User $mentor,
        User $mentee,
        array $goals,
        array $focusAreas = [],
        string $meetingFrequency = 'monthly',
        string $communicationPreference = 'mixed',
        int $durationMonths = 6
    ): VolunteerMentorship {
        return VolunteerMentorship::createRequest(
            $mentor,
            $mentee,
            $goals,
            $focusAreas,
            $meetingFrequency,
            $communicationPreference,
            $durationMonths
        );
    }

    /**
     * Register user for an event
     */
    public function registerForEvent(User $user, CommunityEvent $event, array $data = []): EventRegistration
    {
        return $event->registerUser($user, $data);
    }

    /**
     * Process automatic awards for user
     */
    public function processAutomaticAwards(User $user): array
    {
        $awarded = [];
        $automaticAwards = VolunteerAward::automatic()->active()->valid()->get();

        foreach ($automaticAwards as $award) {
            if ($award->canUserReceive($user) && $award->checkUserCriteria($user)) {
                try {
                    $userAward = $award->awardToUser($user, null, 'Automatically awarded based on criteria');
                    $awarded[] = $userAward;
                    
                    // TODO: Send notification to user
                } catch (\Exception $e) {
                    \Log::error("Failed to award {$award->name} to user {$user->id}: " . $e->getMessage());
                }
            }
        }

        return $awarded;
    }

    /**
     * Get community statistics
     */
    public function getCommunityStatistics(): array
    {
        return [
            'connections' => [
                'total_connections' => VolunteerConnection::accepted()->count(),
                'new_this_month' => VolunteerConnection::accepted()
                    ->where('connected_at', '>=', now()->startOfMonth())
                    ->count(),
                'most_connected_users' => $this->getMostConnectedUsers(5),
            ],
            'mentorships' => [
                'active_mentorships' => VolunteerMentorship::active()->count(),
                'completed_mentorships' => VolunteerMentorship::completed()->count(),
                'success_rate' => $this->getMentorshipSuccessRate(),
                'top_mentors' => $this->getTopMentors(5),
            ],
            'events' => [
                'upcoming_events' => CommunityEvent::published()->upcoming()->count(),
                'total_registrations' => EventRegistration::whereHas('event', function ($query) {
                    $query->where('start_datetime', '>', now());
                })->count(),
                'average_attendance_rate' => CommunityEvent::completed()->avg('attendance_rate'),
                'popular_event_types' => $this->getPopularEventTypes(),
            ],
            'resources' => [
                'total_resources' => VolunteerResource::approved()->public()->count(),
                'total_downloads' => VolunteerResource::sum('download_count'),
                'top_contributors' => $this->getTopResourceContributors(5),
                'popular_resource_types' => $this->getPopularResourceTypes(),
            ],
            'awards' => [
                'total_awards_given' => UserAward::active()->count(),
                'unique_recipients' => UserAward::active()->distinct('user_id')->count(),
                'most_earned_awards' => $this->getMostEarnedAwards(5),
                'top_achievers' => UserAward::getLeaderboard(5),
            ],
        ];
    }

    /**
     * Get most connected users
     */
    private function getMostConnectedUsers(int $limit): array
    {
        return VolunteerConnection::accepted()
            ->selectRaw('
                CASE 
                    WHEN requester_id = ? THEN recipient_id 
                    ELSE requester_id 
                END as user_id,
                COUNT(*) as connection_count
            ', [auth()->id() ?? 0])
            ->groupBy('user_id')
            ->orderByDesc('connection_count')
            ->limit($limit)
            ->with('user')
            ->get()
            ->toArray();
    }

    /**
     * Get mentorship success rate
     */
    private function getMentorshipSuccessRate(): float
    {
        $total = VolunteerMentorship::whereIn('status', ['completed', 'cancelled'])->count();
        $completed = VolunteerMentorship::completed()->count();
        
        return $total > 0 ? ($completed / $total) * 100 : 0;
    }

    /**
     * Get top mentors
     */
    private function getTopMentors(int $limit): array
    {
        return VolunteerMentorship::completed()
            ->selectRaw('mentor_id, COUNT(*) as mentorships_completed, AVG(mentor_rating) as average_rating')
            ->groupBy('mentor_id')
            ->orderByDesc('mentorships_completed')
            ->orderByDesc('average_rating')
            ->limit($limit)
            ->with('mentor')
            ->get()
            ->toArray();
    }

    /**
     * Get popular event types
     */
    private function getPopularEventTypes(): array
    {
        return CommunityEvent::selectRaw('type, COUNT(*) as count')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('type')
            ->orderByDesc('count')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get top resource contributors
     */
    private function getTopResourceContributors(int $limit): array
    {
        return VolunteerResource::approved()
            ->selectRaw('contributor_id, COUNT(*) as resource_count, SUM(download_count) as total_downloads')
            ->groupBy('contributor_id')
            ->orderByDesc('resource_count')
            ->orderByDesc('total_downloads')
            ->limit($limit)
            ->with('contributor')
            ->get()
            ->toArray();
    }

    /**
     * Get popular resource types
     */
    private function getPopularResourceTypes(): array
    {
        return VolunteerResource::approved()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->orderByDesc('count')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get most earned awards
     */
    private function getMostEarnedAwards(int $limit): array
    {
        return UserAward::active()
            ->selectRaw('award_id, COUNT(*) as recipients_count')
            ->groupBy('award_id')
            ->orderByDesc('recipients_count')
            ->limit($limit)
            ->with('award')
            ->get()
            ->toArray();
    }

    /**
     * Search community members
     */
    public function searchCommunityMembers(array $filters = []): Builder
    {
        $query = User::whereHas('volunteerAssignments');

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('bio', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['skills'])) {
            $query->whereHas('skills', function ($q) use ($filters) {
                $q->whereIn('name', (array) $filters['skills']);
            });
        }

        if (!empty($filters['interests'])) {
            $query->whereHas('volunteeringInterests', function ($q) use ($filters) {
                $q->whereIn('category_id', (array) $filters['interests']);
            });
        }

        if (!empty($filters['location'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('city', 'like', "%{$filters['location']}%")
                  ->orWhere('country', 'like', "%{$filters['location']}%");
            });
        }

        if (!empty($filters['experience_level'])) {
            // Filter by number of completed assignments
            $level = $filters['experience_level'];
            $query->withCount(['volunteerAssignments as completed_assignments' => function ($q) {
                $q->where('status', 'completed');
            }]);

            switch ($level) {
                case 'beginner':
                    $query->having('completed_assignments', '<=', 5);
                    break;
                case 'intermediate':
                    $query->having('completed_assignments', '>', 5)
                          ->having('completed_assignments', '<=', 20);
                    break;
                case 'advanced':
                    $query->having('completed_assignments', '>', 20);
                    break;
            }
        }

        return $query->with(['skills', 'volunteeringInterests.category']);
    }
}