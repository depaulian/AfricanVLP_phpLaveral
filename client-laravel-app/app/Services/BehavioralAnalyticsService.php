<?php

namespace App\Services;

use App\Models\User;
use App\Models\ProfileActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class BehavioralAnalyticsService
{
    /**
     * Analyze user behavior patterns.
     */
    public function analyzeUserBehavior(User $user): array
    {
        return [
            'usage_patterns' => $this->getUsagePatterns($user),
            'engagement_patterns' => $this->getEngagementPatterns($user),
            'activity_heatmap' => $this->getActivityHeatmap($user),
            'session_analysis' => $this->getSessionAnalysis($user),
            'feature_usage' => $this->getFeatureUsage($user),
            'behavioral_insights' => $this->getBehavioralInsights($user),
            'user_journey' => $this->getUserJourney($user),
            'predictive_metrics' => $this->getPredictiveMetrics($user),
        ];
    }

    /**
     * Get usage patterns (when user is most active).
     */
    protected function getUsagePatterns(User $user): array
    {
        $activities = ProfileActivityLog::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(90))
            ->get();

        // Hour of day analysis
        $hourlyActivity = array_fill(0, 24, 0);
        foreach ($activities as $activity) {
            $hour = $activity->created_at->hour;
            $hourlyActivity[$hour]++;
        }

        // Day of week analysis
        $dailyActivity = array_fill(0, 7, 0);
        foreach ($activities as $activity) {
            $dayOfWeek = $activity->created_at->dayOfWeek;
            $dailyActivity[$dayOfWeek]++;
        }

        // Peak activity times
        $peakHour = array_keys($hourlyActivity, max($hourlyActivity))[0];
        $peakDay = array_keys($dailyActivity, max($dailyActivity))[0];

        return [
            'hourly_distribution' => $hourlyActivity,
            'daily_distribution' => $dailyActivity,
            'peak_hour' => $peakHour,
            'peak_day' => $peakDay,
            'most_active_period' => $this->getMostActivePeriod($hourlyActivity),
            'activity_consistency' => $this->calculateActivityConsistency($dailyActivity),
            'timezone_pattern' => $this->getTimezonePattern($user, $hourlyActivity),
        ];
    }

    /**
     * Get engagement patterns over time.
     */
    protected function getEngagementPatterns(User $user): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $ninetyDaysAgo = Carbon::now()->subDays(90);

        // Weekly engagement trend
        $weeklyEngagement = [];
        for ($i = 12; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
            
            $weeklyEngagement[] = [
                'week' => $weekStart->format('Y-m-d'),
                'activities' => ProfileActivityLog::where('user_id', $user->id)
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->count(),
                'unique_days' => ProfileActivityLog::where('user_id', $user->id)
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->selectRaw('DATE(created_at) as date')
                    ->distinct()
                    ->count(),
            ];
        }

        // Engagement velocity (rate of change)
        $recentEngagement = ProfileActivityLog::where('user_id', $user->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();
        
        $previousEngagement = ProfileActivityLog::where('user_id', $user->id)
            ->whereBetween('created_at', [$ninetyDaysAgo, $thirtyDaysAgo])
            ->count();

        $engagementVelocity = $previousEngagement > 0 
            ? (($recentEngagement - $previousEngagement) / $previousEngagement) * 100
            : 0;

        return [
            'weekly_trend' => $weeklyEngagement,
            'engagement_velocity' => round($engagementVelocity, 2),
            'engagement_level' => $this->classifyEngagementLevel($recentEngagement),
            'consistency_score' => $this->calculateEngagementConsistency($weeklyEngagement),
            'seasonal_patterns' => $this->getSeasonalPatterns($user),
        ];
    }

    /**
     * Generate activity heatmap data.
     */
    protected function getActivityHeatmap(User $user): array
    {
        $activities = ProfileActivityLog::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(90))
            ->selectRaw('HOUR(created_at) as hour, DAYOFWEEK(created_at) as day, COUNT(*) as count')
            ->groupBy('hour', 'day')
            ->get();

        // Initialize heatmap grid (24 hours x 7 days)
        $heatmap = [];
        for ($day = 1; $day <= 7; $day++) {
            for ($hour = 0; $hour < 24; $hour++) {
                $heatmap[$day][$hour] = 0;
            }
        }

        // Fill with actual data
        foreach ($activities as $activity) {
            $heatmap[$activity->day][$activity->hour] = $activity->count;
        }

        return [
            'data' => $heatmap,
            'max_activity' => $activities->max('count') ?? 0,
            'total_activities' => $activities->sum('count'),
            'active_hours' => $this->getActiveHours($heatmap),
            'quiet_periods' => $this->getQuietPeriods($heatmap),
        ];
    }

    /**
     * Analyze user sessions.
     */
    protected function getSessionAnalysis(User $user): array
    {
        $sessions = ProfileActivityLog::where('user_id', $user->id)
            ->where('activity_type', 'session')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->get();

        if ($sessions->isEmpty()) {
            return [
                'total_sessions' => 0,
                'average_duration' => 0,
                'total_time' => 0,
                'session_patterns' => [],
            ];
        }

        $durations = $sessions->pluck('duration_minutes')->filter()->toArray();
        
        return [
            'total_sessions' => $sessions->count(),
            'average_duration' => round(array_sum($durations) / count($durations), 2),
            'median_duration' => $this->calculateMedian($durations),
            'total_time' => array_sum($durations),
            'longest_session' => max($durations),
            'shortest_session' => min($durations),
            'session_frequency' => $sessions->count() / 30, // per day
            'bounce_rate' => $this->calculateBounceRate($sessions),
            'session_patterns' => $this->getSessionPatterns($sessions),
        ];
    }

    /**
     * Analyze feature usage patterns.
     */
    protected function getFeatureUsage(User $user): array
    {
        $activities = ProfileActivityLog::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('activity_type, COUNT(*) as usage_count')
            ->groupBy('activity_type')
            ->orderBy('usage_count', 'desc')
            ->get();

        $totalActivities = $activities->sum('usage_count');
        
        $featureUsage = $activities->map(function ($activity) use ($totalActivities) {
            return [
                'feature' => $activity->activity_type,
                'usage_count' => $activity->usage_count,
                'percentage' => round(($activity->usage_count / $totalActivities) * 100, 2),
                'category' => $this->categorizeFeature($activity->activity_type),
            ];
        })->toArray();

        return [
            'feature_breakdown' => $featureUsage,
            'most_used_feature' => $activities->first()?->activity_type,
            'feature_diversity' => $activities->count(),
            'core_features_usage' => $this->getCoreFeatureUsage($featureUsage),
            'advanced_features_usage' => $this->getAdvancedFeatureUsage($featureUsage),
            'unused_features' => $this->getUnusedFeatures($user, $activities->pluck('activity_type')->toArray()),
        ];
    }

    /**
     * Generate behavioral insights.
     */
    protected function getBehavioralInsights(User $user): array
    {
        $insights = [];
        $usagePatterns = $this->getUsagePatterns($user);
        $engagementPatterns = $this->getEngagementPatterns($user);
        $featureUsage = $this->getFeatureUsage($user);

        // Activity timing insights
        if ($usagePatterns['peak_hour'] >= 9 && $usagePatterns['peak_hour'] <= 17) {
            $insights[] = [
                'type' => 'timing',
                'insight' => 'Business Hours User',
                'description' => 'Most active during business hours, suggesting professional usage',
                'confidence' => 'high',
            ];
        } elseif ($usagePatterns['peak_hour'] >= 18 || $usagePatterns['peak_hour'] <= 6) {
            $insights[] = [
                'type' => 'timing',
                'insight' => 'Evening/Night User',
                'description' => 'Most active during evening/night hours',
                'confidence' => 'high',
            ];
        }

        // Engagement level insights
        if ($engagementPatterns['engagement_velocity'] > 50) {
            $insights[] = [
                'type' => 'engagement',
                'insight' => 'Increasing Engagement',
                'description' => 'User engagement is trending upward significantly',
                'confidence' => 'high',
            ];
        } elseif ($engagementPatterns['engagement_velocity'] < -30) {
            $insights[] = [
                'type' => 'engagement',
                'insight' => 'Declining Engagement',
                'description' => 'User engagement is declining, may need re-engagement',
                'confidence' => 'medium',
            ];
        }

        // Feature usage insights
        if ($featureUsage['feature_diversity'] < 3) {
            $insights[] = [
                'type' => 'feature_usage',
                'insight' => 'Limited Feature Exploration',
                'description' => 'User primarily uses basic features, could benefit from feature education',
                'confidence' => 'medium',
            ];
        }

        // User type classification
        $userType = $this->classifyUserType($user, $usagePatterns, $engagementPatterns, $featureUsage);
        $insights[] = [
            'type' => 'classification',
            'insight' => $userType['type'],
            'description' => $userType['description'],
            'confidence' => $userType['confidence'],
        ];

        return $insights;
    }

    /**
     * Map user journey stages.
     */
    protected function getUserJourney(User $user): array
    {
        $registrationDate = $user->created_at;
        $daysSinceRegistration = $registrationDate->diffInDays(now());
        
        $milestones = [
            'registration' => $registrationDate,
            'first_login' => ProfileActivityLog::where('user_id', $user->id)
                ->where('activity_type', 'login')
                ->oldest()
                ->first()?->created_at,
            'profile_completion' => ProfileActivityLog::where('user_id', $user->id)
                ->where('activity_type', 'profile_update')
                ->oldest()
                ->first()?->created_at,
            'first_document_upload' => $user->documents()->oldest()->first()?->created_at,
            'first_application' => ProfileActivityLog::where('user_id', $user->id)
                ->where('activity_type', 'application_submitted')
                ->oldest()
                ->first()?->created_at,
        ];

        $currentStage = $this->determineCurrentStage($user, $milestones);
        $nextStage = $this->getNextStage($currentStage);

        return [
            'days_since_registration' => $daysSinceRegistration,
            'milestones' => $milestones,
            'current_stage' => $currentStage,
            'next_stage' => $nextStage,
            'journey_progress' => $this->calculateJourneyProgress($milestones),
            'stage_recommendations' => $this->getStageRecommendations($currentStage),
        ];
    }

    /**
     * Generate predictive metrics.
     */
    protected function getPredictiveMetrics(User $user): array
    {
        $recentActivity = ProfileActivityLog::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->count();
        
        $previousActivity = ProfileActivityLog::where('user_id', $user->id)
            ->whereBetween('created_at', [Carbon::now()->subDays(14), Carbon::now()->subDays(7)])
            ->count();

        // Churn risk calculation
        $churnRisk = $this->calculateChurnRisk($user, $recentActivity, $previousActivity);
        
        // Engagement prediction
        $engagementPrediction = $this->predictEngagement($user, $recentActivity, $previousActivity);
        
        // Success likelihood
        $successLikelihood = $this->calculateSuccessLikelihood($user);

        return [
            'churn_risk' => $churnRisk,
            'engagement_prediction' => $engagementPrediction,
            'success_likelihood' => $successLikelihood,
            'recommended_actions' => $this->getRecommendedActions($churnRisk, $engagementPrediction),
        ];
    }

    // Helper methods
    protected function getMostActivePeriod(array $hourlyActivity): string
    {
        $maxActivity = max($hourlyActivity);
        $peakHours = array_keys($hourlyActivity, $maxActivity);
        
        if (!empty(array_intersect($peakHours, range(6, 11)))) return 'morning';
        if (!empty(array_intersect($peakHours, range(12, 17)))) return 'afternoon';
        if (!empty(array_intersect($peakHours, range(18, 23)))) return 'evening';
        return 'night';
    }

    protected function calculateActivityConsistency(array $dailyActivity): float
    {
        if (empty($dailyActivity)) return 0;
        
        $mean = array_sum($dailyActivity) / count($dailyActivity);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $dailyActivity)) / count($dailyActivity);
        
        $standardDeviation = sqrt($variance);
        $coefficientOfVariation = $mean > 0 ? ($standardDeviation / $mean) * 100 : 0;
        
        // Lower coefficient of variation means higher consistency
        return max(0, 100 - $coefficientOfVariation);
    }

    protected function getTimezonePattern(User $user, array $hourlyActivity): array
    {
        $profile = $user->profile;
        $userTimezone = $profile?->timezone ?? 'UTC';
        
        // Analyze if activity pattern matches expected timezone
        $peakHours = array_keys($hourlyActivity, max($hourlyActivity));
        $expectedBusinessHours = range(9, 17);
        
        $matchesTimezone = !empty(array_intersect($peakHours, $expectedBusinessHours));
        
        return [
            'user_timezone' => $userTimezone,
            'matches_timezone' => $matchesTimezone,
            'confidence' => $matchesTimezone ? 'high' : 'low',
        ];
    }

    protected function classifyEngagementLevel(int $activityCount): string
    {
        if ($activityCount >= 50) return 'very_high';
        if ($activityCount >= 30) return 'high';
        if ($activityCount >= 15) return 'medium';
        if ($activityCount >= 5) return 'low';
        return 'very_low';
    }

    protected function calculateEngagementConsistency(array $weeklyEngagement): float
    {
        if (empty($weeklyEngagement)) return 0;
        
        $activities = array_column($weeklyEngagement, 'activities');
        $mean = array_sum($activities) / count($activities);
        
        if ($mean == 0) return 0;
        
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $activities)) / count($activities);
        
        $standardDeviation = sqrt($variance);
        $coefficientOfVariation = ($standardDeviation / $mean) * 100;
        
        return max(0, 100 - $coefficientOfVariation);
    }

    protected function getSeasonalPatterns(User $user): array
    {
        // Analyze activity patterns across different months
        $monthlyActivity = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->startOfMonth()->copy();
            $monthEnd = $month->endOfMonth()->copy();
            
            $activity = ProfileActivityLog::where('user_id', $user->id)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count();
            
            $monthlyActivity[$month->format('M Y')] = $activity;
        }
        
        return [
            'monthly_distribution' => $monthlyActivity,
            'peak_month' => array_keys($monthlyActivity, max($monthlyActivity))[0] ?? null,
            'low_month' => array_keys($monthlyActivity, min($monthlyActivity))[0] ?? null,
        ];
    }

    protected function getActiveHours(array $heatmap): array
    {
        $activeHours = [];
        foreach ($heatmap as $day => $hours) {
            foreach ($hours as $hour => $activity) {
                if ($activity > 0) {
                    $activeHours[] = ['day' => $day, 'hour' => $hour, 'activity' => $activity];
                }
            }
        }
        
        // Sort by activity level
        usort($activeHours, function($a, $b) {
            return $b['activity'] - $a['activity'];
        });
        
        return array_slice($activeHours, 0, 10); // Top 10 active hours
    }

    protected function getQuietPeriods(array $heatmap): array
    {
        $quietPeriods = [];
        foreach ($heatmap as $day => $hours) {
            $consecutiveQuiet = 0;
            $quietStart = null;
            
            foreach ($hours as $hour => $activity) {
                if ($activity == 0) {
                    if ($quietStart === null) {
                        $quietStart = $hour;
                    }
                    $consecutiveQuiet++;
                } else {
                    if ($consecutiveQuiet >= 3) { // 3+ consecutive quiet hours
                        $quietPeriods[] = [
                            'day' => $day,
                            'start_hour' => $quietStart,
                            'end_hour' => $hour - 1,
                            'duration' => $consecutiveQuiet,
                        ];
                    }
                    $consecutiveQuiet = 0;
                    $quietStart = null;
                }
            }
        }
        
        return $quietPeriods;
    }

    protected function calculateMedian(array $values): float
    {
        if (empty($values)) return 0;
        
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        } else {
            return $values[$middle];
        }
    }

    protected function calculateBounceRate(Collection $sessions): float
    {
        $shortSessions = $sessions->filter(function($session) {
            return ($session->duration_minutes ?? 0) < 2; // Less than 2 minutes
        })->count();
        
        return $sessions->count() > 0 ? ($shortSessions / $sessions->count()) * 100 : 0;
    }

    protected function getSessionPatterns(Collection $sessions): array
    {
        $patterns = [
            'short' => $sessions->filter(fn($s) => ($s->duration_minutes ?? 0) < 5)->count(),
            'medium' => $sessions->filter(fn($s) => ($s->duration_minutes ?? 0) >= 5 && ($s->duration_minutes ?? 0) < 30)->count(),
            'long' => $sessions->filter(fn($s) => ($s->duration_minutes ?? 0) >= 30)->count(),
        ];
        
        return $patterns;
    }

    protected function categorizeFeature(string $activityType): string
    {
        $categories = [
            'core' => ['login', 'profile_view', 'profile_update'],
            'social' => ['message_sent', 'connection_request', 'endorsement'],
            'content' => ['document_upload', 'post_created', 'comment_added'],
            'advanced' => ['search_advanced', 'export_data', 'api_usage'],
        ];
        
        foreach ($categories as $category => $types) {
            if (in_array($activityType, $types)) {
                return $category;
            }
        }
        
        return 'other';
    }

    protected function getCoreFeatureUsage(array $featureUsage): array
    {
        return array_filter($featureUsage, function($feature) {
            return $feature['category'] === 'core';
        });
    }

    protected function getAdvancedFeatureUsage(array $featureUsage): array
    {
        return array_filter($featureUsage, function($feature) {
            return $feature['category'] === 'advanced';
        });
    }

    protected function getUnusedFeatures(User $user, array $usedFeatures): array
    {
        $allFeatures = [
            'profile_update', 'document_upload', 'skill_endorsement',
            'message_sent', 'search_advanced', 'export_data',
            'connection_request', 'testimonial_given'
        ];
        
        return array_diff($allFeatures, $usedFeatures);
    }

    protected function classifyUserType(User $user, array $usagePatterns, array $engagementPatterns, array $featureUsage): array
    {
        $score = 0;
        $factors = [];
        
        // Engagement level factor
        if ($engagementPatterns['engagement_level'] === 'high') {
            $score += 30;
            $factors[] = 'high engagement';
        }
        
        // Feature diversity factor
        if ($featureUsage['feature_diversity'] >= 5) {
            $score += 25;
            $factors[] = 'diverse feature usage';
        }
        
        // Activity consistency factor
        if ($usagePatterns['activity_consistency'] >= 70) {
            $score += 20;
            $factors[] = 'consistent activity';
        }
        
        // Business hours usage factor
        if ($usagePatterns['most_active_period'] === 'morning' || $usagePatterns['most_active_period'] === 'afternoon') {
            $score += 15;
            $factors[] = 'business hours usage';
        }
        
        // Advanced features factor
        if (!empty($featureUsage['advanced_features_usage'])) {
            $score += 10;
            $factors[] = 'advanced features';
        }
        
        // Classify based on score
        if ($score >= 70) {
            return [
                'type' => 'Power User',
                'description' => 'Highly engaged user with diverse feature usage and consistent activity patterns',
                'confidence' => 'high',
                'factors' => $factors,
            ];
        } elseif ($score >= 50) {
            return [
                'type' => 'Active User',
                'description' => 'Regular user with good engagement and moderate feature usage',
                'confidence' => 'medium',
                'factors' => $factors,
            ];
        } elseif ($score >= 30) {
            return [
                'type' => 'Casual User',
                'description' => 'Occasional user with basic feature usage',
                'confidence' => 'medium',
                'factors' => $factors,
            ];
        } else {
            return [
                'type' => 'New/Inactive User',
                'description' => 'Limited engagement and feature usage',
                'confidence' => 'high',
                'factors' => $factors,
            ];
        }
    }

    protected function determineCurrentStage(User $user, array $milestones): string
    {
        if (!$milestones['first_application']) return 'profile_building';
        if (!$milestones['first_document_upload']) return 'document_submission';
        if (!$milestones['profile_completion']) return 'profile_completion';
        if (!$milestones['first_login']) return 'activation';
        return 'onboarding';
    }

    protected function getNextStage(string $currentStage): array
    {
        $stages = [
            'onboarding' => [
                'stage' => 'activation',
                'description' => 'Complete first login and explore the platform',
                'actions' => ['Log in to your account', 'Complete platform tour'],
            ],
            'activation' => [
                'stage' => 'profile_completion',
                'description' => 'Complete your profile information',
                'actions' => ['Add profile photo', 'Complete bio', 'Add skills'],
            ],
            'profile_completion' => [
                'stage' => 'document_submission',
                'description' => 'Upload and verify documents',
                'actions' => ['Upload identity documents', 'Submit professional certificates'],
            ],
            'document_submission' => [
                'stage' => 'profile_building',
                'description' => 'Build comprehensive profile',
                'actions' => ['Add volunteering history', 'Connect with others', 'Apply for opportunities'],
            ],
            'profile_building' => [
                'stage' => 'engagement',
                'description' => 'Active platform engagement',
                'actions' => ['Regular profile updates', 'Community participation', 'Skill development'],
            ],
        ];
        
        return $stages[$currentStage] ?? [
            'stage' => 'mastery',
            'description' => 'Platform mastery and leadership',
            'actions' => ['Mentor others', 'Lead initiatives', 'Share expertise'],
        ];
    }

    protected function calculateJourneyProgress(array $milestones): float
    {
        $totalMilestones = count($milestones);
        $completedMilestones = count(array_filter($milestones));
        
        return $totalMilestones > 0 ? ($completedMilestones / $totalMilestones) * 100 : 0;
    }

    protected function getStageRecommendations(string $currentStage): array
    {
        $recommendations = [
            'onboarding' => [
                'Complete your first login to activate your account',
                'Take the platform tour to understand available features',
                'Set up your notification preferences',
            ],
            'activation' => [
                'Upload a professional profile photo',
                'Write a compelling bio highlighting your interests',
                'Add your skills and expertise areas',
            ],
            'profile_completion' => [
                'Upload identity verification documents',
                'Add professional certificates or qualifications',
                'Complete all required profile sections',
            ],
            'document_submission' => [
                'Add your volunteering history and experiences',
                'Connect with other users in your field',
                'Start applying for volunteering opportunities',
            ],
            'profile_building' => [
                'Keep your profile information up to date',
                'Engage with the community through forums',
                'Seek skill endorsements from connections',
            ],
        ];
        
        return $recommendations[$currentStage] ?? [
            'Continue active engagement with the platform',
            'Share your expertise with the community',
            'Mentor new users and help them succeed',
        ];
    }

    protected function calculateChurnRisk(User $user, int $recentActivity, int $previousActivity): array
    {
        $risk = 'low';
        $score = 0;
        $factors = [];
        
        // Activity decline factor
        if ($previousActivity > 0) {
            $decline = (($previousActivity - $recentActivity) / $previousActivity) * 100;
            if ($decline > 70) {
                $score += 40;
                $factors[] = 'Significant activity decline';
                $risk = 'high';
            } elseif ($decline > 40) {
                $score += 25;
                $factors[] = 'Moderate activity decline';
                $risk = 'medium';
            }
        }
        
        // Last login factor
        $lastLogin = ProfileActivityLog::where('user_id', $user->id)
            ->where('activity_type', 'login')
            ->latest()
            ->first();
        
        if ($lastLogin) {
            $daysSinceLogin = $lastLogin->created_at->diffInDays(now());
            if ($daysSinceLogin > 30) {
                $score += 30;
                $factors[] = 'No login for over 30 days';
                $risk = 'high';
            } elseif ($daysSinceLogin > 14) {
                $score += 15;
                $factors[] = 'No login for over 2 weeks';
                $risk = $risk === 'low' ? 'medium' : $risk;
            }
        }
        
        // Profile completion factor
        $profileCompletion = app(ProfileAnalyticsService::class)
            ->calculateProfileCompletionScore($user)['total_score'];
        
        if ($profileCompletion < 50) {
            $score += 20;
            $factors[] = 'Low profile completion';
        }
        
        return [
            'risk_level' => $risk,
            'risk_score' => min($score, 100),
            'factors' => $factors,
            'confidence' => count($factors) > 2 ? 'high' : 'medium',
        ];
    }

    protected function predictEngagement(User $user, int $recentActivity, int $previousActivity): array
    {
        $trend = 'stable';
        $prediction = 'maintain';
        
        if ($previousActivity > 0) {
            $change = (($recentActivity - $previousActivity) / $previousActivity) * 100;
            
            if ($change > 20) {
                $trend = 'increasing';
                $prediction = 'increase';
            } elseif ($change < -20) {
                $trend = 'decreasing';
                $prediction = 'decrease';
            }
        } elseif ($recentActivity > 0) {
            $trend = 'new_activity';
            $prediction = 'increase';
        }
        
        return [
            'trend' => $trend,
            'prediction' => $prediction,
            'confidence' => abs($recentActivity - $previousActivity) > 5 ? 'high' : 'medium',
            'next_week_estimate' => $this->estimateNextWeekActivity($recentActivity, $previousActivity),
        ];
    }

    protected function calculateSuccessLikelihood(User $user): array
    {
        $score = 0;
        $factors = [];
        
        // Profile completion factor
        $profileCompletion = app(ProfileAnalyticsService::class)
            ->calculateProfileCompletionScore($user)['total_score'];
        
        if ($profileCompletion >= 80) {
            $score += 30;
            $factors[] = 'High profile completion';
        } elseif ($profileCompletion >= 60) {
            $score += 20;
            $factors[] = 'Good profile completion';
        }
        
        // Document verification factor
        $verifiedDocs = $user->documents()->where('verification_status', 'verified')->count();
        if ($verifiedDocs >= 2) {
            $score += 25;
            $factors[] = 'Multiple verified documents';
        } elseif ($verifiedDocs >= 1) {
            $score += 15;
            $factors[] = 'At least one verified document';
        }
        
        // Engagement factor
        $recentActivity = ProfileActivityLog::where('user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();
        
        if ($recentActivity >= 20) {
            $score += 25;
            $factors[] = 'High recent activity';
        } elseif ($recentActivity >= 10) {
            $score += 15;
            $factors[] = 'Moderate recent activity';
        }
        
        // Skills and connections factor
        $skillsCount = $user->skills()->count();
        $connectionsCount = $user->connections()->count();
        
        if ($skillsCount >= 5 && $connectionsCount >= 3) {
            $score += 20;
            $factors[] = 'Good skills and network';
        } elseif ($skillsCount >= 3 || $connectionsCount >= 1) {
            $score += 10;
            $factors[] = 'Basic skills or network';
        }
        
        // Determine likelihood
        $likelihood = 'low';
        if ($score >= 70) $likelihood = 'high';
        elseif ($score >= 50) $likelihood = 'medium';
        
        return [
            'likelihood' => $likelihood,
            'score' => $score,
            'factors' => $factors,
            'areas_for_improvement' => $this->getImprovementAreas($score, $factors),
        ];
    }

    protected function getRecommendedActions(array $churnRisk, array $engagementPrediction): array
    {
        $actions = [];
        
        if ($churnRisk['risk_level'] === 'high') {
            $actions[] = [
                'priority' => 'urgent',
                'action' => 'Re-engagement Campaign',
                'description' => 'Send personalized re-engagement email with platform updates',
            ];
            $actions[] = [
                'priority' => 'high',
                'action' => 'Profile Completion Assistance',
                'description' => 'Offer guided profile completion session',
            ];
        }
        
        if ($engagementPrediction['prediction'] === 'decrease') {
            $actions[] = [
                'priority' => 'medium',
                'action' => 'Feature Introduction',
                'description' => 'Introduce unused features that match user interests',
            ];
        }
        
        if ($engagementPrediction['prediction'] === 'increase') {
            $actions[] = [
                'priority' => 'low',
                'action' => 'Advanced Features',
                'description' => 'Introduce advanced features for power users',
            ];
        }
        
        return $actions;
    }

    protected function estimateNextWeekActivity(int $recent, int $previous): int
    {
        if ($previous == 0) return max(1, $recent);
        
        $trend = ($recent - $previous) / $previous;
        $estimate = $recent * (1 + $trend * 0.5); // Dampen the trend
        
        return max(0, round($estimate));
    }

    protected function getImprovementAreas(int $score, array $factors): array
    {
        $areas = [];
        
        if (!in_array('High profile completion', $factors)) {
            $areas[] = 'Complete profile information';
        }
        
        if (!in_array('Multiple verified documents', $factors)) {
            $areas[] = 'Upload and verify documents';
        }
        
        if (!in_array('High recent activity', $factors)) {
            $areas[] = 'Increase platform engagement';
        }
        
        if (!in_array('Good skills and network', $factors)) {
            $areas[] = 'Build skills profile and connections';
        }
        
        return $areas;
    }
}