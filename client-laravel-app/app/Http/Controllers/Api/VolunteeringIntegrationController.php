<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VolunteeringIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VolunteeringIntegrationController extends Controller
{
    protected $integrationService;

    public function __construct(VolunteeringIntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    /**
     * Get organization volunteering statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id ?? $request->input('organization_id');
        
        if (!$organizationId) {
            return response()->json(['error' => 'Organization ID required'], 400);
        }

        $stats = $this->integrationService->getOrganizationStats($organizationId);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get volunteering data for API consumption
     */
    public function getData(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id ?? $request->input('organization_id');
        $type = $request->input('type', 'opportunities');

        if (!$organizationId) {
            return response()->json(['error' => 'Organization ID required'], 400);
        }

        $data = $this->integrationService->getApiData($organizationId, $type);

        return response()->json([
            'success' => true,
            'data' => $data,
            'type' => $type
        ]);
    }

    /**
     * Export volunteering data
     */
    public function export(Request $request)
    {
        $organizationId = $request->user()->organization_id ?? $request->input('organization_id');
        $format = $request->input('format', 'csv');
        $type = $request->input('type', 'opportunities');

        if (!$organizationId) {
            return response()->json(['error' => 'Organization ID required'], 400);
        }

        $export = $this->integrationService->exportData($organizationId, $format, $type);

        if ($format === 'csv') {
            return response($export)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=\"volunteering_{$type}.csv\"");
        }

        return $export;
    }

    /**
     * Generate social media content
     */
    public function generateSocialContent(Request $request): JsonResponse
    {
        $opportunityId = $request->input('opportunity_id');

        if (!$opportunityId) {
            return response()->json(['error' => 'Opportunity ID required'], 400);
        }

        $content = $this->integrationService->generateSocialContent($opportunityId);

        if (!$content) {
            return response()->json(['error' => 'Opportunity not found'], 404);
        }

        return response()->json([
            'success' => true,
            'content' => $content
        ]);
    }

    /**
     * Get widget data for dashboard
     */
    public function getWidgetData(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id ?? $request->input('organization_id');
        $widget = $request->input('widget', 'overview');

        if (!$organizationId) {
            return response()->json(['error' => 'Organization ID required'], 400);
        }

        $data = $this->integrationService->getWidgetData($organizationId, $widget);

        return response()->json([
            'success' => true,
            'widget' => $widget,
            'data' => $data
        ]);
    }

    /**
     * Sync with event management system
     */
    public function syncEvents(Request $request): JsonResponse
    {
        $organizationId = $request->user()->organization_id ?? $request->input('organization_id');

        if (!$organizationId) {
            return response()->json(['error' => 'Organization ID required'], 400);
        }

        $result = $this->integrationService->syncWithEvents($organizationId);

        return response()->json([
            'success' => true,
            'sync_result' => $result
        ]);
    }
}