<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDocument;
use App\Services\DocumentManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Exception;

class DocumentVerificationController extends Controller
{
    public function __construct(
        private DocumentManagementService $documentService
    ) {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Display documents pending verification.
     */
    public function index(Request $request): View
    {
        $query = $this->documentService->getDocumentsForVerification();

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query = $query->where('category', $request->category);
        }

        // Filter by user
        if ($request->has('user_search') && !empty($request->user_search)) {
            $query = $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->user_search . '%')
                  ->orWhere('email', 'like', '%' . $request->user_search . '%');
            });
        }

        $documents = $query->paginate(20);
        $categories = config('documents.categories');
        $statistics = $this->documentService->getPlatformDocumentStatistics();

        return view('admin.documents.verification.index', compact(
            'documents',
            'categories',
            'statistics'
        ));
    }

    /**
     * Show document for verification.
     */
    public function show(UserDocument $document): View
    {
        if ($document->verification_status !== 'pending') {
            return redirect()->route('admin.documents.verification.index')
                ->with('warning', 'This document has already been processed.');
        }

        return view('admin.documents.verification.show', compact('document'));
    }

    /**
     * Verify a document.
     */
    public function verify(Request $request, UserDocument $document): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $approved = $request->action === 'approve';
            
            $this->documentService->verifyDocument(
                $document,
                Auth::user(),
                $approved,
                $request->notes
            );

            $message = $approved 
                ? 'Document approved successfully.' 
                : 'Document rejected successfully.';

            return redirect()->route('admin.documents.verification.index')
                ->with('success', $message);
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk verify documents.
     */
    public function bulkVerify(Request $request): JsonResponse
    {
        $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'required|integer|exists:user_documents,id',
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $approved = $request->action === 'approve';
            $processed = 0;
            $errors = [];

            foreach ($request->document_ids as $documentId) {
                try {
                    $document = UserDocument::findOrFail($documentId);
                    
                    if ($document->verification_status === 'pending') {
                        $this->documentService->verifyDocument(
                            $document,
                            Auth::user(),
                            $approved,
                            $request->notes
                        );
                        $processed++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Document ID {$documentId}: " . $e->getMessage();
                }
            }

            $message = $approved 
                ? "Successfully approved {$processed} documents." 
                : "Successfully rejected {$processed} documents.";

            if (!empty($errors)) {
                $message .= ' Some documents could not be processed: ' . implode(', ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'processed' => $processed,
                'errors' => $errors
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get verification queue statistics.
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->documentService->getPlatformDocumentStatistics();

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
     * Get expiring documents across platform.
     */
    public function expiring(Request $request): View
    {
        $days = $request->get('days', 30);
        $expiringDocuments = $this->documentService->getExpiringDocuments($days);
        
        return view('admin.documents.expiring', compact('expiringDocuments', 'days'));
    }

    /**
     * Get expired documents across platform.
     */
    public function expired(): View
    {
        $expiredDocuments = $this->documentService->getExpiredDocuments();
        
        return view('admin.documents.expired', compact('expiredDocuments'));
    }

    /**
     * Download document for verification.
     */
    public function download(UserDocument $document)
    {
        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }

        return Storage::disk('private')->download(
            $document->file_path,
            $document->file_name
        );
    }

    /**
     * Get document verification history.
     */
    public function history(Request $request): View
    {
        $query = UserDocument::with(['user', 'verifier'])
            ->whereIn('verification_status', ['verified', 'rejected'])
            ->orderBy('verified_at', 'desc');

        // Filter by verifier
        if ($request->has('verifier') && $request->verifier !== 'all') {
            $query->where('verified_by', $request->verifier);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('verification_status', $request->status);
        }

        // Filter by date range
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('verified_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('verified_at', '<=', $request->date_to);
        }

        $documents = $query->paginate(20);
        $verifiers = User::role('admin')->pluck('name', 'id');

        return view('admin.documents.verification.history', compact(
            'documents',
            'verifiers'
        ));
    }

    /**
     * Export verification report.
     */
    public function exportReport(Request $request): Response
    {
        $request->validate([
            'format' => 'required|in:csv,pdf,excel',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        try {
            // This would typically use a dedicated export service
            $query = UserDocument::with(['user', 'verifier'])
                ->whereIn('verification_status', ['verified', 'rejected']);

            if ($request->date_from) {
                $query->whereDate('verified_at', '>=', $request->date_from);
            }

            if ($request->date_to) {
                $query->whereDate('verified_at', '<=', $request->date_to);
            }

            $documents = $query->orderBy('verified_at', 'desc')->get();

            // Generate export based on format
            switch ($request->format) {
                case 'csv':
                    return $this->exportToCsv($documents);
                case 'pdf':
                    return $this->exportToPdf($documents);
                case 'excel':
                    return $this->exportToExcel($documents);
                default:
                    throw new Exception('Invalid export format');
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Export documents to CSV.
     */
    private function exportToCsv($documents): Response
    {
        $filename = 'document_verification_report_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($documents) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Document ID',
                'User Name',
                'User Email',
                'Document Name',
                'Category',
                'Verification Status',
                'Verified By',
                'Verified At',
                'Notes'
            ]);

            // CSV data
            foreach ($documents as $document) {
                fputcsv($file, [
                    $document->id,
                    $document->user->name,
                    $document->user->email,
                    $document->name,
                    $document->category,
                    $document->verification_status,
                    $document->verifier->name ?? 'N/A',
                    $document->verified_at ? $document->verified_at->format('Y-m-d H:i:s') : 'N/A',
                    $document->verification_notes ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export documents to PDF (placeholder).
     */
    private function exportToPdf($documents): Response
    {
        // This would typically use a PDF library like DomPDF or TCPDF
        throw new Exception('PDF export not implemented yet');
    }

    /**
     * Export documents to Excel (placeholder).
     */
    private function exportToExcel($documents): Response
    {
        // This would typically use a library like PhpSpreadsheet
        throw new Exception('Excel export not implemented yet');
    }
}