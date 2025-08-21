<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Models\Event;
use App\Models\News;
use App\Models\VolunteeringOpportunity;
use App\Models\ForumThread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display the client dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's organizations
        $userOrganizations = $user->organizations()->where('status', 'active')->get();
        
        // Get recent news
        $recentNews = News::where('status', 'published')
                         ->latest('created')
                         ->take(5)
                         ->get();
        
        // Get upcoming events
        $upcomingEvents = Event::where('start_date', '>', now())
                              ->where('status', 'active')
                              ->orderBy('start_date')
                              ->take(5)
                              ->get();
        
        // Get volunteering opportunities
        $volunteeringOpportunities = VolunteeringOpportunity::where('status', 'active')
                                                           ->where('end_date', '>', now())
                                                           ->where('current_volunteers', '<', 'max_volunteers')
                                                           ->take(5)
                                                           ->get();
        
        // Get recent forum threads from user's organizations
        $recentForumThreads = ForumThread::whereIn('organization_id', $userOrganizations->pluck('id'))
                                        ->where('status', 'active')
                                        ->latest('last_post_at')
                                        ->take(5)
                                        ->get();
        
        // Get user's volunteering history
        $userVolunteeringHistory = $user->volunteeringHistory()
                                       ->where('status', 'active')
                                       ->with('volunteeringOpportunity')
                                       ->take(3)
                                       ->get();

        return view('client.dashboard', compact(
            'user',
            'userOrganizations',
            'recentNews',
            'upcomingEvents',
            'volunteeringOpportunities',
            'recentForumThreads',
            'userVolunteeringHistory'
        ));
    }

    /**
     * Get personalized content for the user.
     */
    public function getPersonalizedContent()
    {
        $user = Auth::user();
        
        // Get content based on user's interests and organizations
        $userOrganizationIds = $user->organizations()->pluck('organization_id');
        $userInterestCategories = $user->volunteeringInterests()->pluck('volunteering_category_id');
        
        $personalizedNews = News::where('status', 'published')
                               ->where(function($query) use ($userOrganizationIds) {
                                   $query->whereIn('organization_id', $userOrganizationIds)
                                         ->orWhereNull('organization_id');
                               })
                               ->latest('created')
                               ->take(10)
                               ->get();
        
        $personalizedEvents = Event::where('status', 'active')
                                  ->where('start_date', '>', now())
                                  ->where(function($query) use ($userOrganizationIds) {
                                      $query->whereIn('organization_id', $userOrganizationIds)
                                            ->orWhereNull('organization_id');
                                  })
                                  ->orderBy('start_date')
                                  ->take(10)
                                  ->get();
        
        $personalizedOpportunities = VolunteeringOpportunity::where('status', 'active')
                                                           ->where('end_date', '>', now())
                                                           ->whereHas('categories', function($query) use ($userInterestCategories) {
                                                               $query->whereIn('volunteering_category_id', $userInterestCategories);
                                                           })
                                                           ->take(10)
                                                           ->get();

        return response()->json([
            'news' => $personalizedNews,
            'events' => $personalizedEvents,
            'opportunities' => $personalizedOpportunities,
        ]);
    }

    /**
     * Get user's activity summary.
     */
    public function getActivitySummary()
    {
        $user = Auth::user();
        
        $summary = [
            'organizations_count' => $user->organizations()->where('status', 'active')->count(),
            'volunteering_hours' => $user->volunteeringHistory()->sum('hours_completed'),
            'completed_volunteering' => $user->volunteeringHistory()->where('status', 'completed')->count(),
            'active_volunteering' => $user->volunteeringHistory()->where('status', 'active')->count(),
            'forum_posts' => $user->forumPosts()->count(),
            'unread_notifications' => $user->notifications()->where('is_read', false)->count(),
        ];

        return response()->json($summary);
    }
}