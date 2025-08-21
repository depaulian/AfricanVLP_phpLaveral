<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\VolunteerCommunityService;
use App\Models\User;
use App\Models\VolunteerConnection;
use App\Models\VolunteerMentorship;
use App\Models\CommunityEvent;
use App\Models\VolunteerResource;
use App\Models\VolunteerAward;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class VolunteerCommunityController extends Controller
{
    public function __construct(
        private VolunteerCommunityService $communityService
    ) {}

    /**
     * Display the community dashboard
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        $dashboardData = $this->communityService->getCommunityDashboard($user);
        $communityStats = $this->communityService->getCommunityStatistics();

        return view('client.volunteering.community.dashboard', compact('dashboardData', 'communityStats'));
    }

    /**
     * Display volunteer connections
     */
    public function connections(Request $request): View
    {
        $user = auth()->user();
        
        $connections = VolunteerConnection::forUser($user->id)
            ->accepted()
            ->with(['requester', 'recipient'])
            ->orderByDesc('connected_at')
            ->paginate(12);

        $pendingRequests = VolunteerConnection::where('recipient_id', $user->id)
            ->pending()
            ->with('requester')
            ->get();

        $sentRequests = VolunteerConnection::where('requester_id', $user->id)
            ->pending()
            ->with('recipient')
            ->get();

        $suggestedConnections = VolunteerConnection::getSuggestedConnections($user, 6);
        $connectionStats = VolunteerConnection::getUserConnectionStats($user);

        return view('client.volunteering.community.connections', compact(
            'connections',
            'pendingRequests',
            'sentRequests',
            'suggestedConnections',
            'connectionStats'
        ));
    }

    /**
     * Send connection request
     */
    public function sendConnectionRequest(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:500',
            'connection_reasons' => 'nullable|array',
        ]);

        $user = auth()->user();
        $recipient = User::findOrFail($request->recipient_id);

        try {
            $connection = $this->communityService->createConnectionRequest(
                $user,
                $recipient,
                $request->message,
                $request->connection_reasons ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Connection request sent successfully!',
                'connection' => $connection->load(['requester', 'recipient'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Respond to connection request
     */
    public function respondToConnectionRequest(Request $request, VolunteerConnection $connection): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:accept,decline,block',
        ]);

        if ($connection->recipient_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            switch ($request->action) {
                case 'accept':
                    $connection->accept();
                    $message = 'Connection request accepted!';
                    break;
                case 'decline':
                    $connection->decline();
                    $message = 'Connection request declined.';
                    break;
                case 'block':
                    $connection->block();
                    $message = 'User blocked.';
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'connection' => $connection->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display mentorships
     */
    public function mentorships(): View
    {
        $user = auth()->user();
        
        $asMentor = VolunteerMentorship::forMentor($user->id)
            ->with('mentee')
            ->orderByDesc('created_at')
            ->get();

        $asMentee = VolunteerMentorship::forMentee($user->id)
            ->with('mentor')
            ->orderByDesc('created_at')
            ->get();

        $potentialMentors = VolunteerMentorship::findPotentialMentors($user, 6);
        $mentorshipStats = VolunteerMentorship::getUserMentorshipStats($user);

        return view('client.volunteering.community.mentorships', compact(
            'asMentor',
            'asMentee',
            'potentialMentors',
            'mentorshipStats'
        ));
    }

    /**
     * Create mentorship request
     */
    public function createMentorshipRequest(Request $request): JsonResponse
    {
        $request->validate([
            'mentor_id' => 'required|exists:users,id',
            'goals' => 'required|array|min:1',
            'goals.*' => 'string|max:255',
            'focus_areas' => 'nullable|array',
            'meeting_frequency' => 'required|in:weekly,bi_weekly,monthly,as_needed',
            'communication_preference' => 'required|in:in_person,video_call,phone,messaging,mixed',
            'duration_months' => 'required|integer|min:1|max:24',
        ]);

        $user = auth()->user();
        $mentor = User::findOrFail($request->mentor_id);

        try {
            $mentorship = $this->communityService->createMentorshipRequest(
                $mentor,
                $user,
                $request->goals,
                $request->focus_areas ?? [],
                $request->meeting_frequency,
                $request->communication_preference,
                $request->duration_months
            );

            return response()->json([
                'success' => true,
                'message' => 'Mentorship request sent successfully!',
                'mentorship' => $mentorship->load(['mentor', 'mentee'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display community events
     */
    public function events(Request $request): View
    {
        $query = CommunityEvent::published()->public()->upcoming();

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('format')) {
            $query->where('format', $request->format);
        }

        if ($request->filled('location')) {
            $query->where(function ($q) use ($request) {
                $q->where('city', 'like', "%{$request->location}%")
                  ->orWhere('country', 'like', "%{$request->location}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('start_datetime', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('start_datetime', '<=', $request->date_to);
        }

        $events = $query->orderBy('start_datetime')->paginate(12);
        
        $featuredEvents = CommunityEvent::published()
            ->public()
            ->upcoming()
            ->featured()
            ->limit(3)
            ->get();

        $userRegistrations = auth()->user()->eventRegistrations()
            ->whereHas('event', function ($q) {
                $q->where('start_datetime', '>', now());
            })
            ->with('event')
            ->get();

        return view('client.volunteering.community.events', compact(
            'events',
            'featuredEvents',
            'userRegistrations'
        ));
    }

    /**
     * Show event details
     */
    public function showEvent(CommunityEvent $event): View
    {
        $event->load(['organizer', 'organization']);
        $event->incrementViews();

        $userRegistration = auth()->user()->eventRegistrations()
            ->where('event_id', $event->id)
            ->first();

        $similarEvents = $event->getSimilarEvents(4);
        $eventStats = $event->getStatistics();

        return view('client.volunteering.community.event-details', compact(
            'event',
            'userRegistration',
            'similarEvents',
            'eventStats'
        ));
    }

    /**
     * Register for event
     */
    public function registerForEvent(Request $request, CommunityEvent $event): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'custom_fields' => 'nullable|array',
        ]);

        try {
            $registration = $this->communityService->registerForEvent(
                auth()->user(),
                $event,
                $request->only(['notes', 'custom_fields'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Successfully registered for the event!',
                'registration' => $registration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display volunteer resources
     */
    public function resources(Request $request): View
    {
        $query = VolunteerResource::approved()->public();

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('difficulty_level')) {
            $query->where('difficulty_level', $request->difficulty_level);
        }

        if ($request->filled('category')) {
            $query->whereJsonContains('categories', $request->category);
        }

        if ($request->filled('search')) {
            $keyword = $request->search;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%")
                  ->orWhereJsonContains('tags', $keyword);
            });
        }

        $sortBy = $request->get('sort_by', 'popularity');
        switch ($sortBy) {
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'title':
                $query->orderBy('title');
                break;
            case 'rating':
                $query->orderByDesc('average_rating');
                break;
            case 'downloads':
                $query->orderByDesc('download_count');
                break;
            default:
                $query->orderByDesc('popularity_score');
                break;
        }

        $resources = $query->paginate(12);
        
        $featuredResources = VolunteerResource::approved()
            ->public()
            ->featured()
            ->limit(4)
            ->get();

        $userBookmarks = auth()->user()->resourceInteractions()
            ->where('interaction_type', 'bookmark')
            ->with('resource')
            ->get();

        return view('client.volunteering.community.resources', compact(
            'resources',
            'featuredResources',
            'userBookmarks'
        ));
    }

    /**
     * Show resource details
     */
    public function showResource(VolunteerResource $resource): View
    {
        if (!$resource->canUserAccess(auth()->user())) {
            abort(403, 'You do not have access to this resource.');
        }

        $resource->incrementViews();
        $resource->load(['contributor', 'organization']);

        $user = auth()->user();
        $userRating = $resource->getUserRating($user);
        $isLiked = $resource->isLikedBy($user);
        $isBookmarked = $resource->isBookmarkedBy($user);

        $similarResources = $resource->getSimilarResources(4);

        return view('client.volunteering.community.resource-details', compact(
            'resource',
            'userRating',
            'isLiked',
            'isBookmarked',
            'similarResources'
        ));
    }

    /**
     * Download resource
     */
    public function downloadResource(VolunteerResource $resource): JsonResponse
    {
        if (!$resource->canUserAccess(auth()->user())) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        if (!$resource->file_path) {
            return response()->json(['success' => false, 'message' => 'No file available'], 404);
        }

        $resource->incrementDownloads();

        return response()->json([
            'success' => true,
            'download_url' => $resource->file_url,
            'filename' => $resource->file_name
        ]);
    }

    /**
     * Toggle resource like
     */
    public function toggleResourceLike(VolunteerResource $resource): JsonResponse
    {
        $isLiked = $resource->toggleLike(auth()->user());

        return response()->json([
            'success' => true,
            'is_liked' => $isLiked,
            'like_count' => $resource->fresh()->like_count
        ]);
    }

    /**
     * Toggle resource bookmark
     */
    public function toggleResourceBookmark(VolunteerResource $resource): JsonResponse
    {
        $isBookmarked = $resource->toggleBookmark(auth()->user());

        return response()->json([
            'success' => true,
            'is_bookmarked' => $isBookmarked,
            'bookmark_count' => $resource->fresh()->bookmark_count
        ]);
    }

    /**
     * Rate resource
     */
    public function rateResource(Request $request, VolunteerResource $resource): JsonResponse
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $resource->addRating(auth()->user(), $request->rating, $request->comment);

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully!',
            'average_rating' => $resource->fresh()->average_rating,
            'rating_count' => $resource->fresh()->rating_count
        ]);
    }

    /**
     * Display awards and recognition
     */
    public function awards(): View
    {
        $user = auth()->user();
        $awardsSummary = $this->communityService->getAwardsSummary($user);
        
        $availableAwards = VolunteerAward::active()
            ->valid()
            ->whereDoesntHave('userAwards', function ($query) use ($user) {
                $query->where('user_id', $user->id)->where('status', 'active');
            })
            ->get();

        $leaderboard = UserAward::getLeaderboard(10);

        return view('client.volunteering.community.awards', compact(
            'awardsSummary',
            'availableAwards',
            'leaderboard'
        ));
    }

    /**
     * Search community members
     */
    public function searchMembers(Request $request): View
    {
        $query = $this->communityService->searchCommunityMembers($request->all());
        $members = $query->paginate(12);

        return view('client.volunteering.community.members', compact('members'));
    }

    /**
     * Get community statistics (API)
     */
    public function getStatistics(): JsonResponse
    {
        $stats = $this->communityService->getCommunityStatistics();
        return response()->json($stats);
    }

    /**
     * Get user's activity feed (API)
     */
    public function getActivityFeed(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);
        $activities = $this->communityService->getActivityFeed(auth()->user(), $limit);
        
        return response()->json($activities);
    }

    /**
     * Get recommendations for user (API)
     */
    public function getRecommendations(): JsonResponse
    {
        $recommendations = $this->communityService->getRecommendations(auth()->user());
        return response()->json($recommendations);
    }
}