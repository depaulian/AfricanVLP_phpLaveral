<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\UserProfileService;
use App\Services\DocumentManagementService;
use App\Models\UserDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class DocumentController extends Controller
{
    public function __construct(
        private UserProfileService $profileService,
        private DocumentManagementService $documentService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display user's documents.
     */
    public function index(): View
    {
        $user = Auth::user();
        $documents = $user->documents()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $documentTypes = config('documents.categories');
        $verificationStatuses = UserDocument::VERIFICATION_STATUSES ?? [
            'pending' => 'Pending',
            'verified' => 'Verified',
            'rejected' => 'Rejected'
        ];

        // Get document statistics
        $statistics = $this->documentService->getUserDocumentStatistics($user);

        // Get expiring documents
        $expiringDocuments = $this->documentService->getExpiringDocuments(30)
            ->where('user_id', $user->id);

        return view('client.profile.documents', compact(
            'documents',
            'documentTypes',
            'verificationStatuses',
            'statistics',
            'expiringDocuments'
        ));
    }

    /**
     * Show the form for uploading a new document.
     */
    public function create(): View
    {
        $categories = config('documents.categories');
        
        return view('client.profile.documents-upload', compact('categories'));
    }

    /**
     * Store a newly uploaded document.
     */
    public function store(Request $request): RedirectResponse
    {
        $categories = array_keys(config('documents.categories'));
        
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', $categories),
            'description' => 'nullable|string|max:1000',
            'file' => 'required|file|max:' . (config('documents.max_file_size') / 1024) . '|mimes:' . implode(',', config('documents.allowed_extensions')),
            'expiry_date' => 'nullable|date|after:today',
            'is_sensitive' => 'boolean'
        ]);

        try {
            $document = $this->documentService->uploadDocument(
                Auth::user(),
                $request->file('file'),
                $request->category,
                [
                    'name' => $request->name,
                    'description' => $request->description,
                    'expiry_date' => $request->expiry_date,
                    'is_sensitive' => $request->boolean('is_sensitive', false)
                ]
            );

            return redirect()->route('profile.documents.index')
                ->with('success', 'Document uploaded successfully and is being processed for verification.');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified document.
     */
    public function show(UserDocument $document): View
    {
        $this->authorize('view', $document);

        return view('client.documents.show', compact('document'));
    }

    /**
     * Download the specified document.
     */
    public function download(UserDocument $document): Response
    {
        $this->authorize('view', $document);

        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('private')->download(
            $document->file_path,
            $document->file_name
        );
    }

    /**
     * Remove the specified document.
     */
    public function destroy(UserDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        try {
            // Delete the file from storage
            if (Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }

            // Delete the database record
            $document->delete();

            return redirect()->route('documents.index')
                ->with('success', 'Document deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete document. Please try again.');
        }
    }

    /**
     * Upload document via AJAX.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB
            'document_type' => 'required|in:' . implode(',', array_keys(UserDocument::DOCUMENT_TYPES)),
        ]);

        try {
            $document = $this->profileService->uploadDocument(
                Auth::user(),
                $request->file('document'),
                $request->document_type
            );

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully!',
                'document' => [
                    'id' => $document->id,
                    'file_name' => $document->file_name,
                    'document_type' => $document->document_type,
                    'document_type_label' => $document->document_type_label,
                    'file_size_human' => $document->file_size_human,
                    'verification_status' => $document->verification_status,
                    'verification_status_label' => $document->verification_status_label,
                    'created_at' => $document->created_at->format('M d, Y'),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get documents list via AJAX.
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = $user->documents();

            // Filter by document type
            if ($request->has('type') && $request->type !== 'all') {
                $query->where('document_type', $request->type);
            }

            // Filter by verification status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('verification_status', $request->status);
            }

            // Search by filename
            if ($request->has('search') && !empty($request->search)) {
                $query->where('file_name', 'like', '%' . $request->search . '%');
            }

            $documents = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'documents' => $documents->items(),
                'pagination' => [
                    'current_page' => $documents->currentPage(),
                    'last_page' => $documents->lastPage(),
                    'per_page' => $documents->perPage(),
                    'total' => $documents->total(),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load documents'
            ], 500);
        }
    }

    /**
     * Get document verification status via AJAX.
     */
    public function verificationStatus(UserDocument $document): JsonResponse
    {
        $this->authorize('view', $document);

        try {
            return response()->json([
                'success' => true,
                'status' => $document->verification_status,
                'status_label' => $document->verification_status_label,
                'verified_at' => $document->verified_at?->format('M d, Y H:i'),
                'verified_by' => $document->verifier?->full_name,
                'rejection_reason' => $document->rejection_reason,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load verification status'
            ], 500);
        }
    }

    /**
     * Get document statistics via AJAX.
     */
    public function statistics(): JsonResponse
    {
        try {
            $user = Auth::user();
            $statistics = $this->documentService->getUserDocumentStatistics($user);

            return response()->json([
                'success' => true,
                'statistics' => $statistics
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics'
            ], 500);
        }
    }

    /**
     * Share a document with specified recipients.
     */
    public function share(Request $request, UserDocument $document): JsonResponse
    {
        $this->authorize('view', $document);

        $request->validate([
            'recipients' => 'required|array|min:1|max:10',
            'recipients.*' => 'required|email',
            'permissions' => 'array',
            'permissions.view' => 'boolean',
            'permissions.download' => 'boolean',
            'permissions.print' => 'boolean',
            'expires_at' => 'nullable|date|after:now|before:' . now()->addDays(30)->toDateString()
        ]);

        try {
            $expiresAt = $request->expires_at ? Carbon::parse($request->expires_at) : null;
            
            $shareResult = $this->documentService->shareDocument(
                $document,
                $request->recipients,
                $request->permissions ?? [],
                $expiresAt
            );

            return response()->json([
                'success' => true,
                'message' => 'Document shared successfully',
                'share_url' => $shareResult['share_url'],
                'expires_at' => $shareResult['expires_at']?->format('M d, Y H:i'),
                'permissions' => $shareResult['permissions']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Access a shared document.
     */
    public function shared(Request $request, string $token): View|RedirectResponse
    {
        try {
            $shareData = $this->documentService->accessSharedDocument($token, Auth::user());
            
            return view('client.profile.document-shared', [
                'document' => $shareData['document'],
                'permissions' => $shareData['permissions'],
                'shared_by' => $shareData['shared_by'],
                'expires_at' => $shareData['expires_at']
            ]);
        } catch (Exception $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }
    }

    /**
     * Create a backup of the document.
     */
    public function backup(UserDocument $document): JsonResponse
    {
        $this->authorize('view', $document);

        try {
            $backupResult = $this->documentService->createDocumentBackup($document);

            return response()->json([
                'success' => true,
                'message' => 'Document backup created successfully',
                'backup_path' => $backupResult['backup_path'],
                'backup_size' => $this->formatFileSize($backupResult['backup_size']),
                'created_at' => Carbon::parse($backupResult['created_at'])->format('M d, Y H:i')
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expiring documents for the user.
     */
    public function expiring(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);
            $user = Auth::user();
            
            $expiringDocuments = $user->documents()
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays($days))
                ->where('expiry_date', '>', now())
                ->orderBy('expiry_date')
                ->get()
                ->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'name' => $document->name,
                        'category' => $document->category,
                        'expiry_date' => $document->expiry_date->format('M d, Y'),
                        'days_until_expiry' => $document->expiry_date->diffInDays(now()),
                        'is_critical' => $document->expiry_date->diffInDays(now()) <= 7
                    ];
                });

            return response()->json([
                'success' => true,
                'documents' => $expiringDocuments
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load expiring documents'
            ], 500);
        }
    }

    /**
     * Update document metadata.
     */
    public function updateMetadata(Request $request, UserDocument $document): JsonResponse
    {
        $this->authorize('update', $document);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'expiry_date' => 'nullable|date|after:today',
            'is_sensitive' => 'boolean'
        ]);

        try {
            $document->update([
                'name' => $request->name,
                'description' => $request->description,
                'expiry_date' => $request->expiry_date,
                'is_sensitive' => $request->boolean('is_sensitive', false)
            ]);

            // Reschedule expiration reminders if expiry date changed
            if ($request->expiry_date && $document->wasChanged('expiry_date')) {
                $this->documentService->scheduleExpirationReminders($document);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully',
                'document' => [
                    'id' => $document->id,
                    'name' => $document->name,
                    'description' => $document->description,
                    'expiry_date' => $document->expiry_date?->format('M d, Y'),
                    'is_sensitive' => $document->is_sensitive
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get document activity log.
     */
    public function activity(UserDocument $document): JsonResponse
    {
        $this->authorize('view', $document);

        try {
            // This would typically come from an activity log table
            $activities = collect([
                [
                    'action' => 'uploaded',
                    'description' => 'Document uploaded',
                    'user' => $document->user->name,
                    'timestamp' => $document->created_at->format('M d, Y H:i'),
                    'ip_address' => $document->upload_ip ?? 'Unknown'
                ]
            ]);

            if ($document->verified_at) {
                $activities->push([
                    'action' => 'verified',
                    'description' => 'Document verified',
                    'user' => $document->verifier->name ?? 'System',
                    'timestamp' => $document->verified_at->format('M d, Y H:i'),
                    'notes' => $document->verification_notes
                ]);
            }

            return response()->json([
                'success' => true,
                'activities' => $activities->sortByDesc('timestamp')->values()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load document activity'
            ], 500);
        }
    }

    /**
     * Format file size in human readable format.
     */
    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}