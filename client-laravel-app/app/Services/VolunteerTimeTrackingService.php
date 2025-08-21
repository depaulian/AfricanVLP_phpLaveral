<?php

namespace App\Services;

use App\Models\VolunteerAssignment;
use App\Models\VolunteerTimeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class VolunteerTimeTrackingService
{
    /**
     * Log volunteer time
     */
    public function logTime(VolunteerAssignment $assignment, array $data): VolunteerTimeLog
    {
        // Validate assignment is active
        if (!$assignment->isActive()) {
            throw new \Exception('Cannot log time for inactive assignment.');
        }

        // Validate date is not in the future
        $date = Carbon::parse($data['date']);
        if ($date->isFuture()) {
            throw new \Exception('Cannot log time for future dates.');
        }

        // Validate time range
        $startTime = Carbon::parse($data['start_time']);
        $endTime = Carbon::parse($data['end_time']);
        
        if ($endTime->lte($startTime)) {
            throw new \Exception('End time must be after start time.');
        }

        // Calculate hours if not provided
        if (!isset($data['hours'])) {
            $data['hours'] = $endTime->diffInMinutes($startTime) / 60;
        }

        // Check for overlapping time logs
        $this->validateNoOverlap($assignment, $date, $startTime, $endTime);

        return DB::transaction(function () use ($assignment, $data) {
            $timeLog = VolunteerTimeLog::create(array_merge($data, [
                'assignment_id' => $assignment->id,
                'supervisor_approved' => false
            ]));

            // Send notification to supervisor if assigned
            if ($assignment->supervisor_id) {
                // This would typically trigger a notification
            }

            return $timeLog;
        });
    }

    /**
     * Update time log
     */
    public function updateTimeLog(VolunteerTimeLog $timeLog, array $data): VolunteerTimeLog
    {
        // Cannot update approved time logs
        if ($timeLog->isApproved()) {
            throw new \Exception('Cannot update approved time log.');
        }

        // Validate new time range if provided
        if (isset($data['start_time']) || isset($data['end_time'])) {
            $startTime = Carbon::parse($data['start_time'] ?? $timeLog->start_time);
            $endTime = Carbon::parse($data['end_time'] ?? $timeLog->end_time);
            
            if ($endTime->lte($startTime)) {
                throw new \Exception('End time must be after start time.');
            }

            // Recalculate hours
            $data['hours'] = $endTime->diffInMinutes($startTime) / 60;

            // Check for overlapping time logs (excluding current log)
            $this->validateNoOverlap(
                $timeLog->assignment,
                Carbon::parse($data['date'] ?? $timeLog->date),
                $startTime,
                $endTime,
                $timeLog->id
            );
        }

        return DB::transaction(function () use ($timeLog, $data) {
            $timeLog->update($data);
            return $timeLog->fresh();
        });
    }

    /**
     * Delete time log
     */
    public function deleteTimeLog(VolunteerTimeLog $timeLog): bool
    {
        // Cannot delete approved time logs
        if ($timeLog->isApproved()) {
            throw new \Exception('Cannot delete approved time log.');
        }

        return $timeLog->delete();
    }

    /**
     * Approve time log
     */
    public function approveTimeLog(VolunteerTimeLog $timeLog, User $supervisor): VolunteerTimeLog
    {
        if ($timeLog->isApproved()) {
            throw new \Exception('Time log is already approved.');
        }

        return DB::transaction(function () use ($timeLog, $supervisor) {
            $timeLog->approve($supervisor);
            return $timeLog->fresh();
        });
    }

    /**
     * Unapprove time log
     */
    public function unapproveTimeLog(VolunteerTimeLog $timeLog): VolunteerTimeLog
    {
        if (!$timeLog->isApproved()) {
            throw new \Exception('Time log is not approved.');
        }

        return DB::transaction(function () use ($timeLog) {
            $timeLog->unapprove();
            return $timeLog->fresh();
        });
    }

    /**
     * Bulk approve time logs
     */
    public function bulkApproveTimeLogs(array $timeLogIds, User $supervisor): array
    {
        $results = ['approved' => 0, 'errors' => []];

        $timeLogs = VolunteerTimeLog::whereIn('id', $timeLogIds)
            ->where('supervisor_approved', false)
            ->get();

        DB::transaction(function () use ($timeLogs, $supervisor, &$results) {
            foreach ($timeLogs as $timeLog) {
                try {
                    $timeLog->approve($supervisor);
                    $results['approved']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Time log {$timeLog->id}: {$e->getMessage()}";
                }
            }
        });

        return $results;
    }

    /**
     * Get time logs for assignment
     */
    public function getTimeLogsForAssignment(
        VolunteerAssignment $assignment,
        array $filters = []
    ): Collection {
        $query = $assignment->timeLogs()->with('approver');

        if (isset($filters['approved'])) {
            if ($filters['approved']) {
                $query->approved();
            } else {
                $query->pendingApproval();
            }
        }

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Get time logs requiring approval for supervisor
     */
    public function getPendingApprovals(User $supervisor): Collection
    {
        return VolunteerTimeLog::with(['assignment.application.opportunity', 'assignment.application.user'])
            ->whereHas('assignment', function ($query) use ($supervisor) {
                $query->where('supervisor_id', $supervisor->id);
            })
            ->pendingApproval()
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * Generate time report for assignment
     */
    public function generateTimeReport(VolunteerAssignment $assignment, array $options = []): array
    {
        $timeLogs = $assignment->timeLogs()->approved()->orderBy('date')->get();
        
        $report = [
            'assignment_id' => $assignment->id,
            'volunteer_name' => $assignment->application->user->name,
            'opportunity_title' => $assignment->application->opportunity->title,
            'organization_name' => $assignment->application->opportunity->organization->name,
            'period' => [
                'start_date' => $assignment->start_date->format('Y-m-d'),
                'end_date' => $assignment->end_date?->format('Y-m-d') ?? 'Ongoing'
            ],
            'summary' => [
                'total_hours' => $timeLogs->sum('hours'),
                'total_days' => $timeLogs->count(),
                'hours_committed' => $assignment->hours_committed,
                'completion_percentage' => $assignment->completion_percentage
            ],
            'time_logs' => []
        ];

        // Group by month if requested
        if ($options['group_by'] === 'month') {
            $groupedLogs = $timeLogs->groupBy(function ($log) {
                return $log->date->format('Y-m');
            });

            foreach ($groupedLogs as $month => $logs) {
                $report['time_logs'][] = [
                    'period' => $month,
                    'total_hours' => $logs->sum('hours'),
                    'days_worked' => $logs->count(),
                    'details' => $logs->map(function ($log) {
                        return [
                            'date' => $log->date->format('Y-m-d'),
                            'hours' => $log->hours,
                            'activity' => $log->activity_description,
                            'time_range' => $log->formatted_time_range
                        ];
                    })->toArray()
                ];
            }
        } else {
            // Detailed daily logs
            $report['time_logs'] = $timeLogs->map(function ($log) {
                return [
                    'date' => $log->date->format('Y-m-d'),
                    'start_time' => $log->start_time->format('H:i'),
                    'end_time' => $log->end_time->format('H:i'),
                    'hours' => $log->hours,
                    'activity_description' => $log->activity_description,
                    'approved_at' => $log->approved_at?->format('Y-m-d H:i:s'),
                    'approved_by' => $log->approver?->name
                ];
            })->toArray();
        }

        return $report;
    }

    /**
     * Get volunteer hours summary
     */
    public function getVolunteerHoursSummary(User $volunteer, array $filters = []): array
    {
        $query = VolunteerTimeLog::whereHas('assignment.application', function ($q) use ($volunteer) {
            $q->where('user_id', $volunteer->id);
        })->approved();

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        if (!empty($filters['organization_id'])) {
            $query->whereHas('assignment.application.opportunity', function ($q) use ($filters) {
                $q->where('organization_id', $filters['organization_id']);
            });
        }

        $timeLogs = $query->with([
            'assignment.application.opportunity.organization',
            'assignment.application.opportunity.category'
        ])->get();

        $summary = [
            'total_hours' => $timeLogs->sum('hours'),
            'total_days' => $timeLogs->count(),
            'organizations' => $timeLogs->groupBy('assignment.application.opportunity.organization.name')
                ->map(function ($logs, $orgName) {
                    return [
                        'name' => $orgName,
                        'hours' => $logs->sum('hours'),
                        'days' => $logs->count()
                    ];
                })->values()->toArray(),
            'categories' => $timeLogs->groupBy('assignment.application.opportunity.category.name')
                ->map(function ($logs, $categoryName) {
                    return [
                        'name' => $categoryName,
                        'hours' => $logs->sum('hours'),
                        'days' => $logs->count()
                    ];
                })->values()->toArray(),
            'monthly_breakdown' => $timeLogs->groupBy(function ($log) {
                return $log->date->format('Y-m');
            })->map(function ($logs, $month) {
                return [
                    'month' => $month,
                    'hours' => $logs->sum('hours'),
                    'days' => $logs->count()
                ];
            })->values()->toArray()
        ];

        return $summary;
    }

    /**
     * Validate no overlapping time logs
     */
    private function validateNoOverlap(
        VolunteerAssignment $assignment,
        Carbon $date,
        Carbon $startTime,
        Carbon $endTime,
        ?int $excludeLogId = null
    ): void {
        $query = $assignment->timeLogs()
            ->where('date', $date->format('Y-m-d'))
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($subQ) use ($startTime, $endTime) {
                    // New log starts during existing log
                    $subQ->where('start_time', '<=', $startTime->format('H:i:s'))
                         ->where('end_time', '>', $startTime->format('H:i:s'));
                })
                ->orWhere(function ($subQ) use ($startTime, $endTime) {
                    // New log ends during existing log
                    $subQ->where('start_time', '<', $endTime->format('H:i:s'))
                         ->where('end_time', '>=', $endTime->format('H:i:s'));
                })
                ->orWhere(function ($subQ) use ($startTime, $endTime) {
                    // New log completely contains existing log
                    $subQ->where('start_time', '>=', $startTime->format('H:i:s'))
                         ->where('end_time', '<=', $endTime->format('H:i:s'));
                });
            });

        if ($excludeLogId) {
            $query->where('id', '!=', $excludeLogId);
        }

        if ($query->exists()) {
            throw new \Exception('Time log overlaps with existing time entry.');
        }
    }

    /**
     * Get organization time tracking statistics
     */
    public function getOrganizationTimeStats(int $organizationId, array $filters = []): array
    {
        $query = VolunteerTimeLog::whereHas('assignment.application.opportunity', function ($q) use ($organizationId) {
            $q->where('organization_id', $organizationId);
        })->approved();

        if (!empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        $timeLogs = $query->with([
            'assignment.application.user',
            'assignment.application.opportunity'
        ])->get();

        return [
            'total_hours' => $timeLogs->sum('hours'),
            'total_volunteers' => $timeLogs->pluck('assignment.application.user.id')->unique()->count(),
            'total_opportunities' => $timeLogs->pluck('assignment.application.opportunity.id')->unique()->count(),
            'average_hours_per_volunteer' => $timeLogs->isNotEmpty() 
                ? $timeLogs->sum('hours') / $timeLogs->pluck('assignment.application.user.id')->unique()->count()
                : 0,
            'top_volunteers' => $timeLogs->groupBy('assignment.application.user.id')
                ->map(function ($logs) {
                    $user = $logs->first()->assignment->application->user;
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'hours' => $logs->sum('hours'),
                        'days' => $logs->count()
                    ];
                })
                ->sortByDesc('hours')
                ->take(10)
                ->values()
                ->toArray()
        ];
    }

    /**
     * Log hours using the new interface
     */
    public function logHours(VolunteerAssignment $assignment, array $data): VolunteerTimeLog
    {
        return $this->logTime($assignment, $data);
    }

    /**
     * Export time logs to CSV
     */
    public function exportTimeLogsToCSV($timeLogs, User $user): \Illuminate\Http\Response
    {
        $filename = "volunteer_time_logs_{$user->id}_" . date('Y-m-d') . ".csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($timeLogs) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Date',
                'Assignment',
                'Organization',
                'Start Time',
                'End Time',
                'Hours',
                'Activity Description',
                'Status',
                'Approved By',
                'Approved At'
            ]);

            // CSV data
            foreach ($timeLogs as $log) {
                fputcsv($file, [
                    $log->date->format('Y-m-d'),
                    $log->assignment->opportunity->title,
                    $log->assignment->opportunity->organization->name,
                    $log->start_time->format('H:i'),
                    $log->end_time->format('H:i'),
                    number_format($log->hours, 2),
                    $log->activity_description ?: '',
                    $log->supervisor_approved ? 'Approved' : 'Pending',
                    $log->approver ? $log->approver->name : '',
                    $log->approved_at ? $log->approved_at->format('Y-m-d H:i') : ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get user analytics with filters
     */
    public function getUserAnalytics(User $user, array $filters = []): array
    {
        $query = VolunteerTimeLog::whereHas('assignment.application', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        if (isset($filters['assignment_id'])) {
            $query->where('assignment_id', $filters['assignment_id']);
        }

        $timeLogs = $query->with(['assignment.opportunity.organization'])->get();

        return [
            'total_entries' => $timeLogs->count(),
            'total_hours' => $timeLogs->sum('hours'),
            'approved_hours' => $timeLogs->where('supervisor_approved', true)->sum('hours'),
            'pending_hours' => $timeLogs->where('supervisor_approved', false)->sum('hours'),
            'hours_by_month' => $timeLogs->groupBy(function ($log) {
                return $log->date->format('Y-m');
            })->map->sum('hours'),
            'hours_by_organization' => $timeLogs->groupBy('assignment.opportunity.organization.name')
                ->map->sum('hours'),
            'entries_by_day_of_week' => $timeLogs->groupBy(function ($log) {
                return $log->date->format('l');
            })->map->count(),
            'average_hours_per_session' => $timeLogs->count() > 0 ? $timeLogs->avg('hours') : 0,
        ];
    }

    /**
     * Generate user report
     */
    public function generateUserReport(User $user, array $options = []): \Illuminate\Http\Response
    {
        $format = $options['format'] ?? 'pdf';
        
        // Build query
        $query = VolunteerTimeLog::whereHas('assignment.application', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['assignment.opportunity.organization', 'assignment.supervisor']);

        // Apply filters
        if (isset($options['date_from'])) {
            $query->where('date', '>=', $options['date_from']);
        }

        if (isset($options['date_to'])) {
            $query->where('date', '<=', $options['date_to']);
        }

        if (isset($options['assignment_ids'])) {
            $query->whereIn('assignment_id', $options['assignment_ids']);
        }

        $timeLogs = $query->orderBy('date', 'desc')->get();

        if ($format === 'csv') {
            return $this->exportTimeLogsToCSV($timeLogs, $user);
        }

        // For PDF, we would need to implement PDF generation
        // For now, return CSV as fallback
        return $this->exportTimeLogsToCSV($timeLogs, $user);
    }
}