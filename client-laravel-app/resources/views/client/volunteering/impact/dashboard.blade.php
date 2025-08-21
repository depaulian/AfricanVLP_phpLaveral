@extends('layouts.client')

@section('title', 'Impact Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Your Volunteer Impact</h1>
        <p class="text-gray-600">Track and visualize the positive change you're making in the community</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] }}" 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] }}" 
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="organization_id" class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                <select id="organization_id" name="organization_id" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Organizations</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>
                            {{ $org->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <i class="fas fa-chart-line text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Records</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $dashboardData['total_impact_records'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <i class="fas fa-building text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Organizations</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $dashboardData['total_organizations_impacted'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <i class="fas fa-trophy text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Achievements</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $dashboardData['recent_achievements']->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <i class="fas fa-heart text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Impact Score</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($dashboardData['summary_stats']->sum('total_value')) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Impact by Category -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Impact by Category</h3>
            @if($dashboardData['impact_by_category']->isNotEmpty())
                <div class="space-y-4">
                    @foreach($dashboardData['impact_by_category'] as $category => $categoryData)
                        <div class="border-l-4 border-blue-500 pl-4">
                            <h4 class="font-medium text-gray-900 capitalize">{{ $category }} Impact</h4>
                            <p class="text-sm text-gray-600 mb-2">{{ $categoryData['total_records'] }} records</p>
                            <div class="space-y-2">
                                @foreach($categoryData['metrics'] as $metricName => $metricData)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-700">{{ $metricName }}</span>
                                        <span class="text-sm font-medium text-gray-900">{{ $metricData['formatted_total'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-chart-pie text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No impact data available for the selected period.</p>
                </div>
            @endif
        </div>

        <!-- Recent Impact Timeline -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Impact</h3>
            @if(!empty($dashboardData['impact_timeline']))
                <div class="space-y-4">
                    @foreach(array_slice($dashboardData['impact_timeline'], 0, 5) as $item)
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ $item['metric'] }}</p>
                                <p class="text-sm text-gray-600">{{ $item['value'] }} • {{ $item['organization'] }}</p>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($item['date'])->format('M j, Y') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 pt-4 border-t">
                    <a href="{{ route('client.volunteering.impact.records') }}" 
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        View all records →
                    </a>
                </div>
            @else
                <div class="text-center py-8">
                    <i class="fas fa-clock text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No recent impact records.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Achievements -->
    @if($dashboardData['recent_achievements']->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Achievements</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($dashboardData['recent_achievements'] as $userAchievement)
                    <div class="flex items-center space-x-3 p-3 bg-yellow-50 rounded-lg">
                        <div class="flex-shrink-0">
                            <i class="fas fa-medal text-yellow-600 text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $userAchievement->achievement->name }}</p>
                            <p class="text-xs text-gray-600">{{ $userAchievement->earned_at->format('M j, Y') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4">
        <a href="{{ route('client.volunteering.impact.create') }}" 
           class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <i class="fas fa-plus mr-2"></i>
            Record New Impact
        </a>
        
        <a href="{{ route('client.volunteering.impact.records') }}" 
           class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
            <i class="fas fa-list mr-2"></i>
            View All Records
        </a>
        
        <a href="{{ route('client.volunteering.impact.report') }}" 
           class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
            <i class="fas fa-chart-bar mr-2"></i>
            Generate Report
        </a>
        
        <a href="{{ route('client.volunteering.impact.stories') }}" 
           class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500">
            <i class="fas fa-book mr-2"></i>
            Impact Stories
        </a>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filters change
    const filterForm = document.querySelector('form');
    const filterInputs = filterForm.querySelectorAll('select, input[type="date"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Add a small delay to allow for multiple quick changes
            clearTimeout(this.submitTimeout);
            this.submitTimeout = setTimeout(() => {
                filterForm.submit();
            }, 500);
        });
    });
});
</script>
@endpush