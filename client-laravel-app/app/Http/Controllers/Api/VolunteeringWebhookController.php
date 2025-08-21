<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WebhookService;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class VolunteeringWebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
        $this->middleware('auth:sanctum');
        $this->middleware('throttle:api');
    }

    /**
     * Register a new webhook
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', $this->webhookService->getAvailableEvents()),
            'secret' => 'nullable|string|min:16',
            'verify_ssl' => 'nullable|boolean',
            'timeout' => 'nullable|integer|min:5|max:120',
            'max_retries' => 'nullable|integer|min:0|max:10',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $webhook = $this->webhookService->register(
                $request->url,
                $request->events,
                $request->only(['secret', 'verify_ssl', 'timeout', 'max_retries', 'metadata'])
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'active' => $webhook->active,
                    'verify_ssl' => $webhook->verify_ssl,
                    'timeout' => $webhook->timeout,
                    'max_retries' => $webhook->max_retries,
                    'created_at' => $webhook->created_at->toISOString()
                ],
                'message' => 'Webhook registered successfully'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register webhook',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * List registered webhooks
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Webhook::query();

            if ($request->filled('active')) {
                $query->where('active', $request->boolean('active'));
            }

            if ($request->filled('event')) {
                $query->whereJsonContains('events', $request->event);
            }

            $webhooks = $query->orderBy('created_at', 'desc')->paginate(20);

            $data = $webhooks->getCollection()->map(function ($webhook) {
                $stats = $this->webhookService->getWebhookStats($webhook);
                
                return [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'active' => $webhook->active,
                    'verify_ssl' => $webhook->verify_ssl,
                    'timeout' => $webhook->timeout,
                    'max_retries' => $webhook->max_retries,
                    'statistics' => $stats,
                    'created_at' => $webhook->created_at->toISOString(),
                    'updated_at' => $webhook->updated_at->toISOString()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $webhooks->currentPage(),
                    'last_page' => $webhooks->lastPage(),
                    'per_page' => $webhooks->perPage(),
                    'total' => $webhooks->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch webhooks',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get webhook details
     */
    public function show(Webhook $webhook): JsonResponse
    {
        try {
            $stats = $this->webhookService->getWebhookStats($webhook);
            
            $recentDeliveries = $webhook->deliveries()
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($delivery) {
                    return [
                        'id' => $delivery->id,
                        'event' => $delivery->event,
                        'status' => $delivery->status,
                        'attempts' => $delivery->attempts,
                        'response_status' => $delivery->response_status,
                        'created_at' => $delivery->created_at->toISOString(),
                        'delivered_at' => $delivery->delivered_at?->toISOString(),
                        'failed_at' => $delivery->failed_at?->toISOString()
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'active' => $webhook->active,
                    'verify_ssl' => $webhook->verify_ssl,
                    'timeout' => $webhook->timeout,
                    'max_retries' => $webhook->max_retries,
                    'metadata' => $webhook->metadata,
                    'statistics' => $stats,
                    'recent_deliveries' => $recentDeliveries,
                    'created_at' => $webhook->created_at->toISOString(),
                    'updated_at' => $webhook->updated_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch webhook details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update webhook
     */
    public function update(Request $request, Webhook $webhook): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'sometimes|required|url',
            'events' => 'sometimes|required|array|min:1',
            'events.*' => 'string|in:' . implode(',', $this->webhookService->getAvailableEvents()),
            'active' => 'sometimes|boolean',
            'verify_ssl' => 'sometimes|boolean',
            'timeout' => 'sometimes|integer|min:5|max:120',
            'max_retries' => 'sometimes|integer|min:0|max:10',
            'metadata' => 'sometimes|nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $webhook->update($request->only([
                'url', 'events', 'active', 'verify_ssl', 'timeout', 'max_retries', 'metadata'
            ]));

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'active' => $webhook->active,
                    'verify_ssl' => $webhook->verify_ssl,
                    'timeout' => $webhook->timeout,
                    'max_retries' => $webhook->max_retries,
                    'metadata' => $webhook->metadata,
                    'updated_at' => $webhook->updated_at->toISOString()
                ],
                'message' => 'Webhook updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update webhook',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete webhook
     */
    public function destroy(Webhook $webhook): JsonResponse
    {
        try {
            $webhook->delete();

            return response()->json([
                'success' => true,
                'message' => 'Webhook deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete webhook',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Test webhook
     */
    public function test(Webhook $webhook): JsonResponse
    {
        try {
            $result = $this->webhookService->testWebhook($webhook);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => $result['success'] ? 'Webhook test successful' : 'Webhook test failed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to test webhook',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get available webhook events
     */
    public function events(): JsonResponse
    {
        try {
            $events = $this->webhookService->getAvailableEvents();

            $eventDescriptions = [
                'opportunity.created' => 'Triggered when a new volunteering opportunity is created',
                'opportunity.updated' => 'Triggered when a volunteering opportunity is updated',
                'opportunity.deleted' => 'Triggered when a volunteering opportunity is deleted',
                'application.submitted' => 'Triggered when a volunteer submits an application',
                'application.reviewed' => 'Triggered when an application is reviewed',
                'application.accepted' => 'Triggered when an application is accepted',
                'application.rejected' => 'Triggered when an application is rejected',
                'time_log.submitted' => 'Triggered when a volunteer logs their hours',
                'time_log.approved' => 'Triggered when logged hours are approved',
                'time_log.rejected' => 'Triggered when logged hours are rejected',
                'volunteer.assigned' => 'Triggered when a volunteer is assigned to an opportunity',
                'volunteer.completed' => 'Triggered when a volunteer completes their assignment'
            ];

            $formattedEvents = collect($events)->map(function ($event) use ($eventDescriptions) {
                return [
                    'event' => $event,
                    'description' => $eventDescriptions[$event] ?? 'Event description not available'
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedEvents
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available events',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get webhook delivery logs
     */
    public function deliveries(Request $request, Webhook $webhook): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,delivered,failed,exhausted',
            'event' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = $webhook->deliveries();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('event')) {
                $query->where('event', $request->event);
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            $perPage = $request->get('per_page', 20);
            $deliveries = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $data = $deliveries->getCollection()->map(function ($delivery) {
                return [
                    'id' => $delivery->id,
                    'event' => $delivery->event,
                    'status' => $delivery->status,
                    'attempts' => $delivery->attempts,
                    'response_status' => $delivery->response_status,
                    'response_body' => $delivery->response_body,
                    'payload' => $delivery->payload,
                    'context' => $delivery->context,
                    'created_at' => $delivery->created_at->toISOString(),
                    'delivered_at' => $delivery->delivered_at?->toISOString(),
                    'failed_at' => $delivery->failed_at?->toISOString()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $deliveries->currentPage(),
                    'last_page' => $deliveries->lastPage(),
                    'per_page' => $deliveries->perPage(),
                    'total' => $deliveries->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch webhook deliveries',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}