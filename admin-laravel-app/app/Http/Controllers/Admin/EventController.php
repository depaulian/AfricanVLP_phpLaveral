<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Organization;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EventController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display a listing of events
     */
    public function index(Request $request): JsonResponse
    {
        $query = Event::with(['organization', 'city', 'country', 'region']);

        // Apply filters
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->input('city_id'));
        }

        if ($request->filled('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }

        if ($request->filled('region_id')) {
            $query->where('region_id', $request->input('region_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('date_filter')) {
            switch ($request->input('date_filter')) {
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

        // Sorting
        $sortBy = $request->input('sort_by', 'start_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $events = $query->paginate($perPage)->withQueryString();

        // Get filter options
        $organizations = Organization::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $cities = City::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $countries = Country::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $regions = Region::select('id', 'name')->where('status', 'active')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'events' => $events,
                'filters' => [
                    'organizations' => $organizations,
                    'cities' => $cities,
                    'countries' => $countries,
                    'regions' => $regions
                ]
            ]
        ]);
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'sometimes|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'registration_deadline' => 'sometimes|date|before:start_date',
            'location' => 'required|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'city_id' => 'sometimes|exists:cities,id',
            'country_id' => 'sometimes|exists:countries,id',
            'region_id' => 'sometimes|exists:regions,id',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'max_participants' => 'sometimes|integer|min:1',
            'contact_email' => 'sometimes|email',
            'contact_phone' => 'sometimes|string|max:20',
            'website_url' => 'sometimes|url',
            'status' => 'sometimes|in:draft,active,cancelled,completed',
            'featured' => 'sometimes|boolean',
            'is_virtual' => 'sometimes|boolean',
            'virtual_link' => 'sometimes|url',
            'tags' => 'sometimes|array',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            
            // Generate slug if not provided
            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Handle image upload
            if ($request->hasFile('image')) {
                $uploadResult = $this->fileUploadService->uploadFile(
                    $request->file('image'),
                    'events/images',
                    [
                        'folder' => 'events',
                        'transformation' => [
                            'width' => 1200,
                            'height' => 630,
                            'crop' => 'fill',
                            'quality' => 'auto'
                        ]
                    ]
                );

                if ($uploadResult['success']) {
                    $data['image'] = $uploadResult['file_path'];
                }
            }

            $event = Event::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => $event->load(['organization', 'city', 'country', 'region'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Event creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Event creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified event
     */
    public function show(Event $event): JsonResponse
    {
        $event->load(['organization', 'city', 'country', 'region', 'volunteeringOpportunities']);

        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, Event $event): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'content' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'registration_deadline' => 'sometimes|date|before:start_date',
            'location' => 'sometimes|string|max:255',
            'organization_id' => 'sometimes|exists:organizations,id',
            'city_id' => 'sometimes|exists:cities,id',
            'country_id' => 'sometimes|exists:countries,id',
            'region_id' => 'sometimes|exists:regions,id',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'max_participants' => 'sometimes|integer|min:1',
            'contact_email' => 'sometimes|email',
            'contact_phone' => 'sometimes|string|max:20',
            'website_url' => 'sometimes|url',
            'status' => 'sometimes|in:draft,active,cancelled,completed',
            'featured' => 'sometimes|boolean',
            'is_virtual' => 'sometimes|boolean',
            'virtual_link' => 'sometimes|url',
            'tags' => 'sometimes|array',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($event->image) {
                    $this->fileUploadService->deleteFile($event->image);
                }

                $uploadResult = $this->fileUploadService->uploadFile(
                    $request->file('image'),
                    'events/images',
                    [
                        'folder' => 'events',
                        'transformation' => [
                            'width' => 1200,
                            'height' => 630,
                            'crop' => 'fill',
                            'quality' => 'auto'
                        ]
                    ]
                );

                if ($uploadResult['success']) {
                    $data['image'] = $uploadResult['file_path'];
                }
            }

            $event->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => $event->load(['organization', 'city', 'country', 'region'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Event update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Event update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified event
     */
    public function destroy(Event $event): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Delete image if exists
            if ($event->image) {
                $this->fileUploadService->deleteFile($event->image);
            }

            $event->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Event deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Event deletion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations on events
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,feature,unfeature,cancel,complete,delete',
            'event_ids' => 'required|array|min:1',
            'event_ids.*' => 'exists:events,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $eventIds = $request->input('event_ids');
            $action = $request->input('action');
            $affected = 0;

            switch ($action) {
                case 'activate':
                    $affected = Event::whereIn('id', $eventIds)
                        ->update(['status' => 'active']);
                    break;

                case 'deactivate':
                    $affected = Event::whereIn('id', $eventIds)
                        ->update(['status' => 'draft']);
                    break;

                case 'feature':
                    $affected = Event::whereIn('id', $eventIds)
                        ->update(['featured' => true]);
                    break;

                case 'unfeature':
                    $affected = Event::whereIn('id', $eventIds)
                        ->update(['featured' => false]);
                    break;

                case 'cancel':
                    $affected = Event::whereIn('id', $eventIds)
                        ->update(['status' => 'cancelled']);
                    break;

                case 'complete':
                    $affected = Event::whereIn('id', $eventIds)
                        ->update(['status' => 'completed']);
                    break;

                case 'delete':
                    $events = Event::whereIn('id', $eventIds)->get();
                    foreach ($events as $event) {
                        if ($event->image) {
                            $this->fileUploadService->deleteFile($event->image);
                        }
                    }
                    $affected = Event::whereIn('id', $eventIds)->delete();
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk {$action} completed successfully",
                'affected_count' => $affected
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Bulk {$action} error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => "Bulk {$action} failed: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get event statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_events' => Event::count(),
            'active_events' => Event::where('status', 'active')->count(),
            'draft_events' => Event::where('status', 'draft')->count(),
            'featured_events' => Event::where('featured', true)->count(),
            'upcoming_events' => Event::where('start_date', '>', now())->count(),
            'ongoing_events' => Event::where('start_date', '<=', now())
                                    ->where('end_date', '>=', now())
                                    ->count(),
            'past_events' => Event::where('end_date', '<', now())->count(),
            'virtual_events' => Event::where('is_virtual', true)->count(),
            'total_participants' => Event::sum('current_participants'),
            'events_this_month' => Event::whereMonth('created_at', now()->month)
                                       ->whereYear('created_at', now()->year)
                                       ->count(),
            'top_organizations' => Organization::withCount('events')
                                              ->orderBy('events_count', 'desc')
                                              ->take(5)
                                              ->get(['id', 'name', 'events_count']),
            'by_status' => Event::select('status', DB::raw('count(*) as count'))
                               ->groupBy('status')
                               ->get(),
            'by_region' => Event::with('region')
                               ->select('region_id', DB::raw('count(*) as count'))
                               ->groupBy('region_id')
                               ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Duplicate an event
     */
    public function duplicate(Event $event): JsonResponse
    {
        try {
            DB::beginTransaction();

            $newEvent = $event->replicate();
            $newEvent->title = $event->title . ' (Copy)';
            $newEvent->slug = Str::slug($newEvent->title);
            $newEvent->status = 'draft';
            $newEvent->featured = false;
            $newEvent->current_participants = 0;
            $newEvent->start_date = null;
            $newEvent->end_date = null;
            $newEvent->registration_deadline = null;
            $newEvent->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Event duplicated successfully',
                'data' => $newEvent->load(['organization', 'city', 'country', 'region'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Event duplication error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Event duplication failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get events by organization
     */
    public function byOrganization(Request $request, Organization $organization): JsonResponse
    {
        $query = $organization->events()->with(['city', 'country', 'region']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'start_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $events = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    /**
     * UI: Events list page
     */
    public function uiIndex(Request $request)
    {
        $query = Event::with(['organization', 'city', 'country', 'region']);

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->input('city_id'));
        }
        if ($request->filled('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->input('region_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->where('start_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->where('start_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('date_filter')) {
            switch ($request->input('date_filter')) {
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

        $sortBy = $request->input('sort_by', 'start_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 15);
        $events = $query->paginate($perPage)->withQueryString();

        $organizations = Organization::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $cities = City::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $countries = Country::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $regions = Region::select('id', 'name')->where('status', 'active')->orderBy('name')->get();

        return view('admin.events.index', [
            'events' => $events,
            'filters' => [
                'organizations' => $organizations,
                'cities' => $cities,
                'countries' => $countries,
                'regions' => $regions,
            ],
            'sort' => [
                'by' => $sortBy,
                'order' => $sortOrder,
            ],
        ]);
    }

    /**
     * UI: Event create page
     */
    public function uiCreate()
    {
        $organizations = Organization::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $cities = City::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $countries = Country::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $regions = Region::select('id', 'name')->where('status', 'active')->orderBy('name')->get();

        return view('admin.events.create', compact('organizations', 'cities', 'countries', 'regions'));
    }

    /**
     * UI: Event details page
     */
    public function uiShow(Event $event)
    {
        $event->load(['organization', 'city', 'country', 'region', 'volunteeringOpportunities']);
        return view('admin.events.show', compact('event'));
    }

    /**
     * UI: Event edit page
     */
    public function uiEdit(Event $event)
    {
        $event->load(['organization', 'city', 'country', 'region']);
        $organizations = Organization::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $cities = City::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $countries = Country::select('id', 'name')->where('status', 'active')->orderBy('name')->get();
        $regions = Region::select('id', 'name')->where('status', 'active')->orderBy('name')->get();

        return view('admin.events.edit', compact('event', 'organizations', 'cities', 'countries', 'regions'));
    }

    /**
     * UI: Handle event creation from Blade form
     */
    public function uiStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'sometimes|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'registration_deadline' => 'sometimes|date|before:start_date',
            'location' => 'required|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'city_id' => 'sometimes|exists:cities,id',
            'country_id' => 'sometimes|exists:countries,id',
            'region_id' => 'sometimes|exists:regions,id',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'max_participants' => 'sometimes|integer|min:1',
            'contact_email' => 'sometimes|email',
            'contact_phone' => 'sometimes|string|max:20',
            'website_url' => 'sometimes|url',
            'status' => 'sometimes|in:draft,scheduled,ongoing,cancelled,completed',
            'featured' => 'sometimes|boolean',
            'is_virtual' => 'sometimes|boolean',
            'virtual_link' => 'sometimes|url',
            'tags' => 'sometimes|array',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            if (!isset($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            if ($request->hasFile('image')) {
                $uploadResult = $this->fileUploadService->uploadFile(
                    $request->file('image'),
                    'events/images',
                    [
                        'folder' => 'events',
                        'transformation' => [
                            'width' => 1200,
                            'height' => 630,
                            'crop' => 'fill',
                            'quality' => 'auto'
                        ]
                    ]
                );

                if ($uploadResult['success']) {
                    $data['image'] = $uploadResult['file_path'];
                }
            }

            $event = Event::create($data);

            DB::commit();

            return redirect()->route('admin.events.ui.show', $event)
                ->with('status', 'Event created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UI Event creation error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Event creation failed'])->withInput();
        }
    }

    /**
     * UI: Handle event update from Blade form
     */
    public function uiUpdate(Request $request, Event $event)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'content' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'registration_deadline' => 'sometimes|date|before:start_date',
            'location' => 'sometimes|string|max:255',
            'organization_id' => 'sometimes|exists:organizations,id',
            'city_id' => 'sometimes|exists:cities,id',
            'country_id' => 'sometimes|exists:countries,id',
            'region_id' => 'sometimes|exists:regions,id',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'max_participants' => 'sometimes|integer|min:1',
            'contact_email' => 'sometimes|email',
            'contact_phone' => 'sometimes|string|max:20',
            'website_url' => 'sometimes|url',
            'status' => 'sometimes|in:draft,scheduled,ongoing,cancelled,completed',
            'featured' => 'sometimes|boolean',
            'is_virtual' => 'sometimes|boolean',
            'virtual_link' => 'sometimes|url',
            'tags' => 'sometimes|array',
            'meta_title' => 'sometimes|string|max:255',
            'meta_description' => 'sometimes|string|max:500',
            'meta_keywords' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $data = $request->all();

            if ($request->hasFile('image')) {
                if ($event->image) {
                    $this->fileUploadService->deleteFile($event->image);
                }

                $uploadResult = $this->fileUploadService->uploadFile(
                    $request->file('image'),
                    'events/images',
                    [
                        'folder' => 'events',
                        'transformation' => [
                            'width' => 1200,
                            'height' => 630,
                            'crop' => 'fill',
                            'quality' => 'auto'
                        ]
                    ]
                );

                if ($uploadResult['success']) {
                    $data['image'] = $uploadResult['file_path'];
                }
            }

            $event->update($data);

            DB::commit();

            return redirect()->route('admin.events.ui.show', $event)
                ->with('status', 'Event updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UI Event update error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Event update failed'])->withInput();
        }
    }
}
