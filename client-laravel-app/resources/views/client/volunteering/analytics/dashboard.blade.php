@extends('layouts.client')

@section('title', 'Volunteering Analytics Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Volunteering Analytics</h1>
                <p class="text-gray-600">Track volunteer engagement, impact, and performance metrics</p>
            </div>
            
            <!-- Filters -->
            <div class="mt-4 md:mt-0 flex flex-wrap gap-4">
                <form method="GET" class="flex flex-wrap gap-4">
                    <!-- Organization Filter -->
                    @if($organizations->count() > 1)
                    <select name="organization_id" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Organizations</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ $organizationId == $org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                    @endif
                    
                    <!-- Period Filter -->
                    <select name="period" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach($periods as $value => $label)
                            <option value="{{ $value }}" {{ $filters['period'] == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    
                    <!-- Category Filter -->
                    <select name="metric_category" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach($metricCategories as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['metric_category'] ?? '') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Apply Filters
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    @if(isset($dashboardData['summary_metrics']))
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
        @foreach($dashboardData['summary_metrics'] as $metricKey => $metric)
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">
                        {{ ucwords(str_replace('_', ' ', $metricKey)) }}
                    </p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ $metric['formatted_value'] }}
                    </p>
                </div>
                <div class="flex-shrink-0">
                    @if(isset($metric['trend']))
                        @if($metric['trend']['direction'] === 'up')
                            <div class="flex items-center text-green-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                @if($metric['trend']['percentage'])
                                    <span class="text-sm font-medium">{{ number_format($metric['trend']['percentage'], 1) }}%</span>
                                @endif
                            </div>
                        @elseif($metric['trend']['direction'] === 'down')
                            <div class="flex items-center text-red-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                @if($metric['trend']['percentage'])
                                    <span class="text-sm font-medium">{{ number_format($metric['trend']['percentage'], 1) }}%</span>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm font-medium">0%</span>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Charts and Detailed Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Performance Trends Chart -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Trends</h3>
            <div class="h-64 flex items-center justify-center text-gray-500">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p>Performance trends chart will be displayed here</p>
                </div>
            </div>
        </div>

        <!-- Engagement Metrics -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Engagement Overview</h3>
            <div class="h-64 flex items-center justify-center text-gray-500">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                    <p>Engagement metrics chart will be displayed here</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Impact Overview and Demographics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        <!-- Impact Overview -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Impact Overview</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Total Impact Score</span>
                    <span class="font-semibold">{{ $dashboardData['summary_metrics']['impact_score']['formatted_value'] ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Hours Contributed</span>
                    <span class="font-semibold">{{ $dashboardData['summary_metrics']['hours_logged']['formatted_value'] ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Active Volunteers</span>
                    <span class="font-semibold">{{ $dashboardData['summary_metrics']['volunteer_count']['formatted_value'] ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Performers</h3>
            <div class="space-y-3">
                @if(isset($dashboardData['top_performers']) && count($dashboardData['top_performers']) > 0)
                    @foreach(array_slice($dashboardData['top_performers'], 0, 5) as $performer)
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-blue-600">{{ substr($performer['name'], 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $performer['name'] }}</p>
                            <p class="text-sm text-gray-500">{{ $performer['hours'] }} hours</p>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p class="text-sm text-gray-500">No performance data available</p>
                @endif
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activities</h3>
            <div class="space-y-3">
                @if(isset($dashboardData['recent_activities']) && count($dashboardData['recent_activities']) > 0)
                    @foreach(array_slice($dashboardData['recent_activities'], 0, 5) as $activity)
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-2 h-2 bg-blue-400 rounded-full mt-2"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-900">{{ $activity['description'] }}</p>
                            <p class="text-xs text-gray-500">{{ $activity['time'] }}</p>
                        </div>
                    </div>
                    @endforeach
                @else
                    <p class="text-sm text-gray-500">No recent activities</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4">
        <a href="{{ route('client.volunteering.analytics.metrics') }}" 
           class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            View Detailed Metrics
        </a>
        
        @if($organizations->count() > 1)
        <a href="{{ route('client.volunteering.analytics.comparison') }}" 
           class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
            Compare Organizations
        </a>
        @endif
        
        <a href="{{ route('client.volunteering.analytics.scheduled-reports') }}" 
           class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
            Scheduled Reports
        </a>
        
        <button onclick="generateReport()" 
                class="bg-orange-600 text-white px-6 py-2 rounded-md hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500">
            Generate Report
        </button>
    </div>
</div>

<!-- Report Generation Modal -->
<div id="reportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Generate Report</h3>
                
                <form id="reportForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                            <select name="report_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="volunteer_performance">Volunteer Performance</option>
                                <option value="impact_summary">Impact Summary</option>
                                <option value="engagement_metrics">Engagement Metrics</option>
                                <option value="comparative_analysis">Comparative Analysis</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Format</label>
                            <select name="format" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                                <option value="csv">CSV</option>
                                <option value="html">HTML</option>
                            </select>
                        </div>
                        
                        @if($organizationId)
                        <input type="hidden" name="organization_id" value="{{ $organizationId }}">
                        @endif
                        
                        <input type="hidden" name="config" value='{"period": "{{ $filters['period'] }}"}'>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeReportModal()" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function generateReport() {
    document.getElementById('reportModal').classList.remove('hidden');
}

function closeReportModal() {
    document.getElementById('reportModal').classList.add('hidden');
}

document.getElementById('reportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    data.config = JSON.parse(data.config);
    
    fetch('{{ route("client.volunteering.analytics.generate-report") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert('Error: ' + data.error);
        } else {
            alert('Report generation started. You will receive an email when it\'s ready.');
            closeReportModal();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while generating the report.');
    });
});

// Auto-refresh data every 5 minutes
setInterval(function() {
    fetch('{{ route("client.volunteering.analytics.api-data") }}?' + new URLSearchParams({
        organization_id: '{{ $organizationId }}',
        period: '{{ $filters["period"] }}',
        metric_category: '{{ $filters["metric_category"] ?? "" }}'
    }))
    .then(response => response.json())
    .then(data => {
        // Update summary metrics
        // This would update the dashboard with fresh data
        console.log('Data refreshed:', data);
    })
    .catch(error => console.error('Error refreshing data:', error));
}, 300000); // 5 minutes
</script>
@endpush
@endsection