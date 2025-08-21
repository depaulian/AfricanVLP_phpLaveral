<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\ProfileAnalyticsService;
use App\Services\ProfileScoringService;
use App\Services\BehavioralAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProfileAnalyticsController extends Controller
{
    protected ProfileAnalyticsService $analyticsService;
    protected ProfileScoringService $scoringService;
    protected BehavioralAnalyticsService $behavioralService;

    public function __construct(
        ProfileAnalyticsService $analyticsService,
        ProfileScoringService $scoringService,
        BehavioralAnalyticsService $behavioralService
    ) {
        $this->analyticsService = $analyticsService;
        $this->scoringService = $scoringService;
        $this->behavioralService = $behavioralService;
        $this->middleware('auth');
    }

    /**
     * Display user's comprehensive profile analytics dashboard.
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        
        // Get comprehensive analytics data
        $analytics = $this->analyticsService->getUserProfileAnalytics($user);
        $profileScore = $this->scoringService->calculateComprehensiveScore($user);
        $behavioralAnalytics = $this->behavioralService->analyzeUserBehavior($user);
        $scoreHistory = $this->scoringService->getScoreHistory($user);
        
        return view('client.profile.analytics-dashboard', compact(
            'analytics',
            'profileScore',
            'behavioralAnalytics',
            'scoreHistory'
        ));
    }

    /**
     * Get profile score data for API consumption.
     */
    public function profileScore(): JsonResponse
    {
        $user = auth()->user();
        $profileScore = $this->scoringService->calculateComprehensiveScore($user);
        
        return response()->json([
            'success' => true,
            'data' => $profileScore
        ]);
    }

    /**
     * Get behavioral analytics data.
     */
    public function behavioralAnalytics(): JsonResponse
    {
        $user = auth()->user();
        $behavioralAnalytics = $this->behavioralService->analyzeUserBehavior($user);
        
        return response()->json([
            'success' => true,
            'data' => $behavioralAnalytics
        ]);
    }

    /**
     * Get profile score history.
     */
    public function scoreHistory(Request $request): JsonResponse
    {
        $user = auth()->user();
        $days = $request->get('days', 90);
        
        $scoreHistory = $this->scoringService->getScoreHistory($user, $days);
        
        return response()->json([
            'success' => true,
            'data' => $scoreHistory
        ]);
    }

    /**
     * Get activity heatmap data.
     */
    public function activityHeatmap(): JsonResponse
    {
        $user = auth()->user();
        $behavioralAnalytics = $this->behavioralService->analyzeUserBehavior($user);
        
        return response()->json([
            'success' => true,
            'data' => $behavioralAnalytics['activity_heatmap']
        ]);
    }

    /**
     * Get engagement trends.
     */
    public function engagementTrends(): JsonResponse
    {
        $user = auth()->user();
        $behavioralAnalytics = $this->behavioralService->analyzeUserBehavior($user);
        
        return response()->json([
            'success' => true,
            'data' => $behavioralAnalytics['engagement_patterns']
        ]);
    }

    /**
     * Get personalized recommendations.
     */
    public function recommendations(): JsonResponse
    {
        $user = auth()->user();
        
        $profileScore = $this->scoringService->calculateComprehensiveScore($user);
        $behavioralAnalytics = $this->behavioralService->analyzeUserBehavior($user);
        
        $recommendations = [
            'profile_improvements' => $profileScore['improvement_areas'],
            'behavioral_insights' => $behavioralAnalytics['behavioral_insights'],
            'journey_recommendations' => $behavioralAnalytics['user_journey']['stage_recommendations'],
            'predictive_actions' => $behavioralAnalytics['predictive_metrics']['recommended_actions'],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $recommendations
        ]);
    }

    /**
     * Get comparative analytics (user vs platform average).
     */
    public function comparative(): JsonResponse
    {
        $user = auth()->user();
        $analytics = $this->analyticsService->getUserProfileAnalytics($user);
        
        return response()->json([
            'success' => true,
            'data' => $analytics['comparative_data']
        ]);
    }

    /**
     * Export user's analytics data.
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:json,csv,pdf',
            'sections' => 'array',
            'sections.*' => 'in:profile_score,behavioral,engagement,recommendations'
        ]);

        $user = auth()->user();
        $format = $request->get('format');
        $sections = $request->get('sections', ['profile_score', 'behavioral', 'engagement']);

        $exportData = [];

        if (in_array('profile_score', $sections)) {
            $exportData['profile_score'] = $this->scoringService->calculateComprehensiveScore($user);
        }

        if (in_array('behavioral', $sections)) {
            $exportData['behavioral'] = $this->behavioralService->analyzeUserBehavior($user);
        }

        if (in_array('engagement', $sections)) {
            $exportData['engagement'] = $this->analyticsService->getUserProfileAnalytics($user);
        }

        if (in_array('recommendations', $sections)) {
            $profileScore = $this->scoringService->calculateComprehensiveScore($user);
            $behavioralAnalytics = $this->behavioralService->analyzeUserBehavior($user);
            
            $exportData['recommendations'] = [
                'profile_improvements' => $profileScore['improvement_areas'],
                'behavioral_insights' => $behavioralAnalytics['behavioral_insights'],
                'journey_recommendations' => $behavioralAnalytics['user_journey']['stage_recommendations'],
            ];
        }

        $filename = "profile_analytics_" . $user->id . "_" . now()->format('Y-m-d_H-i-s');

        switch ($format) {
            case 'json':
                return response()->json($exportData)
                    ->header('Content-Disposition', "attachment; filename={$filename}.json");

            case 'csv':
                $csvData = $this->convertToCsv($exportData);
                return response($csvData)
                    ->header('Content-Type', 'text/csv')
                    ->header('Content-Disposition', "attachment; filename={$filename}.csv");

            case 'pdf':
                // For PDF export, you'd typically use a package like DomPDF
                // For now, return JSON format
                return response()->json([
                    'success' => false,
                    'message' => 'PDF export not yet implemented'
                ], 501);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid export format'
        ], 400);
    }

    /**
     * Get insights summary for quick overview.
     */
    public function insightsSummary(): JsonResponse
    {
        $user = auth()->user();
        
        $profileScore = $this->scoringService->calculateComprehensiveScore($user);
        $behavioralAnalytics = $this->behavioralService->analyzeUserBehavior($user);
        
        $summary = [
            'overall_score' => $profileScore['total_score'],
            'grade' => $profileScore['grade'],
            'user_type' => null,
            'churn_risk' => $behavioralAnalytics['predictive_metrics']['churn_risk']['risk_level'],
            'engagement_trend' => $behavioralAnalytics['predictive_metrics']['engagement_prediction']['trend'],
            'top_strengths' => array_slice($profileScore['strengths'], 0, 3),
            'priority_improvements' => array_slice($profileScore['improvement_areas'], 0, 3),
            'next_milestone' => $profileScore['next_milestone'],
        ];

        // Extract user type from behavioral insights
        foreach ($behavioralAnalytics['behavioral_insights'] as $insight) {
            if ($insight['type'] === 'classification') {
                $summary['user_type'] = $insight['insight'];
                break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Convert analytics data to CSV format.
     */
    protected function convertToCsv(array $data): string
    {
        $csv = [];
        
        // Profile Score Section
        if (isset($data['profile_score'])) {
            $csv[] = ['Section', 'Profile Score'];
            $csv[] = ['Total Score', $data['profile_score']['total_score']];
            $csv[] = ['Grade', $data['profile_score']['grade']['letter']];
            $csv[] = ['Description', $data['profile_score']['grade']['description']];
            $csv[] = [];
            
            $csv[] = ['Category', 'Score'];
            foreach ($data['profile_score']['category_scores'] as $category => $score) {
                $csv[] = [ucfirst(str_replace('_', ' ', $category)), $score];
            }
            $csv[] = [];
        }

        // Behavioral Analytics Section
        if (isset($data['behavioral'])) {
            $csv[] = ['Section', 'Behavioral Analytics'];
            $csv[] = ['Most Active Period', $data['behavioral']['usage_patterns']['most_active_period']];
            $csv[] = ['Peak Hour', $data['behavioral']['usage_patterns']['peak_hour']];
            $csv[] = ['Engagement Level', $data['behavioral']['engagement_patterns']['engagement_level']];
            $csv[] = ['Churn Risk', $data['behavioral']['predictive_metrics']['churn_risk']['risk_level']];
            $csv[] = [];
        }

        // Convert array to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);
        
        return $csvString;
    }
}