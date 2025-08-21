<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MobileVolunteeringService;
use App\Services\MobileDetectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MobileVolunteeringController extends Controller
{
    protected MobileVolunteeringService $mobileService;
    protected MobileDetectionService $mobileDetection;

    public function __construct(
        MobileVolunteeringService $mobileService,
        MobileDetectionService $mobileDetection
    ) {
        $this->mobileService = $mobileService;
        $this->mobileDetection = $mobileDetection;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get nearby volunteering opportunities
     */
    public function getNearbyOpportunities(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:50',
            'limit' => 'nullable|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $opportunities = $this->mobileService->getNearbyOpportunities($request);

            return response()->json([
                'success' => true,
                'data' => $opportunities,
                'meta' => [
                    'count' => count($opportunities),
                    'location' => [
                        'latitude' => $request->get('latitude'),
                        'longitude' => $request->get('longitude'),
                        'radius' => $request->get('radius', config('mobile.gps.search_radius.default'))
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch nearby opportunities',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create mobile time log
     */
    public function createTimeLog(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|exists:volunteer_assignments,id',
            'log_date' => 'required|date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after:start_time',
            'hours_logged' => 'required|numeric|min:0.1|max:24',
            'description' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_accuracy' => 'nullable|integer|min:1',
            'device_info' => 'nullable|array',
            'captured_at' => 'nullable|date',
            'photo' => 'nullable|image|max:5120' // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->validated();
            $data['user_id'] = Auth::id();

            $photo = $request->hasFile('photo') ? $request->file('photo') : null;
            $result = $this->mobileService->createMobileTimeLog($data, $photo);

            return response()->json($result, $result['success'] ? 201 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create time log',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check in to volunteer assignment
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|exists:volunteer_assignments,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'location_accuracy' => 'nullable|integer|min:1',
            'device_info' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
            'photo' => 'nullable|image|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->validated();
            $data['user_id'] = Auth::id();

            $photo = $request->hasFile('photo') ? $request->file('photo') : null;
            $result = $this->mobileService->checkIn($data, $photo);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check in',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check out from volunteer assignment
     */
    public function checkOut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assignment_id' => 'required|exists:volunteer_assignments,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_accuracy' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'photo' => 'nullable|image|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->validated();
            $data['user_id'] = Auth::id();

            $photo = $request->hasFile('photo') ? $request->file('photo') : null;
            $result = $this->mobileService->checkOut($data, $photo);

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check out',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get offline data for mobile app
     */
    public function getOfflineData(Request $request): JsonResponse
    {
        try {
            $data = $this->mobileService->getOfflineData(Auth::id());

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'cache_duration' => config('mobile.offline.cache_duration'),
                    'sync_interval' => config('mobile.offline.sync_interval'),
                    'generated_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch offline data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Sync offline data
     */
    public function syncOfflineData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'time_logs' => 'nullable|array',
            'time_logs.*.local_id' => 'nullable|string',
            'time_logs.*.assignment_id' => 'required|exists:volunteer_assignments,id',
            'time_logs.*.log_date' => 'required|date',
            'time_logs.*.start_time' => 'required|date_format:H:i:s',
            'time_logs.*.end_time' => 'required|date_format:H:i:s',
            'time_logs.*.hours_logged' => 'required|numeric|min:0.1',
            'check_ins' => 'nullable|array',
            'check_ins.*.local_id' => 'nullable|string',
            'check_ins.*.assignment_id' => 'required|exists:volunteer_assignments,id',
            'check_ins.*.check_in_time' => 'required|date',
            'check_ins.*.check_out_time' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->validated();
            
            // Add user_id to all items
            if (isset($data['time_logs'])) {
                foreach ($data['time_logs'] as &$timeLog) {
                    $timeLog['user_id'] = Auth::id();
                }
            }

            if (isset($data['check_ins'])) {
                foreach ($data['check_ins'] as &$checkIn) {
                    $checkIn['user_id'] = Auth::id();
                }
            }

            $result = $this->mobileService->syncOfflineData($data);

            return response()->json([
                'success' => $result['success'],
                'data' => $result,
                'meta' => [
                    'synced_count' => count($result['synced']),
                    'error_count' => count($result['errors']),
                    'synced_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync offline data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user's active check-ins
     */
    public function getActiveCheckIns(Request $request): JsonResponse
    {
        try {
            $checkIns = \App\Models\VolunteerCheckIn::with(['assignment.opportunity'])
                ->where('user_id', Auth::id())
                ->where('status', 'active')
                ->whereNull('check_out_time')
                ->get()
                ->map(function ($checkIn) {
                    return [
                        'id' => $checkIn->id,
                        'assignment_id' => $checkIn->assignment_id,
                        'opportunity' => [
                            'id' => $checkIn->assignment->opportunity->id,
                            'title' => $checkIn->assignment->opportunity->title
                        ],
                        'check_in_time' => $checkIn->check_in_time->toISOString(),
                        'duration' => $checkIn->getCurrentDuration(),
                        'formatted_duration' => $checkIn->getFormattedDuration(),
                        'location' => [
                            'latitude' => $checkIn->check_in_latitude,
                            'longitude' => $checkIn->check_in_longitude,
                            'accuracy' => $checkIn->check_in_accuracy
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $checkIns,
                'meta' => [
                    'count' => $checkIns->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active check-ins',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update FCM token for push notifications
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $user->update(['fcm_token' => $request->fcm_token]);

            return response()->json([
                'success' => true,
                'message' => 'FCM token updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update FCM token',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get mobile app configuration
     */
    public function getConfig(Request $request): JsonResponse
    {
        try {
            $config = [
                'gps' => [
                    'enabled' => config('mobile.gps.enabled'),
                    'accuracy_threshold' => config('mobile.gps.accuracy_threshold'),
                    'timeout' => config('mobile.gps.timeout'),
                    'max_age' => config('mobile.gps.max_age'),
                    'search_radius' => config('mobile.gps.search_radius')
                ],
                'offline' => [
                    'enabled' => config('mobile.offline.enabled'),
                    'cache_duration' => config('mobile.offline.cache_duration'),
                    'sync_interval' => config('mobile.offline.sync_interval'),
                    'max_opportunities' => config('mobile.offline.max_opportunities'),
                    'max_time_logs' => config('mobile.offline.max_time_logs')
                ],
                'push_notifications' => [
                    'enabled' => config('mobile.push_notifications.enabled'),
                    'topics' => config('mobile.push_notifications.topics')
                ],
                'photo_capture' => [
                    'enabled' => config('mobile.photo_capture.enabled'),
                    'max_size' => config('mobile.photo_capture.max_size'),
                    'allowed_types' => config('mobile.photo_capture.allowed_types'),
                    'compression' => config('mobile.photo_capture.compression')
                ],
                'check_in' => [
                    'enabled' => config('mobile.check_in.enabled'),
                    'location_required' => config('mobile.check_in.location_required'),
                    'photo_required' => config('mobile.check_in.photo_required'),
                    'geofence_radius' => config('mobile.check_in.geofence_radius'),
                    'auto_check_out' => config('mobile.check_in.auto_check_out')
                ],
                'ui' => config('mobile.ui'),
                'performance' => config('mobile.performance')
            ];

            return response()->json([
                'success' => true,
                'data' => $config,
                'meta' => [
                    'version' => '1.0.0',
                    'updated_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch configuration',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get device compatibility info
     */
    public function getDeviceInfo(Request $request): JsonResponse
    {
        try {
            $userAgent = $request->header('User-Agent', '');
            $isMobile = $this->mobileDetection->isMobile($userAgent);
            $isTablet = $this->mobileDetection->isTablet($userAgent);
            $deviceType = $this->mobileDetection->getDeviceType($userAgent);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_mobile' => $isMobile,
                    'is_tablet' => $isTablet,
                    'device_type' => $deviceType,
                    'user_agent' => $userAgent,
                    'features_supported' => [
                        'gps' => config('mobile.gps.enabled'),
                        'push_notifications' => config('mobile.push_notifications.enabled'),
                        'offline_mode' => config('mobile.offline.enabled'),
                        'photo_capture' => config('mobile.photo_capture.enabled'),
                        'check_in_system' => config('mobile.check_in.enabled')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get device info',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}