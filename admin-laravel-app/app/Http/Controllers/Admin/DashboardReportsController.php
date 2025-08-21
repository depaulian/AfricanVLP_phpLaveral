<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Models\Event;
use App\Models\VolunteeringOpportunity;
use App\Models\VolunteerApplication;
use App\Models\VolunteerTimeLog;
use App\Models\SupportTicket;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardReportsController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display the reports dashboard
     */
    public function index(): View
    {
        $quickStats = $this->getQuickStats();
        $recentActivity = $this->getRecentActivity();
        
        return view('admin.reports.index', compact('quickStats', 'recentActivity'));
    }

    /**
     * Generate volunteers report
     */
    public function volunteersReport(Request $request): View|JsonResponse
    {
        $dateRange = $this->getDateRange($request);
        
        $stats = [
            'total_volunteers' => User::where('created', '>=', $dateRange['start'])
                                    ->where('created', '<=', $dateRange['end'])
                                    ->count(),
            'active_volunteers' => User::where('status', 'active')
                                     ->whereHas('volunteerApplications', function($q) use ($dateRange) {
                                         $q->where('status', 'approved')
                                           ->where('created', '>=', $dateRange['start']);
                                     })
                                     ->count(),
            'total_applications' => VolunteerApplication::where('created', '>=', $dateRange['start'])
                                                       ->where('created', '<=', $dateRange['end'])
                                                       ->count(),
            'approved_applications' => VolunteerApplication::where('status', 'approved')
                                                          ->where('created', '>=', $dateRange['start'])
                                                          ->where('created', '<=', $dateRange['end'])
                                                          ->count(),
            'total_hours_logged' => VolunteerTimeLog::where('date', '>=', $dateRange['start'])
                                                   ->where('date', '<=', $dateRange['end'])
                                                   ->sum('hours'),
            'avg_hours_per_volunteer' => 0,
        ];

        // Calculate average hours per volunteer
        $activeVolunteersWithHours = VolunteerTimeLog::where('date', '>=', $dateRange['start'])
                                                    ->where('date', '<=', $dateRange['end'])
                                                    ->distinct('user_id')
                                                    ->count('user_id');
        
        if ($activeVolunteersWithHours > 0) {
            $stats['avg_hours_per_volunteer'] = round($stats['total_hours_logged'] / $activeVolunteersWithHours, 2);
        }

        // Volunteers by month
        $volunteersByMonth = User::selectRaw('DATE_FORMAT(created, "%Y-%m") as month, COUNT(*) as count')
                                ->where('created', '>=', $dateRange['start'])
                                ->where('created', '<=', $dateRange['end'])
                                ->groupBy('month')
                                ->orderBy('month')
                                ->get()
                                ->pluck('count', 'month');

        // Top organizations by volunteer count
        $topOrganizations = Organization::withCount(['users' => function($q) use ($dateRange) {
                                            $q->wherePivot('joined_date', '>=', $dateRange['start'])
                                              ->wherePivot('joined_date', '<=', $dateRange['end']);
                                        }])
                                       ->orderBy('users_count', 'desc')
                                       ->limit(10)
                                       ->get();

        // Volunteer applications by status
        $applicationsByStatus = VolunteerApplication::selectRaw('status, COUNT(*) as count')
                                                   ->where('created', '>=', $dateRange['start'])
                                                   ->where('created', '<=', $dateRange['end'])
                                                   ->groupBy('status')
                                                   ->pluck('count', 'status');

        // Hours logged by month
        $hoursByMonth = VolunteerTimeLog::selectRaw('DATE_FORMAT(date, "%Y-%m") as month, SUM(hours) as total_hours')
                                       ->where('date', '>=', $dateRange['start'])
                                       ->where('date', '<=', $dateRange['end'])
                                       ->groupBy('month')
                                       ->orderBy('month')
                                       ->get()
                                       ->pluck('total_hours', 'month');

        $data = compact(
            'stats', 'volunteersByMonth', 'topOrganizations', 
            'applicationsByStatus', 'hoursByMonth', 'dateRange'
        );

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.reports.volunteers', $data);
    }

    /**
     * Generate organizations report
     */
    public function organizationsReport(Request $request): View|JsonResponse
    {
        $dateRange = $this->getDateRange($request);
        
        $stats = [
            'total_organizations' => Organization::where('created', '>=', $dateRange['start'])
                                                ->where('created', '<=', $dateRange['end'])
                                                ->count(),
            'active_organizations' => Organization::where('status', 'active')
                                                 ->where('created', '>=', $dateRange['start'])
                                                 ->where('created', '<=', $dateRange['end'])
                                                 ->count(),
            'total_members' => DB::table('organization_users')
                                ->where('joined_date', '>=', $dateRange['start'])
                                ->where('joined_date', '<=', $dateRange['end'])
                                ->count(),
            'total_events' => Event::where('created', '>=', $dateRange['start'])
                                  ->where('created', '<=', $dateRange['end'])
                                  ->count(),
            'avg_members_per_org' => 0,
            'avg_events_per_org' => 0,
        ];

        // Calculate averages
        if ($stats['total_organizations'] > 0) {
            $stats['avg_members_per_org'] = round($stats['total_members'] / $stats['total_organizations'], 2);
            $stats['avg_events_per_org'] = round($stats['total_events'] / $stats['total_organizations'], 2);
        }

        // Organizations by month
        $organizationsByMonth = Organization::selectRaw('DATE_FORMAT(created, "%Y-%m") as month, COUNT(*) as count')
                                           ->where('created', '>=', $dateRange['start'])
                                           ->where('created', '<=', $dateRange['end'])
                                           ->groupBy('month')
                                           ->orderBy('month')
                                           ->get()
                                           ->pluck('count', 'month');

        // Organizations by category
        $organizationsByCategory = Organization::selectRaw('category_of_organization_id, COUNT(*) as count')
                                              ->with('categoryOfOrganization')
                                              ->where('created', '>=', $dateRange['start'])
                                              ->where('created', '<=', $dateRange['end'])
                                              ->groupBy('category_of_organization_id')
                                              ->get()
                                              ->mapWithKeys(function($item) {
                                                  $categoryName = $item->categoryOfOrganization->name ?? 'Uncategorized';
                                                  return [$categoryName => $item->count];
                                              });

        // Organizations by country
        $organizationsByCountry = Organization::selectRaw('country_id, COUNT(*) as count')
                                             ->with('country')
                                             ->where('created', '>=', $dateRange['start'])
                                             ->where('created', '<=', $dateRange['end'])
                                             ->groupBy('country_id')
                                             ->orderBy('count', 'desc')
                                             ->limit(10)
                                             ->get()
                                             ->mapWithKeys(function($item) {
                                                 $countryName = $item->country->name ?? 'Unknown';
                                                 return [$countryName => $item->count];
                                             });

        // Most active organizations (by member count)
        $mostActiveOrganizations = Organization::withCount('users')
                                              ->orderBy('users_count', 'desc')
                                              ->limit(10)
                                              ->get();

        $data = compact(
            'stats', 'organizationsByMonth', 'organizationsByCategory',
            'organizationsByCountry', 'mostActiveOrganizations', 'dateRange'
        );

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.reports.organizations', $data);
    }

    /**
     * Generate events report
     */
    public function eventsReport(Request $request): View|JsonResponse
    {
        $dateRange = $this->getDateRange($request);
        
        $stats = [
            'total_events' => Event::where('created', '>=', $dateRange['start'])
                                  ->where('created', '<=', $dateRange['end'])
                                  ->count(),
            'upcoming_events' => Event::where('start_date', '>', now())
                                     ->where('created', '>=', $dateRange['start'])
                                     ->where('created', '<=', $dateRange['end'])
                                     ->count(),
            'ongoing_events' => Event::where('start_date', '<=', now())
                                    ->where('end_date', '>=', now())
                                    ->where('created', '>=', $dateRange['start'])
                                    ->where('created', '<=', $dateRange['end'])
                                    ->count(),
            'completed_events' => Event::where('end_date', '<', now())
                                      ->where('created', '>=', $dateRange['start'])
                                      ->where('created', '<=', $dateRange['end'])
                                      ->count(),
            'total_participants' => Event::where('created', '>=', $dateRange['start'])
                                        ->where('created', '<=', $dateRange['end'])
                                        ->sum('current_participants'),
            'avg_participants_per_event' => 0,
        ];

        // Calculate average participants
        if ($stats['total_events'] > 0) {
            $stats['avg_participants_per_event'] = round($stats['total_participants'] / $stats['total_events'], 2);
        }

        // Events by month
        $eventsByMonth = Event::selectRaw('DATE_FORMAT(start_date, "%Y-%m") as month, COUNT(*) as count')
                             ->where('start_date', '>=', $dateRange['start'])
                             ->where('start_date', '<=', $dateRange['end'])
                             ->groupBy('month')
                             ->orderBy('month')
                             ->get()
                             ->pluck('count', 'month');

        // Events by status
        $eventsByStatus = Event::selectRaw('status, COUNT(*) as count')
                              ->where('created', '>=', $dateRange['start'])
                              ->where('created', '<=', $dateRange['end'])
                              ->groupBy('status')
                              ->pluck('count', 'status');

        // Events by country
        $eventsByCountry = Event::selectRaw('country_id, COUNT(*) as count')
                               ->with('country')
                               ->where('created', '>=', $dateRange['start'])
                               ->where('created', '<=', $dateRange['end'])
                               ->whereNotNull('country_id')
                               ->groupBy('country_id')
                               ->orderBy('count', 'desc')
                               ->limit(10)
                               ->get()
                               ->mapWithKeys(function($item) {
                                   $countryName = $item->country->name ?? 'Unknown';
                                   return [$countryName => $item->count];
                               });

        // Most popular events (by participants)
        $popularEvents = Event::where('created', '>=', $dateRange['start'])
                             ->where('created', '<=', $dateRange['end'])
                             ->orderBy('current_participants', 'desc')
                             ->limit(10)
                             ->get();

        $data = compact(
            'stats', 'eventsByMonth', 'eventsByStatus',
            'eventsByCountry', 'popularEvents', 'dateRange'
        );

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.reports.events', $data);
    }

    /**
     * Generate system activity report
     */
    public function systemActivityReport(Request $request): View|JsonResponse
    {
        $dateRange = $this->getDateRange($request);
        
        $stats = [
            'total_activities' => ActivityLog::where('created_at', '>=', $dateRange['start'])
                                            ->where('created_at', '<=', $dateRange['end'])
                                            ->count(),
            'unique_users' => ActivityLog::where('created_at', '>=', $dateRange['start'])
                                        ->where('created_at', '<=', $dateRange['end'])
                                        ->distinct('user_id')
                                        ->count('user_id'),
            'admin_activities' => ActivityLog::whereHas('user', function($q) {
                                                $q->where('is_admin', true);
                                            })
                                            ->where('created_at', '>=', $dateRange['start'])
                                            ->where('created_at', '<=', $dateRange['end'])
                                            ->count(),
            'user_activities' => ActivityLog::whereHas('user', function($q) {
                                               $q->where('is_admin', false);
                                           })
                                           ->where('created_at', '>=', $dateRange['start'])
                                           ->where('created_at', '<=', $dateRange['end'])
                                           ->count(),
        ];

        // Activities by day
        $activitiesByDay = ActivityLog::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                     ->where('created_at', '>=', $dateRange['start'])
                                     ->where('created_at', '<=', $dateRange['end'])
                                     ->groupBy('date')
                                     ->orderBy('date')
                                     ->get()
                                     ->pluck('count', 'date');

        // Activities by action
        $activitiesByAction = ActivityLog::selectRaw('action, COUNT(*) as count')
                                        ->where('created_at', '>=', $dateRange['start'])
                                        ->where('created_at', '<=', $dateRange['end'])
                                        ->groupBy('action')
                                        ->orderBy('count', 'desc')
                                        ->limit(10)
                                        ->pluck('count', 'action');

        // Most active users
        $mostActiveUsers = ActivityLog::selectRaw('user_id, COUNT(*) as activity_count')
                                     ->with('user')
                                     ->where('created_at', '>=', $dateRange['start'])
                                     ->where('created_at', '<=', $dateRange['end'])
                                     ->groupBy('user_id')
                                     ->orderBy('activity_count', 'desc')
                                     ->limit(10)
                                     ->get();

        $data = compact(
            'stats', 'activitiesByDay', 'activitiesByAction',
            'mostActiveUsers', 'dateRange'
        );

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.reports.system-activity', $data);
    }

    /**
     * Generate support tickets report
     */
    public function supportReport(Request $request): View|JsonResponse
    {
        $dateRange = $this->getDateRange($request);
        
        $stats = [
            'total_tickets' => SupportTicket::where('created', '>=', $dateRange['start'])
                                          ->where('created', '<=', $dateRange['end'])
                                          ->count(),
            'open_tickets' => SupportTicket::open()
                                         ->where('created', '>=', $dateRange['start'])
                                         ->where('created', '<=', $dateRange['end'])
                                         ->count(),
            'resolved_tickets' => SupportTicket::resolved()
                                             ->where('created', '>=', $dateRange['start'])
                                             ->where('created', '<=', $dateRange['end'])
                                             ->count(),
            'avg_resolution_time' => 0,
        ];

        // Calculate average resolution time
        $resolvedTickets = SupportTicket::whereNotNull('resolved_at')
                                      ->where('created', '>=', $dateRange['start'])
                                      ->where('created', '<=', $dateRange['end'])
                                      ->get();

        if ($resolvedTickets->count() > 0) {
            $totalResolutionTime = $resolvedTickets->sum(function($ticket) {
                return $ticket->created->diffInHours($ticket->resolved_at);
            });
            $stats['avg_resolution_time'] = round($totalResolutionTime / $resolvedTickets->count(), 2);
        }

        // Tickets by priority
        $ticketsByPriority = SupportTicket::selectRaw('priority, COUNT(*) as count')
                                         ->where('created', '>=', $dateRange['start'])
                                         ->where('created', '<=', $dateRange['end'])
                                         ->groupBy('priority')
                                         ->pluck('count', 'priority');

        // Tickets by category
        $ticketsByCategory = SupportTicket::selectRaw('category, COUNT(*) as count')
                                         ->where('created', '>=', $dateRange['start'])
                                         ->where('created', '<=', $dateRange['end'])
                                         ->groupBy('category')
                                         ->pluck('count', 'category');

        // Tickets by status
        $ticketsByStatus = SupportTicket::selectRaw('status, COUNT(*) as count')
                                       ->where('created', '>=', $dateRange['start'])
                                       ->where('created', '<=', $dateRange['end'])
                                       ->groupBy('status')
                                       ->pluck('count', 'status');

        $data = compact(
            'stats', 'ticketsByPriority', 'ticketsByCategory',
            'ticketsByStatus', 'dateRange'
        );

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('admin.reports.support', $data);
    }

    /**
     * Export report data to CSV
     */
    public function exportReport(Request $request, string $reportType)
    {
        $dateRange = $this->getDateRange($request);
        
        // Log the export activity
        $this->activityLogService->logExport("Dashboard Report: {$reportType}", [
            'report_type' => $reportType,
            'date_range' => $dateRange
        ]);

        $filename = "{$reportType}_report_" . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($reportType, $dateRange) {
            $file = fopen('php://output', 'w');
            
            switch ($reportType) {
                case 'volunteers':
                    $this->exportVolunteersData($file, $dateRange);
                    break;
                case 'organizations':
                    $this->exportOrganizationsData($file, $dateRange);
                    break;
                case 'events':
                    $this->exportEventsData($file, $dateRange);
                    break;
                case 'support':
                    $this->exportSupportData($file, $dateRange);
                    break;
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get date range from request or default to last 30 days
     */
    private function getDateRange(Request $request): array
    {
        $start = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $end = $request->get('end_date', now()->format('Y-m-d'));
        
        return [
            'start' => Carbon::parse($start)->startOfDay(),
            'end' => Carbon::parse($end)->endOfDay(),
        ];
    }

    /**
     * Get quick statistics for dashboard
     */
    private function getQuickStats(): array
    {
        return [
            'total_users' => User::count(),
            'total_organizations' => Organization::count(),
            'total_events' => Event::count(),
            'open_support_tickets' => SupportTicket::open()->count(),
            'activities_today' => ActivityLog::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Get recent activity for dashboard
     */
    private function getRecentActivity(): array
    {
        return ActivityLog::with('user')
                         ->orderBy('created_at', 'desc')
                         ->limit(10)
                         ->get()
                         ->map(function($log) {
                             return [
                                 'description' => $log->description,
                                 'user' => $log->user ? $log->user->name : 'System',
                                 'time' => $log->created_at->diffForHumans(),
                             ];
                         })
                         ->toArray();
    }

    /**
     * Export volunteers data to CSV
     */
    private function exportVolunteersData($file, array $dateRange): void
    {
        fputcsv($file, ['Name', 'Email', 'Status', 'Joined Date', 'Total Hours', 'Applications Count']);
        
        User::with(['volunteerApplications', 'volunteerTimeLogs'])
            ->where('created', '>=', $dateRange['start'])
            ->where('created', '<=', $dateRange['end'])
            ->chunk(100, function($users) use ($file) {
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->name,
                        $user->email,
                        $user->status,
                        $user->created->format('Y-m-d'),
                        $user->volunteerTimeLogs->sum('hours'),
                        $user->volunteerApplications->count(),
                    ]);
                }
            });
    }

    /**
     * Export organizations data to CSV
     */
    private function exportOrganizationsData($file, array $dateRange): void
    {
        fputcsv($file, ['Name', 'Country', 'Status', 'Created Date', 'Members Count', 'Events Count']);
        
        Organization::with(['country', 'users', 'events'])
                   ->where('created', '>=', $dateRange['start'])
                   ->where('created', '<=', $dateRange['end'])
                   ->chunk(100, function($organizations) use ($file) {
                       foreach ($organizations as $org) {
                           fputcsv($file, [
                               $org->name,
                               $org->country ? $org->country->name : '',
                               $org->status,
                               $org->created->format('Y-m-d'),
                               $org->users->count(),
                               $org->events->count(),
                           ]);
                       }
                   });
    }

    /**
     * Export events data to CSV
     */
    private function exportEventsData($file, array $dateRange): void
    {
        fputcsv($file, ['Title', 'Organization', 'Start Date', 'End Date', 'Status', 'Participants', 'Location']);
        
        Event::with(['organization'])
             ->where('created', '>=', $dateRange['start'])
             ->where('created', '<=', $dateRange['end'])
             ->chunk(100, function($events) use ($file) {
                 foreach ($events as $event) {
                     fputcsv($file, [
                         $event->title,
                         $event->organization ? $event->organization->name : '',
                         $event->start_date->format('Y-m-d H:i'),
                         $event->end_date->format('Y-m-d H:i'),
                         $event->status,
                         $event->current_participants,
                         $event->location,
                     ]);
                 }
             });
    }

    /**
     * Export support data to CSV
     */
    private function exportSupportData($file, array $dateRange): void
    {
        fputcsv($file, ['ID', 'Title', 'Customer', 'Category', 'Priority', 'Status', 'Created', 'Resolved']);
        
        SupportTicket::with(['user'])
                     ->where('created', '>=', $dateRange['start'])
                     ->where('created', '<=', $dateRange['end'])
                     ->chunk(100, function($tickets) use ($file) {
                         foreach ($tickets as $ticket) {
                             fputcsv($file, [
                                 $ticket->id,
                                 $ticket->title,
                                 $ticket->user ? $ticket->user->name : '',
                                 $ticket->category,
                                 $ticket->priority,
                                 $ticket->status,
                                 $ticket->created->format('Y-m-d H:i:s'),
                                 $ticket->resolved_at ? $ticket->resolved_at->format('Y-m-d H:i:s') : '',
                             ]);
                         }
                     });
    }
}
