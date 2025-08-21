@extends('layouts.admin')

@section('title', 'Forum Analytics Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Forum Analytics Dashboard</h1>
            <p class="text-gray-600 mt-2">Comprehensive insights into forum performance and user engagement</p>
        </div>
        <div class="flex space-x-4">
            <select id="period-selector" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="7" {{ $days == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ $days == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ $days == 90 ? 'selected' : '' }}>Last 90 days</option>
                <option value="365" {{ $days == 365 ? 'selected' : '' }}>Last year</option>
            </select>
            <button onclick="exportReport()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-download mr-2"></i>Export Report
            </button>
        </div>
    </div>

    <!-- Health Score Card -->
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-6 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold mb-2">Forum Health Score</h2>
                <div class="text-4xl font-bold">{{ $healthDashboard['health_score'] ?? 0 }}/100</div>
                <p class="text-blue-100 mt-2">Overall forum performance indicator</p>
            </div>
            <div class="text-right">
                <div class="text-sm opacity-90">
                    @if(($healthDashboard['health_score'] ?? 0) >= 80)
                        <span class="bg-green-500 px-3 py-1 rounded-full text-xs">Excellent</span>
                    @elseif(($healthDashboard['health_score'] ?? 0) >= 60)
                        <span class="bg-yellow-500 px-3 py-1 rounded-full text-xs">Good</span>
                    @else
                        <span class="bg-red-500 px-3 py-1 rounded-full text-xs">Needs Attention</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-users text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Users</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($overviewStats['active_users'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-comments text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Posts</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($overviewStats['posts_created'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-list text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Threads</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($overviewStats['threads_created'] ?? 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-eye text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Views</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format(($overviewStats['forum_views'] ?? 0) + ($overviewStats['thread_views'] ?? 0)) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Activity Trend Chart -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Daily Activity Trend</h3>
            <div class="h-64">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <!-- Engagement Chart -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">User Engagement</h3>
            <div class="h-64">
                <canvas id="engagementChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Content Performance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Popular Forums -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Popular Forums</h3>
            <div class="space-y-4">
                @foreach($contentPerformance['popular_forums'] ?? [] as $forum)
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">{{ $forum['name'] }}</p>
                        <p class="text-sm text-gray-500">{{ number_format($forum['views']) }} views</p>
                    </div>
                    <div class="w-24 bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ min(100, ($forum['views'] / max(1, collect($contentPerformance['popular_forums'])->max('views'))) * 100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Top Contributors -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Contributors</h3>
            <div class="space-y-4">
                @foreach($userEngagement['top_contributors'] ?? [] as $contributor)
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-600 text-sm"></i>
                        </div>
                        <div class="ml-3">
                            <p class="font-medium text-gray-900">{{ $contributor['user']['name'] ?? 'Unknown' }}</p>
                            <p class="text-sm text-gray-500">{{ $contributor['event_count'] }} posts</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Alerts and Recommendations -->
    @if(!empty($healthDashboard['alerts']) || !empty($healthDashboard['recommendations']))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Alerts -->
        @if(!empty($healthDashboard['alerts']))
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                Alerts
            </h3>
            <div class="space-y-3">
                @foreach($healthDashboard['alerts'] as $alert)
                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">{{ $alert['message'] }}</p>
                    @if(isset($alert['current']) && isset($alert['expected']))
                    <p class="text-xs text-yellow-600 mt-1">
                        Current: {{ $alert['current'] }} | Expected: {{ $alert['expected'] }}
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Recommendations -->
        @if(!empty($healthDashboard['recommendations']))
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-lightbulb text-blue-500 mr-2"></i>
                Recommendations
            </h3>
            <div class="space-y-3">
                @foreach($healthDashboard['recommendations'] as $recommendation)
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">{{ $recommendation['message'] }}</p>
                    @if(isset($recommendation['action']))
                    <p class="text-xs text-blue-600 mt-1">
                        Action: {{ $recommendation['action'] }}
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Period selector change handler
document.getElementById('period-selector').addEventListener('change', function() {
    const days = this.value;
    window.location.href = `{{ route('admin.forums.analytics.index') }}?days=${days}`;
});

// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    initializeActivityChart();
    initializeEngagementChart();
});

function initializeActivityChart() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    
    // Get activity data via AJAX
    fetch(`{{ route('admin.forums.analytics.trends') }}?metric_type=daily_active_users&days={{ $days }}`)
        .then(response => response.json())
        .then(data => {
            const labels = Object.keys(data.data);
            const values = Object.values(data.data);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Daily Active Users',
                        data: values,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading activity chart:', error));
}

function initializeEngagementChart() {
    const ctx = document.getElementById('engagementChart').getContext('2d');
    
    const data = {
        labels: ['Posts', 'Votes', 'Views', 'Searches'],
        datasets: [{
            data: [
                {{ $overviewStats['posts_created'] ?? 0 }},
                {{ $overviewStats['votes_cast'] ?? 0 }},
                {{ ($overviewStats['forum_views'] ?? 0) + ($overviewStats['thread_views'] ?? 0) }},
                {{ $overviewStats['searches_performed'] ?? 0 }}
            ],
            backgroundColor: [
                'rgba(34, 197, 94, 0.8)',
                'rgba(168, 85, 247, 0.8)',
                'rgba(249, 115, 22, 0.8)',
                'rgba(236, 72, 153, 0.8)'
            ]
        }]
    };
    
    new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function exportReport() {
    const days = document.getElementById('period-selector').value;
    window.open(`{{ route('admin.forums.analytics.report') }}?format=json&days=${days}&include_charts=true`, '_blank');
}
</script>
@endpush
@endsection