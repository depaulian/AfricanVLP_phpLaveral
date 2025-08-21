<?php

namespace App\Services;

use App\Models\VolunteeringOpportunity;
use App\Models\VolunteerApplication;
use App\Models\VolunteerTimeLog;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VolunteeringIntegrationService
{
    /**
     * Get volunteering statistics for organization dashboard
     */
    public function getOrganizationStats($organizationId)
    {
        return Cache::remember("org_volunteering_stats_{$organizationId}", 3600, function () use ($organizationId) {
            return [
                'active_opportunities' => VolunteeringOpportunity::where('organization_id', $organizationId)
                    ->where('status', 'active')
                    ->count(),
                'total_applications' => VolunteerApplication::whereHas('opportunity', function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId);
                })->count(),
                'active_volunteers' => VolunteerApplication::whereHas('opportunity', function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId);
                })->where('status', 'accepted')->count(),
                'total_hours' => VolunteerTimeLog::whereHas('assignment.application.opportunity', function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId);
                })->where('status', 'approved')->sum('hours'),
                'pending_applications' => VolunteerApplication::whereHas('opportunity', function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId);
                })->where('status', 'pending')->count(),
            ];
        });
    }

    /**
     * Get volunteering data for external API
     */
    public function getApiData($organizationId, $type = 'opportunities')
    {
        switch ($type) {
            case 'opportunities':
                return VolunteeringOpportunity::where('organization_id', $organizationId)
                    ->with(['category', 'applications'])
                    ->get()
                    ->map(function ($opportunity) {
                        return [
                            'id' => $opportunity->id,
                            'title' => $opportunity->title,
                            'description' => $opportunity->description,
                            'category' => $opportunity->category->name,
                            'location' => $opportunity->location,
                            'start_date' => $opportunity->start_date,
                            'end_date' => $opportunity->end_date,
                            'volunteers_needed' => $opportunity->volunteers_needed,
                            'applications_count' => $opportunity->applications->count(),
                            'status' => $opportunity->status,
                        ];
                    });

            case 'applications':
                return VolunteerApplication::whereHas('opportunity', function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId);
                })
                ->with(['user', 'opportunity'])
                ->get()
                ->map(function ($application) {
                    return [
                        'id' => $application->id,
                        'volunteer_name' => $application->user->name,
                        'volunteer_email' => $application->user->email,
                        'opportunity_title' => $application->opportunity->title,
                        'status' => $application->status,
                        'applied_at' => $application->created_at,
                    ];
                });

            case 'time_logs':
                return VolunteerTimeLog::whereHas('assignment.application.opportunity', function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId);
                })
                ->with(['assignment.application.user', 'assignment.application.opportunity'])
                ->get()
                ->map(function ($timeLog) {
                    return [
                        'id' => $timeLog->id,
                        'volunteer_name' => $timeLog->assignment->application->user->name,
                        'opportunity_title' => $timeLog->assignment->application->opportunity->title,
                        'date' => $timeLog->date,
                        'hours' => $timeLog->hours,
                        'status' => $timeLog->status,
                        'description' => $timeLog->description,
                    ];
                });

            default:
                return [];
        }
    }

    /**
     * Export volunteering data
     */
    public function exportData($organizationId, $format = 'csv', $type = 'opportunities')
    {
        $data = $this->getApiData($organizationId, $type);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCsv($data, $type);
            case 'json':
                return response()->json($data);
            default:
                return $data;
        }
    }

    /**
     * Convert data to CSV format
     */
    private function exportToCsv($data, $type)
    {
        if (empty($data)) {
            return '';
        }

        $csv = '';
        $headers = array_keys($data[0]);
        $csv .= implode(',', $headers) . "\n";

        foreach ($data as $row) {
            $csv .= implode(',', array_map(function ($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $row)) . "\n";
        }

        return $csv;
    }

    /**
     * Create social media content for opportunity
     */
    public function generateSocialContent($opportunityId)
    {
        $opportunity = VolunteeringOpportunity::with(['organization', 'category'])->find($opportunityId);
        
        if (!$opportunity) {
            return null;
        }

        return [
            'twitter' => [
                'text' => "🌟 Volunteer Opportunity: {$opportunity->title} with {$opportunity->organization->name}! Join us in making a difference. #Volunteer #Community #MakeADifference",
                'hashtags' => ['Volunteer', 'Community', 'MakeADifference', $opportunity->category->name],
            ],
            'facebook' => [
                'title' => "Volunteer with {$opportunity->organization->name}",
                'description' => "{$opportunity->title}\n\n{$opportunity->description}\n\nLocation: {$opportunity->location}\nStart Date: {$opportunity->start_date->format('M j, Y')}",
                'call_to_action' => 'Apply Now',
            ],
            'linkedin' => [
                'title' => "Professional Volunteering Opportunity",
                'description' => "Expand your network and skills while giving back to the community. {$opportunity->title} with {$opportunity->organization->name}.",
                'benefits' => ['networking', 'skill_development', 'community_impact'],
            ],
        ];
    }

    /**
     * Get widget data for organization dashboard
     */
    public function getWidgetData($organizationId, $widget = 'overview')
    {
        switch ($widget) {
            case 'overview':
                return $this->getOrganizationStats($organizationId);

            case 'recent_applications':
                return VolunteerApplication::whereHas('opportunity', function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId);
                })
                ->with(['user', 'opportunity'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            case 'upcoming_opportunities':
                return VolunteeringOpportunity::where('organization_id', $organizationId)
                    ->where('start_date', '>', now())
                    ->orderBy('start_date')
                    ->limit(5)
                    ->get();

            case 'volunteer_hours':
                return VolunteerTimeLog::whereHas('assignment.application.opportunity', function ($query) use ($organizationId) {
                    $query->where('organization_id', $organizationId);
                })
                ->where('status', 'approved')
                ->selectRaw('DATE(date) as date, SUM(hours) as total_hours')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get();

            default:
                return [];
        }
    }

    /**
     * Sync with event management system
     */
    public function syncWithEvents($organizationId)
    {
        // This would integrate with an event management system
        // For now, we'll return a placeholder response
        return [
            'synced_opportunities' => 0,
            'created_events' => 0,
            'updated_events' => 0,
            'last_sync' => now(),
        ];
    }
}