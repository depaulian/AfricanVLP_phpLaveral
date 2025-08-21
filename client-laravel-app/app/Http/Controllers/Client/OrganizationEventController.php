<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Models\VolunteeringOpportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class OrganizationEventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display events for the organization
     */
    public function index(Organization $organization, Request $request): View
    {
        $this->checkOrganizationAccess($organization);

        $query = $organization->events()->with(['city', 'country', 'volunteeringOpportunities']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date filter
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'upcoming':
                    $query->where('start_date', '>', now());
                    break;
                case 'ongoing':
                    $query->where('start_date', '<=', now())
                          ->where('end_date', '>=', now());
                    break;
                case 'past':
                    $query->where('end_date', '<', now());
                    break;
            }
        }

        $events = $query->orderBy('start_date', 'desc')->paginate(12)->withQueryString();

        $statuses = ['draft', 'active', 'cancelled', 'completed'];
        $dateFilters = [
            'upcoming' => 'Upcoming Events',
            'ongoing' => 'Ongoing Events',
            'past' => 'Past Events'
        ];

        return view('client.organization.events.index', compact(
            'organization', 'events', 'statuses', 'dateFilters'
        ));
    }

    /**
     * Show the form for creating a new event
     */
    public function create(Organization $organization): View
    {
        $this->checkOrganizationAdminAccess($organization);

        $countries = Country::where('status', 'active')->orderBy('name')->get();
        $cities = City::where('status', 'active')->orderBy('name')->get();
        $regions = Region::where('status', 'active')->orderBy('name')->get();

        return view('client.organization.events.create', compact(
            'organization', 'countries', 'cities', 'regions'
        ));
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request, Organization $organization): RedirectResponse
    {
        $this->checkOrganizationAdminAccess($organization);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'content' => 'nullable|string',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'country_id' => 'nullable|exists:countries,id',
            'region_id' => 'nullable|exists:regions,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'max_participants' => 'nullable|integer|min:1',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'registration_fee' => 'nullable|numeric|min:0',
            'registration_deadline' => 'nullable|date|before:start_date',
            'status' => 'required|in:draft,active,cancelled',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $eventData = $request->only([
            'title', 'description', 'content', 'start_date', 'end_date',
            'location', 'city_id', 'country_id', 'region_id', 'latitude',
            'longitude', 'max_participants', 'contact_email', 'contact_phone',
            'registration_fee', 'registration_deadline', 'status', 'is_featured'
        ]);

        $eventData['organization_id'] = $organization->id;
        $eventData['current_participants'] = 0;

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('events', 'public');
            $eventData['image'] = $imagePath;
        }

        $event = Event::create($eventData);

        return redirect()
            ->route('client.organizations.events.show', [$organization, $event])
            ->with('success', 'Event created successfully!');
    }

    /**
     * Display the specified event
     */
    public function show(Organization $organization, Event $event): View
    {
        $this->checkOrganizationAccess($organization);
        $this->checkEventBelongsToOrganization($event, $organization);

        $event->load(['city', 'country', 'region', 'volunteeringOpportunities']);

        $isAdmin = $this->isOrganizationAdmin($organization);

        return view('client.organization.events.show', compact(
            'organization', 'event', 'isAdmin'
        ));
    }

    /**
     * Show the form for editing the specified event
     */
    public function edit(Organization $organization, Event $event): View
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkEventBelongsToOrganization($event, $organization);

        $countries = Country::where('status', 'active')->orderBy('name')->get();
        $cities = City::where('status', 'active')->orderBy('name')->get();
        $regions = Region::where('status', 'active')->orderBy('name')->get();

        return view('client.organization.events.edit', compact(
            'organization', 'event', 'countries', 'cities', 'regions'
        ));
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, Organization $organization, Event $event): RedirectResponse
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkEventBelongsToOrganization($event, $organization);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'content' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'required|string|max:255',
            'city_id' => 'nullable|exists:cities,id',
            'country_id' => 'nullable|exists:countries,id',
            'region_id' => 'nullable|exists:regions,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'max_participants' => 'nullable|integer|min:1',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'registration_fee' => 'nullable|numeric|min:0',
            'registration_deadline' => 'nullable|date|before:start_date',
            'status' => 'required|in:draft,active,cancelled,completed',
            'is_featured' => 'boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $eventData = $request->only([
            'title', 'description', 'content', 'start_date', 'end_date',
            'location', 'city_id', 'country_id', 'region_id', 'latitude',
            'longitude', 'max_participants', 'contact_email', 'contact_phone',
            'registration_fee', 'registration_deadline', 'status', 'is_featured'
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($event->image) {
                Storage::disk('public')->delete($event->image);
            }
            $imagePath = $request->file('image')->store('events', 'public');
            $eventData['image'] = $imagePath;
        }

        $event->update($eventData);

        return redirect()
            ->route('client.organizations.events.show', [$organization, $event])
            ->with('success', 'Event updated successfully!');
    }

    /**
     * Remove the specified event
     */
    public function destroy(Organization $organization, Event $event): RedirectResponse
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkEventBelongsToOrganization($event, $organization);

        // Delete image if exists
        if ($event->image) {
            Storage::disk('public')->delete($event->image);
        }

        $event->delete();

        return redirect()
            ->route('client.organizations.events.index', $organization)
            ->with('success', 'Event deleted successfully!');
    }

    /**
     * Add volunteering opportunities to an event
     */
    public function addVolunteeringOpportunity(Request $request, Organization $organization, Event $event): RedirectResponse
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkEventBelongsToOrganization($event, $organization);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'required_skills' => 'nullable|string|max:500',
            'time_commitment' => 'nullable|string|max:255',
            'max_volunteers' => 'nullable|integer|min:1',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $opportunityData = $request->only([
            'title', 'description', 'required_skills', 'time_commitment',
            'max_volunteers', 'contact_email', 'contact_phone'
        ]);

        $opportunityData['event_id'] = $event->id;
        $opportunityData['organization_id'] = $organization->id;
        $opportunityData['status'] = 'active';
        $opportunityData['current_volunteers'] = 0;

        VolunteeringOpportunity::create($opportunityData);

        return back()->with('success', 'Volunteering opportunity added successfully!');
    }

    /**
     * Duplicate an event
     */
    public function duplicate(Organization $organization, Event $event): RedirectResponse
    {
        $this->checkOrganizationAdminAccess($organization);
        $this->checkEventBelongsToOrganization($event, $organization);

        $newEventData = $event->toArray();
        unset($newEventData['id'], $newEventData['created'], $newEventData['modified']);
        
        // Modify title and dates for the duplicate
        $newEventData['title'] = 'Copy of ' . $newEventData['title'];
        $newEventData['status'] = 'draft';
        $newEventData['current_participants'] = 0;
        $newEventData['start_date'] = null;
        $newEventData['end_date'] = null;
        $newEventData['registration_deadline'] = null;

        $newEvent = Event::create($newEventData);

        return redirect()
            ->route('client.organizations.events.edit', [$organization, $newEvent])
            ->with('success', 'Event duplicated successfully! Please update the dates and details.');
    }

    /**
     * Check if user has access to the organization
     */
    private function checkOrganizationAccess(Organization $organization): void
    {
        if (!Auth::user()->belongsToOrganization($organization->id)) {
            abort(403, 'You do not have access to this organization.');
        }
    }

    /**
     * Check if user has admin access to the organization
     */
    private function checkOrganizationAdminAccess(Organization $organization): void
    {
        $userRole = $organization->users()
                                ->where('user_id', Auth::id())
                                ->first()
                                ?->pivot
                                ?->role;

        if ($userRole !== 'admin') {
            abort(403, 'You do not have admin access to this organization.');
        }
    }

    /**
     * Check if user is organization admin
     */
    private function isOrganizationAdmin(Organization $organization): bool
    {
        $userRole = $organization->users()
                                ->where('user_id', Auth::id())
                                ->first()
                                ?->pivot
                                ?->role;

        return $userRole === 'admin';
    }

    /**
     * Check if event belongs to organization
     */
    private function checkEventBelongsToOrganization(Event $event, Organization $organization): void
    {
        if ($event->organization_id !== $organization->id) {
            abort(403, 'This event does not belong to the specified organization.');
        }
    }
}
