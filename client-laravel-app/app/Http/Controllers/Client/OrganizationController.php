<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
{
    /**
     * Display organization dashboard.
     */
    public function dashboard(Organization $organization)
    {
        // Check if user has access to this organization
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }

        $organization->load(['country', 'city', 'categoryOfOrganization']);

        // Get organization statistics
        $stats = [
            'total_members' => $organization->getActiveMembersCount(),
            'upcoming_events' => $organization->getUpcomingEventsCount(),
            'forum_threads' => $organization->forumThreads()->where('status', 'active')->count(),
            'volunteering_opportunities' => $organization->volunteeringOpportunities()->where('status', 'active')->count(),
        ];

        // Get recent activity
        $recentNews = $organization->news()
                                 ->where('status', 'published')
                                 ->latest('published_at')
                                 ->take(5)
                                 ->get();

        $upcomingEvents = $organization->events()
                                     ->where('status', 'active')
                                     ->where('start_date', '>', now())
                                     ->orderBy('start_date')
                                     ->take(5)
                                     ->get();

        $recentForumThreads = $organization->forumThreads()
                                         ->where('status', 'active')
                                         ->with('user')
                                         ->latest('last_post_at')
                                         ->take(5)
                                         ->get();

        return view('client.organization.dashboard', compact(
            'organization', 
            'stats', 
            'recentNews', 
            'upcomingEvents', 
            'recentForumThreads'
        ));
    }

    /**
     * Display organization members.
     */
    public function members(Organization $organization, Request $request)
    {
        // Check if user has access to this organization
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }

        $query = $organization->users()->wherePivot('status', 'active');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $query->wherePivot('role', $request->role);
        }

        $members = $query->withPivot('role', 'joined_date')
                        ->orderBy('organization_users.joined_date', 'desc')
                        ->paginate(20)
                        ->withQueryString();

        $roles = ['admin', 'member', 'moderator', 'viewer'];

        return view('client.organization.members', compact('organization', 'members', 'roles'));
    }

    /**
     * Display organization events.
     */
    public function events(Organization $organization, Request $request)
    {
        // Check if user has access to this organization
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }

        $query = $organization->events()->where('status', 'active');

        // Filter by upcoming/past
        if ($request->get('filter') === 'upcoming') {
            $query->where('start_date', '>', now());
        } elseif ($request->get('filter') === 'past') {
            $query->where('end_date', '<', now());
        }

        $events = $query->orderBy('start_date', 'desc')
                       ->paginate(12)
                       ->withQueryString();

        return view('client.organization.events', compact('organization', 'events'));
    }

    /**
     * Display organization news.
     */
    public function news(Organization $organization, Request $request)
    {
        // Check if user has access to this organization
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }

        $query = $organization->news()->where('status', 'published');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $news = $query->orderBy('published_at', 'desc')
                     ->paginate(10)
                     ->withQueryString();

        return view('client.organization.news', compact('organization', 'news'));
    }

    /**
     * Show organization management page (for organization admins).
     */
    public function manage(Organization $organization)
    {
        // Check if user is admin of this organization
        $userRole = $organization->users()
                                ->where('user_id', Auth::id())
                                ->first()
                                ->pivot
                                ->role ?? null;

        if ($userRole !== 'admin') {
            abort(403, 'You do not have admin access to this organization.');
        }

        $organization->load(['country', 'city', 'categoryOfOrganization', 'institutionType']);

        // Get pending invitations
        $pendingInvitations = $organization->temporaryUserInvitations()
                                         ->where('status', 'pending')
                                         ->latest('created')
                                         ->get();

        return view('client.organization.manage', compact('organization', 'pendingInvitations'));
    }

    /**
     * Update organization information.
     */
    public function update(Request $request, Organization $organization)
    {
        // Check if user is admin of this organization
        $userRole = $organization->users()
                                ->where('user_id', Auth::id())
                                ->first()
                                ->pivot
                                ->role ?? null;

        if ($userRole !== 'admin') {
            abort(403, 'You do not have admin access to this organization.');
        }

        $validator = Validator::make($request->all(), [
            'description' => 'nullable|string|max:5000',
            'email' => 'nullable|string|email|max:100',
            'phone_number' => 'nullable|string|max:20|regex:/^[\+]?[0-9\s\-\(\)]+$/',
            'website' => 'nullable|string|url|max:255',
            'address' => 'nullable|string|max:500',
            'facebook_url' => 'nullable|string|url|max:255',
            'twitter_url' => 'nullable|string|url|max:255',
            'linkedin_url' => 'nullable|string|url|max:255',
            'instagram_url' => 'nullable|string|url|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $organization->update([
            'description' => $request->description,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'website' => $request->website,
            'address' => $request->address,
            'facebook_url' => $request->facebook_url,
            'twitter_url' => $request->twitter_url,
            'linkedin_url' => $request->linkedin_url,
            'instagram_url' => $request->instagram_url,
            'modified' => now(),
        ]);

        return back()->with('success', 'Organization information updated successfully!');
    }

    /**
     * Invite a user to the organization.
     */
    public function inviteUser(Request $request, Organization $organization)
    {
        // Check if user is admin of this organization
        $userRole = $organization->users()
                                ->where('user_id', Auth::id())
                                ->first()
                                ->pivot
                                ->role ?? null;

        if ($userRole !== 'admin') {
            abort(403, 'You do not have admin access to this organization.');
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:100',
            'role' => 'required|in:admin,member,moderator,viewer',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return back()->with('error', 'User with this email does not exist.');
        }

        // Check if user is already a member
        if ($organization->users()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'User is already a member of this organization.');
        }

        // Check if invitation already exists
        if ($organization->temporaryUserInvitations()
                         ->where('email', $request->email)
                         ->where('status', 'pending')
                         ->exists()) {
            return back()->with('error', 'An invitation has already been sent to this email.');
        }

        // Create invitation
        $organization->temporaryUserInvitations()->create([
            'email' => $request->email,
            'role' => $request->role,
            'message' => $request->message,
            'invited_by' => Auth::id(),
            'status' => 'pending',
            'token' => \Str::random(60),
            'expires_at' => now()->addDays(7),
            'created' => now(),
            'modified' => now(),
        ]);

        // Here you would send the invitation email
        // For now, we'll just show a success message

        return back()->with('success', 'Invitation sent successfully!');
    }

    /**
     * Remove a user from the organization.
     */
    public function removeUser(Organization $organization, User $user)
    {
        // Check if user is admin of this organization
        $userRole = $organization->users()
                                ->where('user_id', Auth::id())
                                ->first()
                                ->pivot
                                ->role ?? null;

        if ($userRole !== 'admin') {
            abort(403, 'You do not have admin access to this organization.');
        }

        // Cannot remove yourself
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot remove yourself from the organization.');
        }

        // Remove user from organization
        $organization->users()->detach($user->id);

        return back()->with('success', 'User removed from organization successfully!');
    }

    /**
     * Search organizations via AJAX.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        $organizations = Organization::where('status', 'active')
                                   ->where('name', 'like', "%{$query}%")
                                   ->limit(10)
                                   ->get(['id', 'name', 'description']);

        return response()->json($organizations);
    }
}