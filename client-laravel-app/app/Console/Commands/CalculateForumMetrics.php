<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ForumAnalytic;
use App\Models\ForumMetric;
use App\Models\User;
use App\Models\Forum;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\ForumVote;
use Illuminate\Support\Facades\DB;

class CalculateForumMetrics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'forum:calculate-metrics {date? : The date to calculate metrics for (YYYY-MM-DD)}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate and store daily forum metrics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->argument('date') ?? now()->toDateString();
        
        $this->info("Calculating forum metrics for {$date}...");

        try {
            $this->calculateDailyActiveUsers($date);
            $this->calculateContentMetrics($date);
            $this->calculateEngagementMetrics($date);
            $this->calculateForumSpecificMetrics($date);
            $this->calculateUserMetrics($date);
            
            $this->info("✓ Forum metrics calculated successfully for {$date}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to calculate metrics: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Calculate daily active users
     */
    protected function calculateDailyActiveUsers(string $date): void
    {
        $this->line("Calculating daily active users...");
        
        $activeUsers = ForumAnalytic::whereDate('created_at', $date)
            ->distinct('user_id')
            ->whereNotNull('user_id')
            ->count();

        ForumMetric::record('daily_active_users', $activeUsers, $date);
        
        $this->info("  Daily active users: {$activeUsers}");
    }

    /**
     * Calculate content creation metrics
     */
    protected function calculateContentMetrics(string $date): void
    {
        $this->line("Calculating content metrics...");
        
        // Threads created
        $threadsCreated = ForumThread::whereDate('created_at', $date)->count();
        ForumMetric::record('threads_created', $threadsCreated, $date);
        $this->info("  Threads created: {$threadsCreated}");
        
        // Posts created
        $postsCreated = ForumPost::whereDate('created_at', $date)->count();
        ForumMetric::record('posts_created', $postsCreated, $date);
        $this->info("  Posts created: {$postsCreated}");
        
        // Attachments uploaded
        $attachmentsUploaded = \App\Models\ForumAttachment::whereDate('created_at', $date)->count();
        ForumMetric::record('attachments_uploaded', $attachmentsUploaded, $date);
        $this->info("  Attachments uploaded: {$attachmentsUploaded}");
    }

    /**
     * Calculate engagement metrics
     */
    protected function calculateEngagementMetrics(string $date): void
    {
        $this->line("Calculating engagement metrics...");
        
        // Votes cast
        $votesCast = ForumVote::whereDate('created_at', $date)->count();
        ForumMetric::record('votes_cast', $votesCast, $date);
        $this->info("  Votes cast: {$votesCast}");
        
        // Forum views
        $forumViews = ForumAnalytic::whereDate('created_at', $date)
            ->where('event_type', 'forum_view')
            ->count();
        ForumMetric::record('forum_views', $forumViews, $date);
        $this->info("  Forum views: {$forumViews}");
        
        // Thread views
        $threadViews = ForumAnalytic::whereDate('created_at', $date)
            ->where('event_type', 'thread_view')
            ->count();
        ForumMetric::record('thread_views', $threadViews, $date);
        $this->info("  Thread views: {$threadViews}");
        
        // Post views
        $postViews = ForumAnalytic::whereDate('created_at', $date)
            ->where('event_type', 'post_view')
            ->count();
        ForumMetric::record('post_views', $postViews, $date);
        $this->info("  Post views: {$postViews}");
        
        // Searches performed
        $searchesPerformed = ForumAnalytic::whereDate('created_at', $date)
            ->where('event_type', 'search_performed')
            ->count();
        ForumMetric::record('searches_performed', $searchesPerformed, $date);
        $this->info("  Searches performed: {$searchesPerformed}");
    }

    /**
     * Calculate forum-specific metrics
     */
    protected function calculateForumSpecificMetrics(string $date): void
    {
        $this->line("Calculating forum-specific metrics...");
        
        $forums = Forum::all();
        
        foreach ($forums as $forum) {
            // Forum views
            $forumViews = ForumAnalytic::whereDate('created_at', $date)
                ->where('event_type', 'forum_view')
                ->where('trackable_type', Forum::class)
                ->where('trackable_id', $forum->id)
                ->count();
            
            if ($forumViews > 0) {
                ForumMetric::record('forum_views', $forumViews, $date, $forum);
            }
            
            // Threads created in forum
            $threadsCreated = ForumThread::whereDate('created_at', $date)
                ->where('forum_id', $forum->id)
                ->count();
            
            if ($threadsCreated > 0) {
                ForumMetric::record('threads_created', $threadsCreated, $date, $forum);
            }
            
            // Posts created in forum
            $postsCreated = ForumPost::whereDate('created_at', $date)
                ->whereHas('thread', function ($query) use ($forum) {
                    $query->where('forum_id', $forum->id);
                })
                ->count();
            
            if ($postsCreated > 0) {
                ForumMetric::record('posts_created', $postsCreated, $date, $forum);
            }
            
            // Calculate engagement score for forum
            $engagementScore = ForumMetric::calculateEngagementScore($forum, $date);
            if ($engagementScore > 0) {
                ForumMetric::record('user_engagement_score', (int)$engagementScore, $date, $forum);
            }
        }
        
        $this->info("  Processed {$forums->count()} forums");
    }

    /**
     * Calculate user metrics
     */
    protected function calculateUserMetrics(string $date): void
    {
        $this->line("Calculating user metrics...");
        
        // New users
        $newUsers = User::whereDate('created_at', $date)->count();
        ForumMetric::record('new_users', $newUsers, $date);
        $this->info("  New users: {$newUsers}");
        
        // Returning users (users who were active before and are active today)
        $returningUsers = ForumAnalytic::whereDate('created_at', $date)
            ->whereNotNull('user_id')
            ->whereExists(function ($query) use ($date) {
                $query->select(DB::raw(1))
                    ->from('forum_analytics as fa2')
                    ->whereColumn('fa2.user_id', 'forum_analytics.user_id')
                    ->where('fa2.created_at', '<', $date);
            })
            ->distinct('user_id')
            ->count();
        
        ForumMetric::record('returning_users', $returningUsers, $date);
        $this->info("  Returning users: {$returningUsers}");
        
        // Calculate overall content quality score
        $qualityScore = $this->calculateContentQualityScore($date);
        ForumMetric::record('content_quality_score', (int)$qualityScore, $date);
        $this->info("  Content quality score: {$qualityScore}");
    }

    /**
     * Calculate content quality score for the date
     */
    protected function calculateContentQualityScore(string $date): float
    {
        $totalPosts = ForumPost::whereDate('created_at', $date)->count();
        
        if ($totalPosts == 0) {
            return 0;
        }
        
        $postsWithSolutions = ForumPost::whereDate('created_at', $date)
            ->where('is_solution', true)
            ->count();
        
        $postsWithVotes = ForumPost::whereDate('created_at', $date)
            ->whereHas('votes')
            ->count();
        
        $solutionRate = ($postsWithSolutions / $totalPosts) * 100;
        $engagementRate = ($postsWithVotes / $totalPosts) * 100;
        
        return round(($solutionRate * 0.6) + ($engagementRate * 0.4), 2);
    }
}