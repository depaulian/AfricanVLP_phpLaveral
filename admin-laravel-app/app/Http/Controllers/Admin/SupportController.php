<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketResponse;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class SupportController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Display support tickets dashboard
     */
    public function index(Request $request): View
    {
        $query = SupportTicket::with(['user', 'assignedTo', 'responses'])
                              ->withCount('responses');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQ) use ($search) {
                      $userQ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->open();
            } elseif ($request->status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $request->status);
            }
        }

        // Priority filter
        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        // Category filter
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        // Assigned to filter
        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->unassigned();
            } else {
                $query->assignedTo($request->assigned_to);
            }
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('created', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created', '<=', $request->date_to . ' 23:59:59');
        }

        $tickets = $query->orderBy('created', 'desc')->paginate(25)->withQueryString();

        // Get filter options
        $adminUsers = User::where('is_admin', true)->orderBy('first_name')->get();
        $priorities = ['critical', 'high', 'medium', 'low'];
        $statuses = ['open', 'in_progress', 'pending', 'resolved', 'closed'];
        $categories = ['technical', 'account', 'billing', 'feature_request', 'bug_report', 'general'];

        // Get dashboard statistics
        $stats = $this->getDashboardStats();

        return view('admin.support.index', compact(
            'tickets', 'adminUsers', 'priorities', 'statuses', 'categories', 'stats'
        ));
    }

    /**
     * Show the form for creating a new ticket
     */
    public function create(): View
    {
        $users = User::orderBy('first_name')->get();
        $adminUsers = User::where('is_admin', true)->orderBy('first_name')->get();
        $priorities = ['critical', 'high', 'medium', 'low'];
        $categories = ['technical', 'account', 'billing', 'feature_request', 'bug_report', 'general'];

        return view('admin.support.create', compact(
            'users', 'adminUsers', 'priorities', 'categories'
        ));
    }

    /**
     * Store a newly created ticket
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:technical,account,billing,feature_request,bug_report,general',
            'priority' => 'required|in:critical,high,medium,low',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $ticketData = $request->only([
            'user_id', 'title', 'description', 'category', 'priority', 'assigned_to'
        ]);

        $ticketData['status'] = SupportTicket::STATUS_OPEN;
        
        // Process tags
        if ($request->filled('tags')) {
            $ticketData['tags'] = array_map('trim', explode(',', $request->tags));
        }

        $ticket = SupportTicket::create($ticketData);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->storeAttachment($ticket, null, $file, Auth::id());
            }
        }

        // Log the activity
        $this->activityLogService->logCreated($ticket, [
            'created_by_admin' => true,
            'user_email' => $ticket->user->email
        ]);

        return redirect()
            ->route('admin.support.show', $ticket)
            ->with('success', 'Support ticket created successfully!');
    }

    /**
     * Display the specified ticket
     */
    public function show(SupportTicket $ticket): View
    {
        $ticket->load([
            'user', 
            'assignedTo', 
            'responses.user', 
            'responses.attachments',
            'attachments'
        ]);

        $adminUsers = User::where('is_admin', true)->orderBy('first_name')->get();
        $priorities = ['critical', 'high', 'medium', 'low'];
        $statuses = ['open', 'in_progress', 'pending', 'resolved', 'closed'];

        return view('admin.support.show', compact(
            'ticket', 'adminUsers', 'priorities', 'statuses'
        ));
    }

    /**
     * Update ticket details
     */
    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'category' => 'required|in:technical,account,billing,feature_request,bug_report,general',
            'priority' => 'required|in:critical,high,medium,low',
            'status' => 'required|in:open,in_progress,pending,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|string',
            'resolution' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $originalData = $ticket->toArray();

        $updateData = $request->only([
            'title', 'category', 'priority', 'status', 'assigned_to', 'resolution'
        ]);

        // Process tags
        if ($request->has('tags')) {
            $updateData['tags'] = $request->filled('tags') 
                ? array_map('trim', explode(',', $request->tags))
                : null;
        }

        // Set resolved/closed timestamps
        if ($request->status === 'resolved' && $ticket->status !== 'resolved') {
            $updateData['resolved_at'] = now();
        }
        if ($request->status === 'closed' && $ticket->status !== 'closed') {
            $updateData['closed_at'] = now();
        }

        $ticket->update($updateData);

        // Log the activity
        $this->activityLogService->logUpdated($ticket, $originalData, [
            'updated_fields' => array_keys($updateData),
            'status_changed' => $originalData['status'] !== $ticket->status
        ]);

        return back()->with('success', 'Ticket updated successfully!');
    }

    /**
     * Add a response to the ticket
     */
    public function addResponse(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
            'is_internal' => 'boolean',
            'is_solution' => 'boolean',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Calculate response time if this is the first admin response
        $responseTime = null;
        if (Auth::user()->isAdmin()) {
            $lastCustomerResponse = $ticket->responses()
                ->whereHas('user', function($q) {
                    $q->where('is_admin', false);
                })
                ->latest('created')
                ->first();

            if ($lastCustomerResponse) {
                $responseTime = now()->diffInMinutes($lastCustomerResponse->created);
            } else {
                $responseTime = now()->diffInMinutes($ticket->created);
            }
        }

        $response = SupportTicketResponse::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'is_internal' => $request->boolean('is_internal'),
            'is_solution' => $request->boolean('is_solution'),
            'response_time_minutes' => $responseTime,
        ]);

        // Handle file attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->storeAttachment($ticket, $response, $file, Auth::id());
            }
        }

        // Update ticket status if this is a solution
        if ($request->boolean('is_solution')) {
            $ticket->update([
                'status' => SupportTicket::STATUS_RESOLVED,
                'resolved_at' => now(),
            ]);
        }

        // Log the activity
        $this->activityLogService->log(
            'response_added',
            'Added response to support ticket',
            $ticket,
            [
                'response_id' => $response->id,
                'is_internal' => $response->is_internal,
                'is_solution' => $response->is_solution,
                'response_time_minutes' => $responseTime
            ]
        );

        return back()->with('success', 'Response added successfully!');
    }

    /**
     * Assign ticket to admin user
     */
    public function assign(Request $request, SupportTicket $ticket): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        $oldAssignee = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $request->assigned_to]);

        // Log the activity
        $this->activityLogService->log(
            'ticket_assigned',
            'Assigned support ticket',
            $ticket,
            [
                'old_assignee' => $oldAssignee,
                'new_assignee' => $request->assigned_to,
                'assigned_by' => Auth::id()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Ticket assigned successfully!',
            'assignee' => $ticket->fresh()->assignedTo->name ?? 'Unassigned'
        ]);
    }

    /**
     * Bulk actions on tickets
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:assign,status_change,priority_change,delete',
            'ticket_ids' => 'required|array',
            'ticket_ids.*' => 'exists:support_tickets,id',
            'value' => 'required_unless:action,delete',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }

        $tickets = SupportTicket::whereIn('id', $request->ticket_ids)->get();
        $action = $request->action;
        $value = $request->value;

        foreach ($tickets as $ticket) {
            switch ($action) {
                case 'assign':
                    $ticket->update(['assigned_to' => $value]);
                    break;
                case 'status_change':
                    $updateData = ['status' => $value];
                    if ($value === 'resolved') $updateData['resolved_at'] = now();
                    if ($value === 'closed') $updateData['closed_at'] = now();
                    $ticket->update($updateData);
                    break;
                case 'priority_change':
                    $ticket->update(['priority' => $value]);
                    break;
                case 'delete':
                    $ticket->delete();
                    break;
            }

            // Log the activity
            $this->activityLogService->log(
                'bulk_action',
                "Bulk action: {$action}",
                $ticket,
                [
                    'action' => $action,
                    'value' => $value ?? null,
                    'performed_by' => Auth::id()
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Bulk action completed on " . count($tickets) . " tickets."
        ]);
    }

    /**
     * Get support statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->getDashboardStats();
        return response()->json($stats);
    }

    /**
     * Export tickets to CSV
     */
    public function export(Request $request)
    {
        $query = SupportTicket::with(['user', 'assignedTo']);

        // Apply same filters as index
        if ($request->filled('status')) {
            if ($request->status === 'open') {
                $query->open();
            } elseif ($request->status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        $tickets = $query->orderBy('created', 'desc')->get();

        // Log the export activity
        $this->activityLogService->logExport('Support Tickets', [
            'filters' => $request->only(['status', 'priority', 'category']),
            'record_count' => $tickets->count()
        ]);

        $filename = 'support_tickets_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($tickets) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Title', 'Customer', 'Category', 'Priority', 'Status',
                'Assigned To', 'Created', 'Resolved', 'Response Count'
            ]);

            foreach ($tickets as $ticket) {
                fputcsv($file, [
                    $ticket->id,
                    $ticket->title,
                    $ticket->user ? $ticket->user->name : 'N/A',
                    $ticket->category,
                    $ticket->priority,
                    $ticket->status,
                    $ticket->assignedTo ? $ticket->assignedTo->name : 'Unassigned',
                    $ticket->created->format('Y-m-d H:i:s'),
                    $ticket->resolved_at ? $ticket->resolved_at->format('Y-m-d H:i:s') : '',
                    $ticket->getResponseCount()
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Store attachment for ticket or response
     */
    private function storeAttachment(SupportTicket $ticket, ?SupportTicketResponse $response, $file, int $userId): SupportTicketAttachment
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Store file
        $filePath = $file->store('support-attachments', 'public');

        return SupportTicketAttachment::create([
            'support_ticket_id' => $ticket->id,
            'support_ticket_response_id' => $response?->id,
            'user_id' => $userId,
            'filename' => pathinfo($filePath, PATHINFO_BASENAME),
            'original_filename' => $originalName,
            'file_path' => $filePath,
            'file_size' => $size,
            'mime_type' => $mimeType,
            'file_type' => $extension,
        ]);
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats(): array
    {
        return [
            'total_tickets' => SupportTicket::count(),
            'open_tickets' => SupportTicket::open()->count(),
            'overdue_tickets' => SupportTicket::overdue()->count(),
            'resolved_today' => SupportTicket::resolved()
                ->whereDate('resolved_at', today())
                ->count(),
            'avg_response_time' => SupportTicketResponse::whereNotNull('response_time_minutes')
                ->avg('response_time_minutes'),
            'tickets_by_priority' => SupportTicket::open()
                ->selectRaw('priority, count(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
            'tickets_by_category' => SupportTicket::open()
                ->selectRaw('category, count(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category')
                ->toArray(),
            'recent_activity' => SupportTicket::with('user')
                ->orderBy('created', 'desc')
                ->limit(5)
                ->get()
                ->map(function($ticket) {
                    return [
                        'id' => $ticket->id,
                        'title' => $ticket->title,
                        'customer' => $ticket->user->name,
                        'status' => $ticket->status,
                        'created_at' => $ticket->created->diffForHumans()
                    ];
                })
        ];
    }
}
