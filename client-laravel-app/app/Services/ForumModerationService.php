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
     * Report content for moderation
     */
    public function reportContent($reportable, User $reporter, string $reason, string $severity = 'medium'): bool
    {
        try {
            // Check if user has already reported this content
            $existingReport = ForumReport::where('reportable_type', get_class($reportable))
                ->where('reportable_id', $reportable->id)
                ->where('reporter_id', $reporter->id)
                ->where('status', 'pending')
                ->exists();

            if ($existingReport) {
                return false; // Already reported
            }

            ForumReport::create([
                'reportable_type' => get_class($reportable),
                'reportable_id' => $reportable->id,
                'reporter_id' => $reporter->id,
                'reason' => $reason,
                'severity' => $severity,
                'status' => 'pending'
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if user can moderate forum
     */
    public function canModerate(User $user, Forum $forum): bool
    {
        // Check if user is admin
        if ($user->hasRole('admin')) {
            return true;
        }

        // Check if user is global moderator
        if ($user->hasRole('moderator')) {
            return true;
        }

        // Check if user is forum-specific moderator
        $moderatorIds = $forum->moderator_ids ?? [];
        return in_array($user->id, $moderatorIds);
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

    /**
     * Get user's active warnings
     */
    public function getUserWarnings(User $user): Collection
    {
        return ForumWarning::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with('moderator')
            ->latest()
            ->get();
    }

    /**
     * Get user's suspension info
     */
    public function getUserSuspension(User $user, ?int $forumId = null): ?ForumSuspension
    {
        $query = ForumSuspension::where('user_id', $user->id)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with('moderator');

        if ($forumId) {
            $query->where(function ($q) use ($forumId) {
                $q->where('forum_id', $forumId)->orWhereNull('forum_id');
            });
        }

        return $query->first();
    }

    /**
     * Get user's ban info
     */
    public function getUserBan(User $user, ?int $forumId = null): ?ForumBan
    {
        $query = ForumBan::where('user_id', $user->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_permanent', true)
                  ->orWhere('expires_at', '>', now());
            })
            ->with('moderator');

        if ($forumId) {
            $query->where(function ($q) use ($forumId) {
                $q->where('forum_id', $forumId)->orWhereNull('forum_id');
            });
        }

        return $query->first();
    }

    /**
     * Check if user can post in forum
     */
    public function canUserPost(User $user, Forum $forum): array
    {
        // Check if banned
        if ($this->isUserBanned($user, $forum->id)) {
            $ban = $this->getUserBan($user, $forum->id);
            return [
                'can_post' => false,
                'reason' => 'banned',
                'message' => 'You are banned from this forum.',
                'details' => $ban
            ];
        }

        // Check if suspended
        if ($this->isUserSuspended($user, $forum->id)) {
            $suspension = $this->getUserSuspension($user, $forum->id);
            return [
                'can_post' => false,
                'reason' => 'suspended',
                'message' => 'You are suspended from this forum until ' . $suspension->expires_at->format('M j, Y g:i A'),
                'details' => $suspension
            ];
        }

        return [
            'can_post' => true,
            'reason' => null,
            'message' => null,
            'details' => null
        ];
    }

    /**
     * Check if user can reply to thread
     */
    public function canUserReply(User $user, ForumThread $thread): array
    {
        // Check if thread is locked
        if ($thread->is_locked && !$this->canModerate($user, $thread->forum)) {
            return [
                'can_reply' => false,
                'reason' => 'locked',
                'message' => 'This thread is locked and no longer accepts replies.'
            ];
        }

        // Check forum posting permissions
        $forumCheck = $this->canUserPost($user, $thread->forum);
        if (!$forumCheck['can_post']) {
            return [
                'can_reply' => false,
                'reason' => $forumCheck['reason'],
                'message' => $forumCheck['message'],
                'details' => $forumCheck['details']
            ];
        }

        return [
            'can_reply' => true,
            'reason' => null,
            'message' => null
        ];
    }

    /**
     * Get moderation status for content
     */
    public function getContentModerationStatus($content): array
    {
        $reports = ForumReport::where('reportable_type', get_class($content))
            ->where('reportable_id', $content->id)
            ->count();

        $pendingReports = ForumReport::where('reportable_type', get_class($content))
            ->where('reportable_id', $content->id)
            ->where('status', 'pending')
            ->count();

        return [
            'total_reports' => $reports,
            'pending_reports' => $pendingReports,
            'is_reported' => $reports > 0,
            'needs_attention' => $pendingReports > 0
        ];
    }

    /**
     * Get available report reasons
     */
    public function getReportReasons(): array
    {
        return [
            'spam' => 'Spam or unwanted commercial content',
            'harassment' => 'Harassment or bullying',
            'hate_speech' => 'Hate speech or discrimination',
            'inappropriate' => 'Inappropriate or offensive content',
            'off_topic' => 'Off-topic or irrelevant content',
            'misinformation' => 'False or misleading information',
            'copyright' => 'Copyright violation',
            'personal_info' => 'Sharing personal information',
            'duplicate' => 'Duplicate content',
            'other' => 'Other (please specify)'
        ];
    }

    /**
     * Get user's moderation history
     */
    public function getUserModerationHistory(User $user): array
    {
        return [
            'warnings' => ForumWarning::where('user_id', $user->id)
                ->with('moderator')
                ->latest()
                ->limit(10)
                ->get(),
            'suspensions' => ForumSuspension::where('user_id', $user->id)
                ->with('moderator')
                ->latest()
                ->limit(5)
                ->get(),
            'bans' => ForumBan::where('user_id', $user->id)
                ->with('moderator')
                ->latest()
                ->limit(5)
                ->get(),
            'reports_made' => ForumReport::where('reporter_id', $user->id)
                ->with('reportable')
                ->latest()
                ->limit(10)
                ->get(),
            'reports_received' => $this->getReportsAgainstUser($user)
        ];
    }

    /**
     * Get reports made against user's content
     */
    private function getReportsAgainstUser(User $user): Collection
    {
        $threadReports = ForumReport::where('reportable_type', ForumThread::class)
            ->whereHas('reportable', function ($query) use ($user) {
                $query->where('author_id', $user->id);
            })
            ->with('reportable', 'reporter')
            ->get();

        $postReports = ForumReport::where('reportable_type', ForumPost::class)
            ->whereHas('reportable', function ($query) use ($user) {
                $query->where('author_id', $user->id);
            })
            ->with('reportable', 'reporter')
            ->get();

        return $threadReports->merge($postReports)->sortByDesc('created_at');
    }

    /**
     * Check if content needs moderation approval
     */
    public function needsModerationApproval(User $user, Forum $forum): bool
    {
        // New users might need approval
        if ($user->created_at->gt(now()->subDays(7))) {
            return true;
        }

        // Users with recent warnings might need approval
        $recentWarnings = ForumWarning::where('user_id', $user->id)
            ->where('created_at', '>', now()->subDays(30))
            ->count();

        if ($recentWarnings > 0) {
            return true;
        }

        return false;
    }

    /**
     * Auto-moderate content based on rules
     */
    public function autoModerateContent(string $content, User $author): array
    {
        $flags = [];
        $action = 'approve'; // default action

        // Check for spam patterns
        if ($this->detectSpam($content)) {
            $flags[] = 'potential_spam';
            $action = 'flag';
        }

        // Check for inappropriate language
        if ($this->detectInappropriateLanguage($content)) {
            $flags[] = 'inappropriate_language';
            $action = 'flag';
        }

        // Check for excessive caps
        if ($this->detectExcessiveCaps($content)) {
            $flags[] = 'excessive_caps';
        }

        // Check for excessive links
        if ($this->detectExcessiveLinks($content)) {
            $flags[] = 'excessive_links';
            $action = 'flag';
        }

        return [
            'action' => $action,
            'flags' => $flags,
            'confidence' => $this->calculateConfidence($flags)
        ];
    }

    /**
     * Detect spam patterns in content
     */
    private function detectSpam(string $content): bool
    {
        // Simple spam detection patterns
        $spamPatterns = [
            '/\b(buy now|click here|limited time|act now)\b/i',
            '/\b(viagra|cialis|pharmacy)\b/i',
            '/\$\d+.*\b(earn|make|profit)\b/i',
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect inappropriate language
     */
    private function detectInappropriateLanguage(string $content): bool
    {
        // This would typically use a more sophisticated profanity filter
        $inappropriateWords = [
            // Add inappropriate words here
        ];

        $words = str_word_count(strtolower($content), 1);
        
        foreach ($inappropriateWords as $word) {
            if (in_array($word, $words)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect excessive caps
     */
    private function detectExcessiveCaps(string $content): bool
    {
        $totalChars = strlen($content);
        if ($totalChars < 20) return false;

        $capsChars = strlen(preg_replace('/[^A-Z]/', '', $content));
        $capsRatio = $capsChars / $totalChars;

        return $capsRatio > 0.7; // More than 70% caps
    }

    /**
     * Detect excessive links
     */
    private function detectExcessiveLinks(string $content): bool
    {
        $linkCount = preg_match_all('/https?:\/\/[^\s]+/', $content);
        return $linkCount > 3; // More than 3 links
    }

    /**
     * Calculate confidence score for auto-moderation
     */
    private function calculateConfidence(array $flags): float
    {
        if (empty($flags)) return 1.0;

        $weights = [
            'potential_spam' => 0.8,
            'inappropriate_language' => 0.9,
            'excessive_caps' => 0.3,
            'excessive_links' => 0.6
        ];

        $totalWeight = 0;
        foreach ($flags as $flag) {
            $totalWeight += $weights[$flag] ?? 0.5;
        }

        return min(1.0, $totalWeight / count($flags));
    }
}