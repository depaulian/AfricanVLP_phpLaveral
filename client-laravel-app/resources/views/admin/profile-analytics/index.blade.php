@extends('layouts.admin')

@section('title', 'Profile Analytics Dashboard')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Profile Analytics Dashboard</h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="refreshBtn">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-download"></i> Export
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" data-export="engagement" data-format="csv">Engagement (CSV)</a></li>
                    <li><a class="dropdown-item" href="#" data-export="completion" data-format="csv">Completion (CSV)</a></li>
                    <li><a class="dropdown-item" href="#" data-export="demographics" data-format="csv">Demographics (CSV)</a></li>
                    <li><a class="dropdown-item" href="#" data-export="performance" data-format="csv">Performance (CSV)</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-export="engagement" data-format="json">All Data (JSON)</a></li>
                </ul>
            </div>
            <button type="button" class="btn btn-warning" id="clearCacheBtn">
                <i class="fas fa-trash"></i> Clear Cache
            </button>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalUsers">
                                {{ number_format($dashboardData['key_metrics']['total_users']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Users (30d)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeUsers30d">
                                {{ number_format($dashboardData['key_metrics']['active_users_30d']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Profile Completion Rate</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800" id="profileCompletionRate">
                                        {{ $dashboardData['quick_stats']['profile_completion_rate'] }}%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: {{ $dashboardData['quick_stats']['profile_completion_rate'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">New Users (7d)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="newUsers7d">
                                {{ number_format($dashboardData['key_metrics']['new_users_7d']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- User Growth Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">User Growth Trends</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow">
                            <a class="dropdown-item" href="#" data-period="7">Last 7 days</a>
                            <a class="dropdown-item" href="#" data-period="30">Last 30 days</a>
                            <a class="dropdown-item" href="#" data-period="90">Last 90 days</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="userGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Completion Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Profile Completion Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="profileCompletionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Tabs -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detailed Analytics</h6>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="analyticsTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="engagement-tab" data-bs-toggle="tab" data-bs-target="#engagement" type="button" role="tab">
                                <i class="fas fa-chart-line"></i> User Engagement
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="completion-tab" data-bs-toggle="tab" data-bs-target="#completion" type="button" role="tab">
                                <i class="fas fa-tasks"></i> Profile Completion
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="behavior-tab" data-bs-toggle="tab" data-bs-target="#behavior" type="button" role="tab">
                                <i class="fas fa-user-clock"></i> User Behavior
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="demographics-tab" data-bs-toggle="tab" data-bs-target="#demographics" type="button" role="tab">
                                <i class="fas fa-chart-pie"></i> Demographics
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="performance-tab" data-bs-toggle="tab" data-bs-target="#performance" type="button" role="tab">
                                <i class="fas fa-tachometer-alt"></i> Performance
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="analyticsTabContent">
                        <!-- User Engagement Tab -->
                        <div class="tab-pane fade show active" id="engagement" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="engagementStartDate">Start Date:</label>
                                        <input type="date" class="form-control" id="engagementStartDate" value="{{ now()->subDays(30)->format('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="engagementEndDate">End Date:</label>
                                        <input type="date" class="form-control" id="engagementEndDate" value="{{ now()->format('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary mb-3" id="loadEngagementBtn">Load Engagement Data</button>
                            <div id="engagementContent">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Completion Tab -->
                        <div class="tab-pane fade" id="completion" role="tabpanel">
                            <button type="button" class="btn btn-primary mb-3" id="loadCompletionBtn">Load Completion Data</button>
                            <div id="completionContent">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Behavior Tab -->
                        <div class="tab-pane fade" id="behavior" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="behaviorStartDate">Start Date:</label>
                                        <input type="date" class="form-control" id="behaviorStartDate" value="{{ now()->subDays(30)->format('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="behaviorEndDate">End Date:</label>
                                        <input type="date" class="form-control" id="behaviorEndDate" value="{{ now()->format('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary mb-3" id="loadBehaviorBtn">Load Behavior Data</button>
                            <div id="behaviorContent">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Demographics Tab -->
                        <div class="tab-pane fade" id="demographics" role="tabpanel">
                            <button type="button" class="btn btn-primary mb-3" id="loadDemographicsBtn">Load Demographics Data</button>
                            <div id="demographicsContent">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Tab -->
                        <div class="tab-pane fade" id="performance" role="tabpanel">
                            <button type="button" class="btn btn-primary mb-3" id="loadPerformanceBtn">Load Performance Data</button>
                            <div id="performanceContent">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Profile Activity</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="recentActivityTable">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>User</th>
                                    <th>Target User</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dashboardData['recent_activity'] as $activity)
                                <tr>
                                    <td>
                                        <i class="{{ $activity['activity_type'] === 'profile_viewed' ? 'fas fa-eye text-primary' : 'fas fa-edit text-success' }}"></i>
                                        {{ ucwords(str_replace('_', ' ', $activity['activity_type'])) }}
                                    </td>
                                    <td>{{ $activity['user_name'] }}</td>
                                    <td>{{ $activity['target_user_name'] ?? '-' }}</td>
                                    <td>{{ $activity['created_at'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center">
                <div class="spinner-border" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Processing...</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Initialize charts
    initializeCharts();
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        refreshDashboard();
    }, 300000);
    
    // Event handlers
    $('#refreshBtn').click(function() {
        refreshDashboard();
    });
    
    $('#clearCacheBtn').click(function() {
        clearAnalyticsCache();
    });
    
    // Export handlers
    $('.dropdown-item[data-export]').click(function(e) {
        e.preventDefault();
        const type = $(this).data('export');
        const format = $(this).data('format');
        exportData(type, format);
    });
    
    // Tab load handlers
    $('#loadEngagementBtn').click(loadEngagementData);
    $('#loadCompletionBtn').click(loadCompletionData);
    $('#loadBehaviorBtn').click(loadBehaviorData);
    $('#loadDemographicsBtn').click(loadDemographicsData);
    $('#loadPerformanceBtn').click(loadPerformanceData);
    
    // Load initial data for active tab
    loadEngagementData();
});

function initializeCharts() {
    // User Growth Chart
    const growthCtx = document.getElementById('userGrowthChart').getContext('2d');
    const growthData = @json($dashboardData['growth_trends']);
    
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: Object.keys(growthData),
            datasets: [{
                label: 'New Users',
                data: Object.values(growthData),
                borderColor: 'rgb(78, 115, 223)',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                borderWidth: 2,
                fill: true
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
}

function refreshDashboard() {
    $.ajax({
        url: '{{ route("admin.profile-analytics.realtime-updates") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateKeyMetrics(response.data);
                updateRecentActivity(response.data.recent_activity);
            }
        },
        error: function() {
            showNotification('Failed to refresh dashboard', 'error');
        }
    });
}

function updateKeyMetrics(data) {
    $('#totalUsers').text(data.key_metrics.total_users.toLocaleString());
    $('#activeUsers30d').text(data.key_metrics.active_users_30d.toLocaleString());
    $('#newUsers7d').text(data.key_metrics.new_users_7d.toLocaleString());
    $('#profileCompletionRate').text(data.quick_stats.profile_completion_rate + '%');
}

function loadEngagementData() {
    const startDate = $('#engagementStartDate').val();
    const endDate = $('#engagementEndDate').val();
    
    $('#engagementContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
    
    $.ajax({
        url: '{{ route("admin.profile-analytics.user-engagement") }}',
        method: 'GET',
        data: { start_date: startDate, end_date: endDate },
        success: function(response) {
            if (response.success) {
                renderEngagementData(response.data);
            }
        },
        error: function() {
            $('#engagementContent').html('<div class="alert alert-danger">Failed to load engagement data</div>');
        }
    });
}

function loadCompletionData() {
    $('#completionContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
    
    $.ajax({
        url: '{{ route("admin.profile-analytics.profile-completion") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderCompletionData(response.data);
            }
        },
        error: function() {
            $('#completionContent').html('<div class="alert alert-danger">Failed to load completion data</div>');
        }
    });
}

function loadBehaviorData() {
    const startDate = $('#behaviorStartDate').val();
    const endDate = $('#behaviorEndDate').val();
    
    $('#behaviorContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
    
    $.ajax({
        url: '{{ route("admin.profile-analytics.user-behavior") }}',
        method: 'GET',
        data: { start_date: startDate, end_date: endDate },
        success: function(response) {
            if (response.success) {
                renderBehaviorData(response.data);
            }
        },
        error: function() {
            $('#behaviorContent').html('<div class="alert alert-danger">Failed to load behavior data</div>');
        }
    });
}

function loadDemographicsData() {
    $('#demographicsContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
    
    $.ajax({
        url: '{{ route("admin.profile-analytics.demographics") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderDemographicsData(response.data);
            }
        },
        error: function() {
            $('#demographicsContent').html('<div class="alert alert-danger">Failed to load demographics data</div>');
        }
    });
}

function loadPerformanceData() {
    $('#performanceContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
    
    $.ajax({
        url: '{{ route("admin.profile-analytics.profile-performance") }}',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderPerformanceData(response.data);
            }
        },
        error: function() {
            $('#performanceContent').html('<div class="alert alert-danger">Failed to load performance data</div>');
        }
    });
}

function renderEngagementData(data) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h5>User Metrics</h5>
                <table class="table table-sm">
                    <tr><td>Total Users:</td><td>${data.user_metrics.total_users.toLocaleString()}</td></tr>
                    <tr><td>Active Users:</td><td>${data.user_metrics.active_users.toLocaleString()}</td></tr>
                    <tr><td>New Users:</td><td>${data.user_metrics.new_users.toLocaleString()}</td></tr>
                    <tr><td>Engagement Rate:</td><td>${data.user_metrics.engagement_rate}%</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Profile Metrics</h5>
                <table class="table table-sm">
                    <tr><td>Completed Profiles:</td><td>${data.profile_metrics.completed_profiles.toLocaleString()}</td></tr>
                    <tr><td>Completion Rate:</td><td>${data.profile_metrics.profile_completion_rate}%</td></tr>
                    <tr><td>Profile Views:</td><td>${data.profile_metrics.profile_views.toLocaleString()}</td></tr>
                    <tr><td>Profile Updates:</td><td>${data.profile_metrics.profile_updates.toLocaleString()}</td></tr>
                </table>
            </div>
        </div>
    `;
    $('#engagementContent').html(html);
}

function renderCompletionData(data) {
    let html = '<div class="row">';
    
    Object.keys(data.field_completion_rates).forEach(category => {
        html += `<div class="col-md-6 mb-4">
            <h5>${category.replace('_', ' ').toUpperCase()}</h5>
            <table class="table table-sm">`;
        
        Object.keys(data.field_completion_rates[category]).forEach(field => {
            const stats = data.field_completion_rates[category][field];
            html += `<tr>
                <td>${field.replace('_', ' ')}</td>
                <td>${stats.count.toLocaleString()}</td>
                <td>${stats.percentage}%</td>
            </tr>`;
        });
        
        html += '</table></div>';
    });
    
    html += '</div>';
    $('#completionContent').html(html);
}

function renderBehaviorData(data) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h5>Session Analytics</h5>
                <table class="table table-sm">
                    <tr><td>Avg Session Duration:</td><td>${data.session_analytics.average_session_duration_minutes} min</td></tr>
                    <tr><td>Bounce Rate:</td><td>${data.session_analytics.bounce_rate_percentage}%</td></tr>
                </table>
                
                <h5>Update Patterns</h5>
                <table class="table table-sm">
                    <tr><td>Avg Updates per User:</td><td>${data.update_patterns.average_updates_per_user}</td></tr>
                    <tr><td>Total Profile Updates:</td><td>${data.update_patterns.total_profile_updates.toLocaleString()}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Common Activities</h5>
                <table class="table table-sm">`;
    
    Object.keys(data.common_activities).forEach(activity => {
        html += `<tr><td>${activity.replace('_', ' ')}</td><td>${data.common_activities[activity].toLocaleString()}</td></tr>`;
    });
    
    html += '</table></div></div>';
    $('#behaviorContent').html(html);
}

function renderDemographicsData(data) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h5>Age Distribution</h5>
                <table class="table table-sm">`;
    
    Object.keys(data.age_distribution).forEach(range => {
        html += `<tr><td>${range}</td><td>${data.age_distribution[range].toLocaleString()}</td></tr>`;
    });
    
    html += `</table>
                <h5>Gender Distribution</h5>
                <table class="table table-sm">`;
    
    Object.keys(data.gender_distribution).forEach(gender => {
        html += `<tr><td>${gender}</td><td>${data.gender_distribution[gender].toLocaleString()}</td></tr>`;
    });
    
    html += `</table></div>
            <div class="col-md-6">
                <h5>Top Countries</h5>
                <table class="table table-sm">`;
    
    Object.keys(data.geographic_distribution.countries).forEach(country => {
        html += `<tr><td>${country}</td><td>${data.geographic_distribution.countries[country].toLocaleString()}</td></tr>`;
    });
    
    html += '</table></div></div>';
    $('#demographicsContent').html(html);
}

function renderPerformanceData(data) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <h5>View Metrics</h5>
                <table class="table table-sm">
                    <tr><td>Total Profile Views:</td><td>${data.view_metrics.total_profile_views.toLocaleString()}</td></tr>
                    <tr><td>Unique Profile Views:</td><td>${data.view_metrics.unique_profile_views.toLocaleString()}</td></tr>
                    <tr><td>Avg Views per Profile:</td><td>${data.view_metrics.average_views_per_profile}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Most Viewed Profiles</h5>
                <table class="table table-sm">`;
    
    data.most_viewed_profiles.forEach(profile => {
        html += `<tr><td>${profile.user_name}</td><td>${profile.view_count.toLocaleString()}</td></tr>`;
    });
    
    html += '</table></div></div>';
    $('#performanceContent').html(html);
}

function exportData(type, format) {
    const startDate = type === 'engagement' || type === 'behavior' ? 
        $(`#${type}StartDate`).val() : null;
    const endDate = type === 'engagement' || type === 'behavior' ? 
        $(`#${type}EndDate`).val() : null;
    
    const params = new URLSearchParams({
        type: type,
        format: format
    });
    
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    
    window.location.href = `{{ route("admin.profile-analytics.export") }}?${params.toString()}`;
}

function clearAnalyticsCache() {
    $('#loadingModal').modal('show');
    
    $.ajax({
        url: '{{ route("admin.profile-analytics.clear-cache") }}',
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            $('#loadingModal').modal('hide');
            if (response.success) {
                showNotification('Cache cleared successfully', 'success');
                refreshDashboard();
            }
        },
        error: function() {
            $('#loadingModal').modal('hide');
            showNotification('Failed to clear cache', 'error');
        }
    });
}

function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const notification = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
    
    $('body').prepend(notification);
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endpush