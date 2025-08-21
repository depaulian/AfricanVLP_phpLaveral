<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ForumAttachment;
use App\Services\ForumAttachmentService;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Exception;

class ForumAttachmentController extends Controller
{
    public function __construct(
        private ForumAttachmentService $attachmentService
    ) {
        $this->middleware('auth');
    }

    /**
     * Download a forum attachment.
     */
    public function download(ForumAttachment $attachment): StreamedResponse
    {
        // Check if user can access this attachment
        if (!$this->attachmentService->canUserAccessAttachment($attachment, auth()->user())) {
            abort(403, 'Access denied to this attachment');
        }

        try {
            return $this->attachmentService->downloadAttachment($attachment);
        } catch (Exception $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Display an image attachment inline.
     */
    public function show(ForumAttachment $attachment): Response
    {
        // Check if user can view the post that owns this attachment
        $this->authorize('view', $attachment->post);

        // Only allow images to be displayed inline
        if (!$attachment->isImage()) {
            abort(404, 'File cannot be displayed');
        }

        // Check if file exists
        if (!$attachment->fileExists()) {
            abort(404, 'File not found');
        }

        $fileContents = Storage::get($attachment->file_path);

        return response($fileContents, 200, [
            'Content-Type' => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->file_name . '"',
            'Cache-Control' => 'public, max-age=3600',
            'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 3600)
        ]);
    }

    /**
     * Delete an attachment.
     */
    public function destroy(ForumAttachment $attachment): JsonResponse
    {
        $post = $attachment->post;
        
        // Check permissions - only post author or moderators can delete
        if (auth()->id() !== $post->author_id && !auth()->user()->can('moderate', $post->thread->forum)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this attachment'
            ], 403);
        }

        try {
            $success = $this->attachmentService->deleteAttachment($attachment);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Attachment deleted successfully.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete attachment'
                ], 500);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting attachment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get attachment statistics (for moderators)
     */
    public function stats(): JsonResponse
    {
        // Check if user is moderator or admin
        if (!auth()->user()->hasRole(['admin', 'moderator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $stats = [
            'total_attachments' => ForumAttachment::count(),
            'total_size' => ForumAttachment::sum('file_size'),
            'total_downloads' => ForumAttachment::sum('download_count'),
            'by_type' => [
                'images' => ForumAttachment::where('mime_type', 'like', 'image/%')->count(),
                'documents' => ForumAttachment::whereIn('mime_type', [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain',
                    'text/csv'
                ])->count(),
                'archives' => ForumAttachment::whereIn('mime_type', [
                    'application/zip',
                    'application/x-rar-compressed',
                    'application/x-7z-compressed'
                ])->count(),
            ],
            'recent_uploads' => ForumAttachment::with(['post.thread.forum', 'post.author'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'file_size' => $attachment->human_file_size,
                        'author' => $attachment->post->author->name,
                        'forum' => $attachment->post->thread->forum->name,
                        'thread' => $attachment->post->thread->title,
                        'created_at' => $attachment->created_at->format('M j, Y g:i A'),
                    ];
                })
        ];

        $stats['total_size_human'] = $this->attachmentService->formatBytes($stats['total_size']);

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}