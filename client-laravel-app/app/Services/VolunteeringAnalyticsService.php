<?php

namespace App\Services;

use App\Models\VolunteeringAnalytic;
use App\Models\VolunteerTimeLog;
use App\Models\VolunteerAssignment;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteerApplication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VolunteeringAnalyticsService
{
    /**
     * Calculate and persist analytics for a given period and organization (nullable for global)
     */
    public function calculatePeriodAnalytics(Carbon $startDate, Carbon $endDate, string $periodType, ?int $organizationId = null): void
    {
        // Aggregate core metrics
        $metrics = $this->aggregateCoreMetrics($startDate, $endDate, $organizationId);

        DB::transaction(function () use ($metrics, $startDate, $endDate, $periodType, $organizationId) {
            foreach ($metrics as $metricKey => $data) {
                VolunteeringAnalytic::updateOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'period_type' => $periodType,
                        'period_start' => $startDate->toDateString(),
                        'period_end' => $endDate->toDateString(),
                        'metric_type' => $metricKey,
                    ],
                    [
                        'metric_category' => $data['category'] ?? null,
                        'value' => $data['value'] ?? 0,
                        'metadata' => $data['metadata'] ?? [],
                        'calculated_at' => now(),
                    ]
                );
            }
        });
    }

    /**
     * Dashboard data for UI
     */
    public function getDashboardData(?int $organizationId, array $filters = []): array
    {
        [$start, $end] = $this->resolvePeriod($filters['period'] ?? 'last_30_days');

        // Pull stored analytics where available
        $stored = VolunteeringAnalytic::query()
            ->when($organizationId, fn($q) => $q->where('organization_id', $organizationId))
            ->whereDate('period_start', $start->toDateString())
            ->whereDate('period_end', $end->toDateString())
            ->get()
            ->keyBy('metric_type');

        // If not stored yet, compute on the fly for responsiveness
        $core = $this->aggregateCoreMetrics($start, $end, $organizationId);

        $summary = [
            'volunteer_count' => $this->formatMetric($core['volunteer_count']['value'] ?? 0, 'count'),
            'hours_logged' => $this->formatMetric($core['hours_logged']['value'] ?? 0, 'hours'),
            'active_assignments' => $this->formatMetric($core['active_assignments']['value'] ?? 0, 'count'),
            'opportunities_open' => $this->formatMetric($core['opportunities_open']['value'] ?? 0, 'count'),
            'applications_received' => $this->formatMetric($core['applications_received']['value'] ?? 0, 'count'),
            'completion_rate' => $this->formatMetric($core['completion_rate']['value'] ?? 0, 'percent'),
        ];

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $filters['period'] ?? 'last_30_days',
            ],
            'summary_metrics' => $summary,
            'raw' => $core,
        ];
    }

    /**
     * Compare multiple organizations over a date range for specified metrics
     */
    public function getOrganizationComparison(array $organizationIds, array $metrics, Carbon $startDate, Carbon $endDate): array
    {
        $results = [];
        foreach ($organizationIds as $orgId) {
            $core = $this->aggregateCoreMetrics($startDate, $endDate, (int)$orgId);
            $row = ['organization_id' => (int)$orgId];
            foreach ($metrics as $metric) {
                $row[$metric] = $core[$metric]['value'] ?? 0;
            }
            $results[] = $row;
        }
        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'metrics' => $metrics,
            'data' => $results,
        ];
    }

    /**
     * Provide data for scheduled or on-demand reports
     */
    public function generateReportData(string $reportType, array $config, ?int $organizationId = null): array
    {
        [$start, $end] = $this->resolveReportRange($config);
        $core = $this->aggregateCoreMetrics($start, $end, $organizationId);

        return [
            'report_type' => $reportType,
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'organization_id' => $organizationId,
            'summary_metrics' => [
                'volunteer_count' => $this->formatMetric($core['volunteer_count']['value'] ?? 0, 'count'),
                'hours_logged' => $this->formatMetric($core['hours_logged']['value'] ?? 0, 'hours'),
                'active_assignments' => $this->formatMetric($core['active_assignments']['value'] ?? 0, 'count'),
                'completion_rate' => $this->formatMetric($core['completion_rate']['value'] ?? 0, 'percent'),
            ],
            'details' => $core,
        ];
    }

    // ---------------------
    // Internal helpers
    // ---------------------

    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'last_7_days' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'last_30_days' => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'last_quarter' => [now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [now()->subDays(30)->startOfDay(), now()->endOfDay()],
        };
    }

    private function resolveReportRange(array $config): array
    {
        if (!empty($config['start']) && !empty($config['end'])) {
            return [Carbon::parse($config['start'])->startOfDay(), Carbon::parse($config['end'])->endOfDay()];
        }
        $period = $config['period'] ?? 'last_30_days';
        return $this->resolvePeriod($period);
    }

    /**
     * Core metric aggregation used across dashboards, comparisons and reports
     */
    private function aggregateCoreMetrics(Carbon $start, Carbon $end, ?int $organizationId = null): array
    {
        // Hours from approved time logs within date range
        $timeLogs = VolunteerTimeLog::query()
            ->where('supervisor_approved', true)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($organizationId, function ($q) use ($organizationId) {
                $q->whereHas('assignment.opportunity', function ($qq) use ($organizationId) {
                    $qq->where('organization_id', $organizationId);
                });
            });
        $hoursLogged = (float)$timeLogs->sum('hours');

        // Active assignments in range (belongs to org if provided)
        $assignmentsQuery = VolunteerAssignment::query()
            ->where('status', 'active')
            ->when($organizationId, function ($q) use ($organizationId) {
                $q->whereHas('application.opportunity', function ($qq) use ($organizationId) {
                    $qq->where('organization_id', $organizationId);
                });
            });
        $activeAssignments = (int)$assignmentsQuery->count();

        // Completed assignments in range (for completion rate)
        $completedAssignments = (int)VolunteerAssignment::query()
            ->where('status', 'completed')
            ->when($organizationId, function ($q) use ($organizationId) {
                $q->whereHas('application.opportunity', function ($qq) use ($organizationId) {
                    $qq->where('organization_id', $organizationId);
                });
            })
            ->count();

        $totalEnded = (int)VolunteerAssignment::query()
            ->whereIn('status', ['completed', 'terminated'])
            ->when($organizationId, function ($q) use ($organizationId) {
                $q->whereHas('application.opportunity', function ($qq) use ($organizationId) {
                    $qq->where('organization_id', $organizationId);
                });
            })
            ->count();
        $completionRate = $totalEnded > 0 ? round(($completedAssignments / $totalEnded) * 100, 2) : 0.0;

        // Opportunities open during range
        $opportunitiesOpen = (int)VolunteeringOpportunity::query()
            ->where('status', 'published')
            ->when($organizationId, fn($q) => $q->where('organization_id', $organizationId))
            ->where(function ($q) use ($start, $end) {
                $q->whereNull('application_deadline')
                  ->orWhereBetween('application_deadline', [$start->toDateString(), $end->toDateString()])
                  ->orWhere(function ($qq) use ($start, $end) {
                      $qq->whereNull('start_date')
                         ->orWhereBetween('start_date', [$start->toDateString(), $end->toDateString()]);
                  });
            })
            ->count();

        // Applications received during range
        $applicationsReceived = (int)VolunteerApplication::query()
            ->when($organizationId, function ($q) use ($organizationId) {
                $q->whereHas('opportunity', function ($qq) use ($organizationId) {
                    $qq->where('organization_id', $organizationId);
                });
            })
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // Volunteer count: number of distinct volunteers who logged approved hours in range
        $volunteerCount = (int)VolunteerTimeLog::query()
            ->where('supervisor_approved', true)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($organizationId, function ($q) use ($organizationId) {
                $q->whereHas('assignment.opportunity', function ($qq) use ($organizationId) {
                    $qq->where('organization_id', $organizationId);
                });
            })
            ->distinct('assignment_id')
            ->count('assignment_id');

        return [
            'hours_logged' => [
                'category' => 'performance',
                'value' => $hoursLogged,
            ],
            'active_assignments' => [
                'category' => 'engagement',
                'value' => $activeAssignments,
            ],
            'applications_received' => [
                'category' => 'growth',
                'value' => $applicationsReceived,
            ],
            'opportunities_open' => [
                'category' => 'growth',
                'value' => $opportunitiesOpen,
            ],
            'completion_rate' => [
                'category' => 'retention',
                'value' => $completionRate,
            ],
            'volunteer_count' => [
                'category' => 'engagement',
                'value' => $volunteerCount,
            ],
        ];
    }

    private function formatMetric($value, string $type): array
    {
        return match ($type) {
            'hours' => [
                'value' => (float)$value,
                'formatted_value' => number_format((float)$value, 2) . ' hrs',
            ],
            'percent' => [
                'value' => (float)$value,
                'formatted_value' => number_format((float)$value, 2) . '%',
            ],
            default => [
                'value' => (int)$value,
                'formatted_value' => number_format((int)$value),
            ],
        };
    }
}
