<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NewsletterService;
use App\Models\NewsletterSubscription;
use App\Models\NewsletterContent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    protected $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    /**
     * Display newsletter management interface
     */
    public function index()
    {
        $stats = $this->newsletterService->getStats();
        $recentSubscriptions = NewsletterSubscription::with('user')
                                                    ->orderBy('subscribed_at', 'desc')
                                                    ->limit(10)
                                                    ->get();
        
        $recentNewsletters = NewsletterContent::orderBy('created', 'desc')
                                             ->limit(5)
                                             ->get();

        return view('admin.newsletter.index', compact('stats', 'recentSubscriptions', 'recentNewsletters'));
    }

    /**
     * Display subscribers list
     */
    public function subscribers(Request $request)
    {
        $query = NewsletterSubscription::query();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('email', 'like', "%{$search}%");
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'subscribed_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $subscribers = $query->paginate($perPage)->withQueryString();

        return view('admin.newsletter.subscribers', compact('subscribers'));
    }

    /**
     * Display newsletter content list
     */
    public function content(Request $request)
    {
        $query = NewsletterContent::query();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $newsletters = $query->paginate($perPage)->withQueryString();

        return view('admin.newsletter.content', compact('newsletters'));
    }

    /**
     * Show create newsletter form
     */
    public function create()
    {
        return view('admin.newsletter.create');
    }

    /**
     * Store new newsletter content
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'template' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:draft,ready,sent',
            'scheduled_at' => 'nullable|date|after:now'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->newsletterService->createContent([
            'title' => $request->input('title'),
            'subject' => $request->input('subject'),
            'content' => $request->input('content'),
            'template' => $request->input('template', 'default'),
            'status' => $request->input('status', 'draft'),
            'scheduled_at' => $request->input('scheduled_at'),
            'created_by' => auth()->id()
        ]);

        return response()->json($result);
    }

    /**
     * Send newsletter
     */
    public function send(Request $request, NewsletterContent $newsletter): JsonResponse
    {
        if ($newsletter->status === 'sent') {
            return response()->json([
                'success' => false,
                'message' => 'Newsletter has already been sent'
            ], 400);
        }

        $result = $this->newsletterService->sendNewsletter($newsletter);

        return response()->json($result);
    }

    /**
     * Subscribe email to newsletter
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'preferences' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->newsletterService->subscribe(
            $request->input('email'),
            $request->input('preferences', [])
        );

        return response()->json($result);
    }

    /**
     * Unsubscribe email from newsletter
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'token' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->newsletterService->unsubscribe(
            $request->input('email'),
            $request->input('token')
        );

        return response()->json($result);
    }

    /**
     * Get newsletter statistics
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->newsletterService->getStats();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Export subscribers
     */
    public function exportSubscribers(Request $request)
    {
        $status = $request->input('status', 'active');
        
        $subscribers = NewsletterSubscription::where('status', $status)
                                           ->orderBy('subscribed_at', 'desc')
                                           ->get();

        $filename = "newsletter_subscribers_{$status}_" . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($subscribers) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, ['Email', 'Status', 'Subscribed At', 'Unsubscribed At', 'Preferences']);
            
            // Add data rows
            foreach ($subscribers as $subscriber) {
                fputcsv($file, [
                    $subscriber->email,
                    $subscriber->status,
                    $subscriber->subscribed_at ? $subscriber->subscribed_at->format('Y-m-d H:i:s') : '',
                    $subscriber->unsubscribed_at ? $subscriber->unsubscribed_at->format('Y-m-d H:i:s') : '',
                    json_encode($subscriber->preferences)
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk operations on subscribers
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:subscribe,unsubscribe,delete',
            'emails' => 'required|array|min:1',
            'emails.*' => 'email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $action = $request->input('action');
        $emails = $request->input('emails');
        $results = [];

        foreach ($emails as $email) {
            switch ($action) {
                case 'subscribe':
                    $results[] = $this->newsletterService->subscribe($email);
                    break;
                case 'unsubscribe':
                    $results[] = $this->newsletterService->unsubscribe($email);
                    break;
                case 'delete':
                    NewsletterSubscription::where('email', $email)->delete();
                    $results[] = ['success' => true, 'email' => $email];
                    break;
            }
        }

        $successful = collect($results)->where('success', true)->count();
        $failed = count($results) - $successful;

        return response()->json([
            'success' => true,
            'message' => "Bulk action completed: {$successful} successful, {$failed} failed",
            'results' => $results
        ]);
    }
}