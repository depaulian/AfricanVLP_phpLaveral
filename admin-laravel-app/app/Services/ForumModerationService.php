<?php

namespace App\Services;

use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\User;
use App\Models\ForumReport;
use App\Models\ForumWarning;
use App\Models\ForumSuspension;
use App\Models\ForumBan;
use App\Models\ForumModerationLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Exception;

class ForumModerationService
{
    /**
     * Get count of reported content
     */
    public function getReportedContentCount(): int
    {
        return ForumReport::where('status', 'pending')->count();
    }

    /**
     * Get count of content pending moderation
     */
    public function getPendingModerationCount(): int
    {
        return ForumThread::where('status', 'pending')->count() +
               ForumPost::where('status', 'pending')->count();
    }

    /**
     * Get reported content for review
     */
    public function getReportedContent(int $limit = 20): Collection
    {
        return ForumReport::with(['reportable', 'reporter', 'moderator'])
            ->where('status', 'pending')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get pending moderation actions
     */
    public function getPendingModerationActions(int $limit = 20): Collection
    {
        $threads = ForumThread::with(['author', 'forum'])
            ->where('status', 'pending')
            ->latest()
            ->limit($limit / 2)
            ->get()
            ->map(function ($thread) {
                $thread->type = 'thread';
                return $thread;
            });

        $posts = ForumPost::with(['author', 'thread.forum'])
            ->where('status', 'pending')
            ->latest()
            ->limit($limit / 2)
            ->get()
            ->map(function ($post) {
                $post->type = 'post';
                return $post;
            });

        return $threads->merge($posts)->sortByDesc('created_at');
    }

    /**
     * Get moderation statistics
     */
    public function getModerationStatistics(): array
    {
        return [
            'total_reports' => ForumReport::count(),
            'pending_reports' => ForumReport::where('status', 'pending')->count(),
            'resolved_reports' => ForumReport::where('status', 'resolved')->count(),
            'active_warnings' => ForumWarning::where('expires_at', '>', now())->count(),
            'active_suspensions' => ForumSuspension::where('expires_at', '>', now())->count(),
            'total_bans' => ForumBan::where('is_active', true)->count(),
            'moderation_actions_today' => ForumModerationLog::whereDate('created_at', today())->count(),
            'moderation_actions_week' => ForumModerationLog::where('created_at', '>=', now()->subWeek())->count(),
        ];
    }

    /**
     * Perform bulk moderation action
     */
    public function performBulkAction(string $action, string $type, array $ids, User $moderator): array
    {
        $processed = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($ids as $id) {
                try {
                    if ($type === 'thread') {
                        $item = ForumThread::find($id);
                        if ($item) {
                            $this->performThreadAction($action, $item, $moderator);
                            $processed++;
                        } else {
                            $failed++;
                        }
                    } elseif ($type === 'post') {
                        $item = ForumPost::find($id);
                        if ($item) {
                            $this->performPostAction($action, $item, $moderator);
                            $processed++;
                        } else {
                            $failed++;
                        }
                    }
                } catch (Exception $e) {
                    $failed++;
                    $errors[] = "ID {$id}: " . $e->getMessage();
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Processed {$processed} items successfully" . ($failed > 0 ? ", {$failed} failed" : ""),
                'processed' => $processed,
                'failed' => $failed,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Bulk action failed: ' . $e->getMessage(),
                'processed' => 0,
                'failed' => count($ids)
            ];
        }
    }

    /**
     * Perform action on thread
     */
    private function performThreadAction(string $action, ForumThread $thread, User $moderator): void
    {
        switch ($action) {
            case 'pin':
                $thread->update(['is_pinned' => true]);
                break;
            case 'unpin':
                $thread->update(['is_pinned' => false]);
                break;
            case 'lock':
                $thread->update(['is_locked' => true]);
                break;
            case 'unlock':
                $thread->update(['is_locked' => false]);
                break;
            case 'delete':
                $thread->delete();
                break;
            case 'approve':
                $thread->update(['status' => 'active']);
                break;
            case 'reject':
                $thread->update(['status' => 'rejected']);
                break;
        }

        $this->logModerationAction($moderator, 'thread', $action, $thread->id, [
            'thread_title' => $thread->title,
            'forum_id' => $thread->forum_id
        ]);
    }

    /**
     * Perform action on post
     */
    private function performPostAction(string $action, ForumPost $post, User $moderator): void
    {
        switch ($action) {
            case 'delete':
                $post->delete();
                break;
            case 'approve':
                $post->update(['status' => 'active']);
                break;
            case 'reject':
                $post->update(['status' => 'rejected']);
                break;
        }

        $this->logModerationAction($moderator, 'post', $action, $post->id, [
            'thread_id' => $post->thread_id,
            'author_id' => $post->author_id
        ]);
    }

    /**
     * Toggle thread pin status
     */
    public function toggleThreadPin(ForumThread $thread, User $moderator): bool
    {
        try {
            $newStatus = !$thread->is_pinned;
            $thread->update(['is_pinned' => $newStatus]);

            $this->logModerationAction($moderator, 'thread', $newStatus ? 'pin' : 'unpin', $thread->id, [
                'thread_title' => $thread->title
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Toggle thread lock status
     */
    public function toggleThreadLock(ForumThread $thread, User $moderator): bool
    {
        try {
            $newStatus = !$thread->is_locked;
            $thread->update(['is_locked' => $newStatus]);

            $this->logModerationAction($moderator, 'thread', $newStatus ? 'lock' : 'unlock', $thread->id, [
                'thread_title' => $thread->title
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete thread
     */
    public function deleteThread(ForumThread $thread, User $moderator): bool
    {
        try {
            $threadData = [
                'thread_title' => $thread->title,
                'forum_id' => $thread->forum_id,
                'author_id' => $thread->author_id
            ];

            $thread->delete();

            $this->logModerationAction($moderator, 'thread', 'delete', $thread->id, $threadData);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Delete post
     */
    public function deletePost(ForumPost $post, User $moderator): bool
    {
        try {
            $postData = [
                'thread_id' => $post->thread_id,
                'author_id' => $post->author_id
            ];

            $post->delete();

            $this->logModerationAction($moderator, 'post', 'delete', $post->id, $postData);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Edit post content (moderator override)
     */
    public function editPost(ForumPost $post, string $newContent, string $reason, User $moderator): bool
    {
        try {
            $originalContent = $post->content;
            
            $post->update([
                'content' => $newContent,
                'edited_by_moderator' => true,
                'moderator_edit_reason' => $reason,
                'moderator_edited_at' => now(),
                'moderator_id' => $moderator->id
            ]);

            $this->logModerationAction($moderator, 'post', 'edit', $post->id, [
                'reason' => $reason,
                'original_content_length' => strlen($originalContent),
                'new_content_length' => strlen($newContent)
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Warn user
     */
    public function warnUser(User $user, string $reason, string $severity, User $moderator): bool
    {
        try {
            $expiresAt = match ($severity) {
                'low' => now()->addDays(7),
                'medium' => now()->addDays(30),
                'high' => now()->addDays(90),
                default => now()->addDays(30)
            };

            ForumWarning::create([
                'user_id' => $user->id,
                'moderator_id' => $moderator->id,
                'reason' => $reason,
                'severity' => $severity,
                'expires_at' => $expiresAt,
                'is_active' => true
            ]);

            $this->logModerationAction($moderator, 'user', 'warn', $user->id, [
                'reason' => $reason,
                'severity' => $severity,
                'expires_at' => $expiresAt->toDateTimeString()
            ]);

            // Send notification to user
            $this->notifyUserOfWarning($user, $reason, $severity, $expiresAt);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Suspend user from forums
     */
    public function suspendUser(User $user, string $reason, int $durationDays, User $moderator, ?int $forumId = null): bool
    {
        try {
            $expiresAt = now()->addDays($durationDays);

            ForumSuspension::create([
                'user_id' => $user->id,
                'moderator_id' => $moderator->id,
                'forum_id' => $forumId,
                'reason' => $reason,
                'expires_at' => $expiresAt,
                'is_active' => true
            ]);

            $this->logModerationAction($moderator, 'user', 'suspend', $user->id, [
                'reason' => $reason,
                'duration_days' => $durationDays,
                'forum_id' => $forumId,
                'expires_at' => $expiresAt->toDateTimeString()
            ]);

            // Send notification to user
            $this->notifyUserOfSuspension($user, $reason, $expiresAt, $forumId);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Ban user from forums
     */
    public function banUser(User $user, string $reason, bool $permanent, User $moderator, ?int $forumId = null): bool
    {
        try {
            ForumBan::create([
                'user_id' => $user->id,
                'moderator_id' => $moderator->id,
                'forum_id' => $forumId,
                'reason' => $reason,
                'is_permanent' => $permanent,
                'expires_at' => $permanent ? null : now()->addYear(),
                'is_active' => true
            ]);

            $this->logModerationAction($moderator, 'user', 'ban', $user->id, [
                'reason' => $reason,
                'is_permanent' => $permanent,
                'forum_id' => $forumId
            ]);

            // Send notification to user
            $this->notifyUserOfBan($user, $reason, $permanent, $forumId);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Handle content report
     */
    public function handleContentReport(int $reportId, string $action, ?string $notes, User $moderator): bool
    {
        try {
            $report = ForumReport::findOrFail($reportId);
            
            $report->update([
                'status' => $action === 'approve' ? 'resolved' : ($action === 'reject' ? 'dismissed' : 'escalated'),
                'moderator_id' => $moderator->id,
                'moderator_notes' => $notes,
                'resolved_at' => now()
            ]);

            // Take action on reported content if approved
            if ($action === 'approve') {
                $this->takeActionOnReportedContent($report, $moderator);
            }

            $this->logModerationAction($moderator, 'report', $action, $reportId, [
                'report_type' => $report->reportable_type,
                'report_reason' => $report->reason,
                'notes' => $notes
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Take action on reported content
     */
    private function takeActionOnReportedContent(ForumReport $report, User $moderator): void
    {
        $reportable = $report->reportable;

        if ($reportable instanceof ForumThread) {
            // Delete or lock the thread based on report severity
            if ($report->severity === 'high') {
                $reportable->delete();
            } else {
                $reportable->update(['is_locked' => true]);
            }
        } elseif ($reportable instanceof ForumPost) {
            // Delete the post
            $reportable->delete();
        }
    }

    /**
     * Get moderation logs
     */
    public function getModerationLogs(array $filters = [], int $perPage = 20)
    {
        $query = ForumModerationLog::with(['moderator'])
            ->latest();

        if (!empty($filters['moderator_id'])) {
            $query->where('moderator_id', $filters['moderator_id']);
        }

        if (!empty($filters['action_type'])) {
            $query->where('action_type', $filters['action_type']);
        }

        if (!empty($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Export moderation data
     */
    public function exportModerationData(array $options)
    {
        // Implementation for exporting moderation data to CSV/Excel
        // This would use a package like Laravel Excel
        
        $query = ForumModerationLog::with(['moderator']);

        if (!empty($options['date_from'])) {
            $query->whereDate('created_at', '>=', $options['date_from']);
        }

        if (!empty($options['date_to'])) {
            $query->whereDate('created_at', '<=', $options['date_to']);
        }

        if (!empty($options['type'])) {
            $query->where('target_type', $options['type']);
        }

        $data = $query->get();

        // Return export file (implementation depends on chosen export library)
        return response()->streamDownload(function () use ($data) {
            echo "Moderation Log Export\n";
            echo "Date,Moderator,Action,Target Type,Target ID,Details\n";
            
            foreach ($data as $log) {
                echo sprintf(
                    "%s,%s,%s,%s,%s,%s\n",
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->moderator->name ?? 'Unknown',
                    $log->action_type,
                    $log->target_type,
                    $log->target_id,
                    json_encode($log->details)
                );
            }
        }, 'moderation-log-' . now()->format('Y-m-d') . '.csv');
    }

    /**
     * Log moderation action
     */
    private function logModerationAction(User $moderator, string $targetType, string $actionType, int $targetId, array $details = []): void
    {
        ForumModerationLog::create([
            'moderator_id' => $moderator->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action_type' => $actionType,
            'details' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }

    /**
     * Send warning notification to user
     */
    private function notifyUserOfWarning(User $user, string $reason, string $severity, Carbon $expiresAt): void
    {
        // Implementation for sending warning notification
        // This could be email, in-app notification, etc.
    }

    /**
     * Send suspension notification to user
     */
    private function notifyUserOfSuspension(User $user, string $reason, Carbon $expiresAt, ?int $forumId): void
    {
        // Implementation for sending suspension notification
    }

    /**
     * Send ban notification to user
     */
    private function notifyUserOfBan(User $user, string $reason, bool $permanent, ?int $forumId): void
    {
        // Implementation for sending ban notification
    }

    /**
     * Check if user is suspended from forum
     */
    public function isUserSuspended(User $user, ?int $forumId = null): bool
    {
        $query = ForumSuspension::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now());

        if ($forumId) {
            $query->where(function ($q) use ($forumId) {
                $q->where('forum_id', $forumId)->orWhereNull('forum_id');
            });
        }

        return $query->exists();
    }

    /**
     * Check if user is banned from forum
     */
    public function isUserBanned(User $user, ?int $forumId = null): bool
    {
        $query = ForumBan::where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_permanent', true)
                  ->orWhere('expires_at', '>', now());
            });

        if ($forumId) {
            $query->where(function ($q) use ($forumId) {
                $q->where('forum_id', $forumId)->orWhereNull('forum_id');
            });
        }

        return $query->exists();
    }
}