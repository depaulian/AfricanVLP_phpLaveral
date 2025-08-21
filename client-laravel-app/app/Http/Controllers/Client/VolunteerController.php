<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteeringCategory;
use App\Models\VolunteeringInterest;
use App\Models\VolunteeringHistory;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VolunteerController extends Controller
{
    /**
     * Display volunteer dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's volunteering statistics
        $stats = [
            'active_applications' => $user->volunteeringHistory()->where('status', 'active')->count(),
            'completed_volunteering' => $user->volunteeringHistory()->where('status', 'completed')->count(),
            'total_hours' => $user->volunteeringHistory()->sum('hours_completed'),
            'interests_count' => $user->volunteeringInterests()->where('status', 'active')->count(),
        ];

        // Get recent volunteering history
        $recentHistory = $user->volunteeringHistory()
                             ->with(['volunteeringOpportunity.event', 'organization'])
                             ->latest('created')
                             ->take(5)
                             ->get();

        // Get recommended opportunities based on user interests
        $userInterestCategories = $user->volunteeringInterests()
                                      ->where('status', 'active')
                                      ->pluck('volunteering_category_id');

        $recommendedOpportunities = VolunteeringOpportunity::where('status', 'active')
                                                          ->where('end_date', '>', now())
                                                          ->whereHas('categories', function($query) use ($userInterestCategories) {
                                                              $query->whereIn('volunteering_category_id', $userInterestCategories);
                                                          })
                                                          ->with(['event', 'volunteeringRole'])
                                                          ->take(6)
                                                          ->get();

        return view('client.volunteer.index', compact('stats', 'recentHistory', 'recommendedOpportunities'));
    }

    /**
     * Display volunteering opportunities.
     */
    public function opportunities(Request $request)
    {
        $query = VolunteeringOpportunity::where('status', 'active')
                                       ->where('end_date', '>', now())
                                       ->with(['event', 'volunteeringRole', 'volunteeringDuration']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('event', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('volunteering_category_id', $request->category_id);
            });
        }

        // Role filter
        if ($request->filled('role_id')) {
            $query->where('volunteering_role_id', $request->role_id);
        }

        // Location filter
        if ($request->filled('location')) {
            $location = $request->location;
            $query->where(function($q) use ($location) {
                $q->where('location', 'like', "%{$location}%")
                  ->orWhereHas('event', function($eventQuery) use ($location) {
                      $eventQuery->where('location', 'like', "%{$location}%");
                  });
            });
        }

        // Available spots filter
        if ($request->filled('available_only')) {
            $query->whereRaw('current_volunteers < max_volunteers');
        }

        // Sort
        $sortBy = $request->get('sort', 'start_date');
        $sortDirection = $request->get('direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $opportunities = $query->paginate(12)->withQueryString();

        // Get filter options
        $categories = VolunteeringCategory::active()->ordered()->get();
        $roles = \App\Models\VolunteeringRole::active()->ordered()->get();

        return view('client.volunteer.opportunities', compact('opportunities', 'categories', 'roles'));
    }

    /**
     * Display the specified volunteering opportunity.
     */
    public function showOpportunity(VolunteeringOpportunity $opportunity)
    {
        // Check if opportunity is active and available
        if (!$opportunity->isActive()) {
            abort(404);
        }

        $opportunity->load([
            'event.organization',
            'volunteeringRole',
            'volunteeringDuration',
            'categories'
        ]);

        // Check if user has already applied
        $user = Auth::user();
        $hasApplied = $user->volunteeringHistory()
                          ->where('volunteering_oppurtunity_id', $opportunity->id)
                          ->whereIn('status', ['applied', 'accepted', 'active'])
                          ->exists();

        // Get similar opportunities
        $similarOpportunities = VolunteeringOpportunity::where('status', 'active')
                                                      ->where('id', '!=', $opportunity->id)
                                                      ->where('volunteering_role_id', $opportunity->volunteering_role_id)
                                                      ->where('end_date', '>', now())
                                                      ->with(['event', 'volunteeringRole'])
                                                      ->take(3)
                                                      ->get();

        return view('client.volunteer.opportunity-show', compact('opportunity', 'hasApplied', 'similarOpportunities'));
    }

    /**
     * Apply for a volunteering opportunity.
     */
    public function applyForOpportunity(Request $request, VolunteeringOpportunity $opportunity)
    {
        $user = Auth::user();

        // Check if opportunity is still available
        if (!$opportunity->isActive() || !$opportunity->hasAvailableSpots()) {
            return back()->with('error', 'This opportunity is no longer available.');
        }

        // Check if user has already applied
        $existingApplication = $user->volunteeringHistory()
                                   ->where('volunteering_oppurtunity_id', $opportunity->id)
                                   ->whereIn('status', ['applied', 'accepted', 'active'])
                                   ->first();

        if ($existingApplication) {
            return back()->with('error', 'You have already applied for this opportunity.');
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        // Create application
        VolunteeringHistory::create([
            'user_id' => $user->id,
            'volunteering_oppurtunity_id' => $opportunity->id,
            'organization_id' => $opportunity->event->organization_id,
            'status' => 'applied',
            'applied_date' => now(),
            'notes' => $request->notes,
            'created' => now(),
            'modified' => now(),
        ]);

        return back()->with('success', 'Your application has been submitted successfully!');
    }

    /**
     * Display user's volunteering applications.
     */
    public function myApplications(Request $request)
    {
        $user = Auth::user();
        
        $query = $user->volunteeringHistory()
                     ->with(['volunteeringOpportunity.event.organization', 'organization']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->orderBy('created', 'desc')->paginate(10)->withQueryString();

        $statuses = ['applied', 'accepted', 'active', 'completed', 'cancelled', 'rejected'];

        return view('client.volunteer.my-applications', compact('applications', 'statuses'));
    }

    /**
     * Display user's volunteering history.
     */
    public function history()
    {
        $user = Auth::user();
        
        $completedVolunteering = $user->volunteeringHistory()
                                    ->where('status', 'completed')
                                    ->with(['volunteeringOpportunity.event.organization', 'organization'])
                                    ->orderBy('end_date', 'desc')
                                    ->paginate(10);

        $totalHours = $user->volunteeringHistory()
                          ->where('status', 'completed')
                          ->sum('hours_completed');

        $certificatesEarned = $user->volunteeringHistory()
                                  ->where('status', 'completed')
                                  ->where('certificate_issued', true)
                                  ->count();

        return view('client.volunteer.history', compact('completedVolunteering', 'totalHours', 'certificatesEarned'));
    }

    /**
     * Display and manage user's volunteering interests.
     */
    public function interests()
    {
        $user = Auth::user();
        
        $categories = VolunteeringCategory::active()->ordered()->get();
        $userInterests = $user->volunteeringInterests()
                             ->with('volunteeringCategory')
                             ->get()
                             ->keyBy('volunteering_category_id');

        return view('client.volunteer.interests', compact('categories', 'userInterests'));
    }

    /**
     * Update user's volunteering interests.
     */
    public function updateInterests(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'interests' => 'array',
            'interests.*' => 'integer|between:1,4',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $interests = $request->get('interests', []);

        // Remove existing interests not in the new list
        $user->volunteeringInterests()
            ->whereNotIn('volunteering_category_id', array_keys($interests))
            ->delete();

        // Update or create interests
        foreach ($interests as $categoryId => $interestLevel) {
            $user->volunteeringInterests()->updateOrCreate(
                ['volunteering_category_id' => $categoryId],
                [
                    'interest_level' => $interestLevel,
                    'status' => 'active',
                    'modified' => now(),
                ]
            );
        }

        return back()->with('success', 'Your volunteering interests have been updated!');
    }
}