<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\ForumReport;
use App\Services\ForumModerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ForumReportController extends Controller
{
    public function __construct(
        private ForumModerationService $moderationService
    ) {
        $this->middleware('auth');
    }

    /**
     * Show report form for thread
     */
    public function reportThread(ForumThread $thread): View
    {
        $this->authorize('view', $thread->forum);

        $reportReasons = $this->moderationService->getReportReasons();

        return view('client.forums.report-thread', compact('thread', 'reportReasons'));
    }

    /**
     * Show report form for post
     */
    public function reportPost(ForumPost $post): View
    {
        $this->authorize('view', $post->thread->forum);

        $reportReasons = $this->moderationService->getReportReasons();

        return view('client.forums.report-post', compact('post', 'reportReasons'));
    }

    /**
     * Submit thread report
     */
    public function submitThreadReport(Request $request, ForumThread $thread): JsonResponse
    {
        $this->authorize('view', $thread->forum);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'severity' => 'required|in:low,medium,high',
        ]);

        $success = $this->moderationService->reportContent(
            $thread,
            auth()->user(),
            $validated['reason'],
            $validated['severity']
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Thread reported successfully. Our moderators will review it shortly.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'You have already reported this thread or an error occurred.'
            ], 400);
        }
    }

    /**
     * Submit post report
     */
    public function submitPostReport(Request $request, ForumPost $post): JsonResponse
    {
        $this->authorize('view', $post->thread->forum);

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'severity' => 'required|in:low,medium,high',
        ]);

        $success = $this->moderationService->reportContent(
            $post,
            auth()->user(),
            $validated['reason'],
            $validated['severity']
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Post reported successfully. Our moderators will review it shortly.'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'You have already reported this post or an error occurred.'
            ], 400);
        }
    }

    /**
     * Get user's report history
     */
    public function myReports(): View
    {
        $reports = ForumReport::where('reporter_id', auth()->id())
            ->with(['reportable', 'moderator'])
            ->latest()
            ->paginate(20);

        return view('client.forums.my-reports', compact('reports'));
    }

    /**
     * Get user's moderation history (warnings, suspensions, etc.)
     */
    public function moderationHistory(): View
    {
        $history = $this->moderationService->getUserModerationHistory(auth()->user());

        return view('client.forums.moderation-history', compact('history'));
    }

    /**
     * Acknowledge a warning
     */
    public function acknowledgeWarning(int $warningId): JsonResponse
    {
        $warning = auth()->user()->forumWarnings()->findOrFail($warningId);
        
        $warning->acknowledge();

        return response()->json([
            'success' => true,
            'message' => 'Warning acknowledged.'
        ]);
    }

    /**
     * Check if user can post in forum
     */
    public function checkPostingPermissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'forum_id' => 'required|exists:forums,id',
        ]);

        $forum = \App\Models\Forum::findOrFail($validated['forum_id']);
        $permissions = $this->moderationService->canUserPost(auth()->user(), $forum);

        return response()->json($permissions);
    }

    /**
     * Check if user can reply to thread
     */
    public function checkReplyPermissions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'thread_id' => 'required|exists:forum_threads,id',
        ]);

        $thread = ForumThread::findOrFail($validated['thread_id']);
        $permissions = $this->moderationService->canUserReply(auth()->user(), $thread);

        return response()->json($permissions);
    }

    /**
     * Get content moderation status
     */
    public function getContentStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:thread,post',
            'id' => 'required|integer',
        ]);

        if ($validated['type'] === 'thread') {
            $content = ForumThread::findOrFail($validated['id']);
        } else {
            $content = ForumPost::findOrFail($validated['id']);
        }

        $status = $this->moderationService->getContentModerationStatus($content);

        return response()->json($status);
    }

    /**
     * Get available report reasons
     */
    public function getReportReasons(): JsonResponse
    {
        $reasons = $this->moderationService->getReportReasons();

        return response()->json([
            'success' => true,
            'reasons' => $reasons
        ]);
    }
}