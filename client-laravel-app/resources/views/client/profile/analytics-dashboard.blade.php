@extends('layouts.client')

@section('title', 'Profile Analytics Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Profile Analytics Dashboard</h1>
        <p class="text-gray-600">Comprehensive insights into your profile performance and engagement</p>
    </div>

    <!-- Profile Score Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Profile Score Overview</h2>
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <div class="text-4xl font-bold text-{{ $profileScore['grade']['color'] }}-600">
                            {{ $profileScore['total_score'] }}
                        </div>
                        <div class="text-lg text-gray-600">
                            Grade: {{ $profileScore['grade']['letter'] }} - {{ $profileScore['grade']['description'] }}
                        </div>
                    </div>
                    <div class="w-32 h-32">
                        <canvas id="scoreChart"></canvas>
                    </div>
                </div>
                
                <!-- Category Breakdown -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($profileScore['category_scores'] as $category => $score)
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-2xl font-bold text-gray-800">{{ $score }}</div>
                        <div class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $category) }}</div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $score }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Next Milestone -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-3">Next Milestone</h3>
                <div class="mb-3">
                    <div class="text-xl font-bold text-blue-600">{{ $profileScore['next_milestone']['title'] }}</div>
                    <div class="text-sm text-gray-600">{{ $profileScore['next_milestone']['description'] }}</div>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between text-sm">
                        <span>Progress</span>
                        <span>{{ number_format($profileScore['next_milestone']['progress_percentage'], 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $profileScore['next_milestone']['progress_percentage'] }}%"></div>
                    </div>
                </div>
                <div class="text-sm text-gray-600">
                    {{ $profileScore['next_milestone']['points_needed'] }} points needed
                </div>
            </div>

            <!-- User Type Classification -->
            @if(isset($behavioralAnalytics['behavioral_insights']))
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-3">User Classification</h3>
                @foreach($behavioralAnalytics['behavioral_insights'] as $insight)
                    @if($insight['type'] === 'classification')
                    <div class="text-center">
                        <div class="text-lg font-bold text-purple-600">{{ $insight['insight'] }}</div>
                        <div class="text-sm text-gray-600 mt-1">{{ $insight['description'] }}</div>
                        <div class="mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                {{ ucfirst($insight['confidence']) }} Confidence
                            </span>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
    </div>

    <!-- Behavioral Analytics -->
    @if(isset($behavioralAnalytics))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Usage Patterns -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Usage Patterns</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium">Most Active Period</span>
                        <span class="text-sm text-gray-600 capitalize">{{ $behavioralAnalytics['usage_patterns']['most_active_period'] }}</span>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium">Peak Hour</span>
                        <span class="text-sm text-gray-600">{{ $behavioralAnalytics['usage_patterns']['peak_hour'] }}:00</span>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium">Activity Consistency</span>
                        <span class="text-sm text-gray-600">{{ number_format($behavioralAnalytics['usage_patterns']['activity_consistency'], 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $behavioralAnalytics['usage_patterns']['activity_consistency'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Engagement Trends -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Engagement Trends</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium">Engagement Level</span>
                        <span class="text-sm text-gray-600 capitalize">{{ $behavioralAnalytics['engagement_patterns']['engagement_level'] }}</span>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium">Engagement Velocity</span>
                        <span class="text-sm {{ $behavioralAnalytics['engagement_patterns']['engagement_velocity'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $behavioralAnalytics['engagement_patterns']['engagement_velocity'] > 0 ? '+' : '' }}{{ number_format($behavioralAnalytics['engagement_patterns']['engagement_velocity'], 1) }}%
                        </span>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm font-medium">Consistency Score</span>
                        <span class="text-sm text-gray-600">{{ number_format($behavioralAnalytics['engagement_patterns']['consistency_score'], 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: {{ $behavioralAnalytics['engagement_patterns']['consistency_score'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Heatmap -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">Activity Heatmap</h3>
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full">
                <div class="grid grid-cols-25 gap-1 text-xs">
                    <!-- Header row with hours -->
                    <div></div>
                    @for($hour = 0; $hour < 24; $hour++)
                        <div class="text-center text-gray-500 p-1">{{ $hour }}</div>
                    @endfor
                    
                    <!-- Days and activity data -->
                    @php
                        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        $maxActivity = $behavioralAnalytics['activity_heatmap']['max_activity'];
                    @endphp
                    @foreach($behavioralAnalytics['activity_heatmap']['data'] as $dayIndex => $hours)
                        <div class="text-gray-500 p-1 text-right">{{ $days[$dayIndex - 1] ?? '' }}</div>
                        @foreach($hours as $hour => $activity)
                            @php
                                $intensity = $maxActivity > 0 ? ($activity / $maxActivity) : 0;
                                $opacity = max(0.1, $intensity);
                            @endphp
                            <div class="w-4 h-4 rounded-sm" 
                                 style="background-color: rgba(59, 130, 246, {{ $opacity }})"
                                 title="Day {{ $dayIndex }}, Hour {{ $hour }}: {{ $activity }} activities">
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
            <span>Less active</span>
            <div class="flex space-x-1">
                @for($i = 1; $i <= 5; $i++)
                    <div class="w-3 h-3 rounded-sm" style="background-color: rgba(59, 130, 246, {{ $i * 0.2 }})"></div>
                @endfor
            </div>
            <span>More active</span>
        </div>
    </div>
    @endif

    <!-- Predictive Analytics -->
    @if(isset($behavioralAnalytics['predictive_metrics']))
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Churn Risk -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Churn Risk Analysis</h3>
            <div class="text-center mb-4">
                @php
                    $riskColors = ['low' => 'green', 'medium' => 'yellow', 'high' => 'red'];
                    $riskColor = $riskColors[$behavioralAnalytics['predictive_metrics']['churn_risk']['risk_level']] ?? 'gray';
                @endphp
                <div class="text-3xl font-bold text-{{ $riskColor }}-600 mb-2">
                    {{ ucfirst($behavioralAnalytics['predictive_metrics']['churn_risk']['risk_level']) }}
                </div>
                <div class="text-sm text-gray-600">
                    Risk Score: {{ $behavioralAnalytics['predictive_metrics']['churn_risk']['risk_score'] }}/100
                </div>
            </div>
            @if(!empty($behavioralAnalytics['predictive_metrics']['churn_risk']['factors']))
            <div class="space-y-2">
                <div class="text-sm font-medium text-gray-700">Risk Factors:</div>
                @foreach($behavioralAnalytics['predictive_metrics']['churn_risk']['factors'] as $factor)
                <div class="text-xs text-gray-600 bg-gray-50 rounded px-2 py-1">{{ $factor }}</div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- Engagement Prediction -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Engagement Prediction</h3>
            <div class="text-center mb-4">
                @php
                    $trendColors = ['increasing' => 'green', 'stable' => 'blue', 'decreasing' => 'red'];
                    $trendColor = $trendColors[$behavioralAnalytics['predictive_metrics']['engagement_prediction']['trend']] ?? 'gray';
                @endphp
                <div class="text-2xl font-bold text-{{ $trendColor }}-600 mb-2 capitalize">
                    {{ str_replace('_', ' ', $behavioralAnalytics['predictive_metrics']['engagement_prediction']['trend']) }}
                </div>
                <div class="text-sm text-gray-600">
                    Next Week Estimate: {{ $behavioralAnalytics['predictive_metrics']['engagement_prediction']['next_week_estimate'] }} activities
                </div>
            </div>
            <div class="text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ ucfirst($behavioralAnalytics['predictive_metrics']['engagement_prediction']['confidence']) }} Confidence
                </span>
            </div>
        </div>

        <!-- Success Likelihood -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Success Likelihood</h3>
            <div class="text-center mb-4">
                @php
                    $successColors = ['high' => 'green', 'medium' => 'yellow', 'low' => 'red'];
                    $successColor = $successColors[$behavioralAnalytics['predictive_metrics']['success_likelihood']['likelihood']] ?? 'gray';
                @endphp
                <div class="text-3xl font-bold text-{{ $successColor }}-600 mb-2">
                    {{ ucfirst($behavioralAnalytics['predictive_metrics']['success_likelihood']['likelihood']) }}
                </div>
                <div class="text-sm text-gray-600">
                    Score: {{ $behavioralAnalytics['predictive_metrics']['success_likelihood']['score'] }}/100
                </div>
            </div>
            @if(!empty($behavioralAnalytics['predictive_metrics']['success_likelihood']['factors']))
            <div class="space-y-2">
                <div class="text-sm font-medium text-gray-700">Success Factors:</div>
                @foreach(array_slice($behavioralAnalytics['predictive_metrics']['success_likelihood']['factors'], 0, 3) as $factor)
                <div class="text-xs text-gray-600 bg-green-50 rounded px-2 py-1">{{ $factor }}</div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Recommendations and Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Improvement Areas -->
        @if(!empty($profileScore['improvement_areas']))
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Areas for Improvement</h3>
            <div class="space-y-3">
                @foreach($profileScore['improvement_areas'] as $area)
                <div class="flex items-start space-x-3 p-3 bg-yellow-50 rounded-lg">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-900 capitalize">{{ $area['category'] }}</div>
                        <div class="text-sm text-gray-600">Score: {{ $area['score'] }}/100</div>
                        @if(!empty($area['suggestions']))
                        <ul class="mt-2 text-xs text-gray-600 space-y-1">
                            @foreach(array_slice($area['suggestions'], 0, 2) as $suggestion)
                            <li>• {{ $suggestion }}</li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Behavioral Insights -->
        @if(isset($behavioralAnalytics['behavioral_insights']))
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold mb-4">Behavioral Insights</h3>
            <div class="space-y-3">
                @foreach($behavioralAnalytics['behavioral_insights'] as $insight)
                    @if($insight['type'] !== 'classification')
                    <div class="flex items-start space-x-3 p-3 bg-blue-50 rounded-lg">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $insight['insight'] }}</div>
                            <div class="text-sm text-gray-600">{{ $insight['description'] }}</div>
                            <div class="mt-1">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ ucfirst($insight['confidence']) }} confidence
                                </span>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- User Journey -->
    @if(isset($behavioralAnalytics['user_journey']))
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h3 class="text-lg font-semibold mb-4">User Journey Progress</h3>
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium">Overall Progress</span>
                <span class="text-sm text-gray-600">{{ number_format($behavioralAnalytics['user_journey']['journey_progress'], 1) }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full" style="width: {{ $behavioralAnalytics['user_journey']['journey_progress'] }}%"></div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium text-gray-900 mb-3">Current Stage</h4>
                <div class="p-4 bg-blue-50 rounded-lg">
                    <div class="text-lg font-semibold text-blue-900 capitalize">{{ str_replace('_', ' ', $behavioralAnalytics['user_journey']['current_stage']) }}</div>
                    <div class="text-sm text-blue-700 mt-1">
                        {{ $behavioralAnalytics['user_journey']['days_since_registration'] }} days since registration
                    </div>
                </div>
            </div>
            
            <div>
                <h4 class="font-medium text-gray-900 mb-3">Next Stage</h4>
                <div class="p-4 bg-green-50 rounded-lg">
                    <div class="text-lg font-semibold text-green-900">{{ $behavioralAnalytics['user_journey']['next_stage']['stage'] }}</div>
                    <div class="text-sm text-green-700 mt-1">{{ $behavioralAnalytics['user_journey']['next_stage']['description'] }}</div>
                </div>
            </div>
        </div>

        @if(!empty($behavioralAnalytics['user_journey']['stage_recommendations']))
        <div class="mt-6">
            <h4 class="font-medium text-gray-900 mb-3">Recommended Actions</h4>
            <ul class="space-y-2">
                @foreach($behavioralAnalytics['user_journey']['stage_recommendations'] as $recommendation)
                <li class="flex items-start space-x-2">
                    <svg class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm text-gray-700">{{ $recommendation }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    <!-- Score History Chart -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold mb-4">Profile Score History</h3>
        <div class="h-64">
            <canvas id="scoreHistoryChart"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Profile Score Donut Chart
const scoreCtx = document.getElementById('scoreChart').getContext('2d');
new Chart(scoreCtx, {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [{{ $profileScore['total_score'] }}, {{ 100 - $profileScore['total_score'] }}],
            backgroundColor: ['#3B82F6', '#E5E7EB'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Score History Line Chart
const historyCtx = document.getElementById('scoreHistoryChart').getContext('2d');
new Chart(historyCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode(array_column($scoreHistory, 'date')) !!},
        datasets: [{
            label: 'Profile Score',
            data: {!! json_encode(array_column($scoreHistory, 'score')) !!},
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
@endpush
@endsection