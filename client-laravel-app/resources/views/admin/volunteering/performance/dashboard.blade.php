@extends('layouts.admin')

@section('title', 'Volunteering Performance Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Volunteering Performance Dashboard</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.volunteering.index') }}">Volunteering</a></li>
                        <li class="breadcrumb-item active">Performance</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Overview Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Average Response Time">Avg Response Time</h5>
                            <h3 class="my-2 py-1">{{ $metrics['avg_response_time'] ?? 0 }}ms</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-{{ $metrics['response_time_trend'] === 'up' ? 'danger' : 'success' }} me-2">
                                    <i class="mdi mdi-arrow-{{ $metrics['response_time_trend'] === 'up' ? 'up' : 'down' }}-bold"></i>
                                    {{ $metrics['response_time_change'] ?? 0 }}%
                                </span>
                                <span class="text-nowrap">Since last week</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="response-time-chart" data-colors="#727cf5"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Cache Hit Rate">Cache Hit Rate</h5>
                            <h3 class="my-2 py-1">{{ $metrics['cache_hit_rate'] ?? 0 }}%</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-{{ $metrics['cache_trend'] === 'up' ? 'success' : 'danger' }} me-2">
                                    <i class="mdi mdi-arrow-{{ $metrics['cache_trend'] === 'up' ? 'up' : 'down' }}-bold"></i>
                                    {{ $metrics['cache_change'] ?? 0 }}%
                                </span>
                                <span class="text-nowrap">Since last week</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="cache-hit-chart" data-colors="#0acf97"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Database Queries">Avg DB Queries</h5>
                            <h3 class="my-2 py-1">{{ $metrics['avg_query_count'] ?? 0 }}</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-{{ $metrics['query_trend'] === 'up' ? 'danger' : 'success' }} me-2">
                                    <i class="mdi mdi-arrow-{{ $metrics['query_trend'] === 'up' ? 'up' : 'down' }}-bold"></i>
                                    {{ $metrics['query_change'] ?? 0 }}%
                                </span>
                                <span class="text-nowrap">Since last week</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="query-count-chart" data-colors="#fa5c7c"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Memory Usage">Avg Memory Usage</h5>
                            <h3 class="my-2 py-1">{{ $metrics['avg_memory_usage'] ?? 0 }}MB</h3>
                            <p class="mb-0 text-muted">
                                <span class="text-{{ $metrics['memory_trend'] === 'up' ? 'danger' : 'success' }} me-2">
                                    <i class="mdi mdi-arrow-{{ $metrics['memory_trend'] === 'up' ? 'up' : 'down' }}-bold"></i>
                                    {{ $metrics['memory_change'] ?? 0 }}%
                                </span>
                                <span class="text-nowrap">Since last week</span>
                            </p>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="memory-usage-chart" data-colors="#ffbc00"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Performance Trends Chart -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <div class="dropdown float-end">
                        <a href="#" class="dropdown-toggle arrow-none card-drop" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="mdi mdi-dots-vertical"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0);" class="dropdown-item">Refresh Data</a>
                            <a href="javascript:void(0);" class="dropdown-item">Export Report</a>
                        </div>
                    </div>
                    <h4 class="header-title mb-3">Performance Trends</h4>
                    <div id="performance-trends-chart" class="apex-charts" data-colors="#727cf5,#0acf97,#fa5c7c,#ffbc00"></div>
                </div>
            </div>
        </div>

        <!-- Top Slow Routes -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Slowest Routes</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-nowrap mb-0">
                            <thead>
                                <tr>
                                    <th>Route</th>
                                    <th>Avg Time</th>
                                    <th>Requests</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($slowRoutes ?? [] as $route)
                                <tr>
                                    <td>
                                        <span class="text-truncate" style="max-width: 150px;" title="{{ $route['path'] }}">
                                            {{ $route['path'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-soft-{{ $route['avg_time'] > 1000 ? 'danger' : ($route['avg_time'] > 500 ? 'warning' : 'success') }}">
                                            {{ number_format($route['avg_time'], 0) }}ms
                                        </span>
                                    </td>
                                    <td>{{ number_format($route['requests']) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Cache Statistics -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Cache Statistics</h4>
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-success">{{ $cacheStats['hit_rate'] ?? 0 }}%</h3>
                                <p class="text-muted mb-0">Hit Rate</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="text-info">{{ $cacheStats['total_keys'] ?? 0 }}</h3>
                                <p class="text-muted mb-0">Total Keys</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="row">
                            <div class="col-6">
                                <p class="mb-1">Memory Usage</p>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: {{ $cacheStats['memory_usage_percent'] ?? 0 }}%" 
                                         aria-valuenow="{{ $cacheStats['memory_usage_percent'] ?? 0 }}" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">{{ $cacheStats['memory_usage'] ?? '0B' }}</small>
                            </div>
                            <div class="col-6">
                                <p class="mb-1">Cache Operations</p>
                                <small class="text-muted">
                                    Hits: {{ number_format($cacheStats['hits'] ?? 0) }}<br>
                                    Misses: {{ number_format($cacheStats['misses'] ?? 0) }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Performance -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Database Performance</h4>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Table</th>
                                    <th>Size</th>
                                    <th>Rows</th>
                                    <th>Avg Query Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dbStats ?? [] as $table)
                                <tr>
                                    <td>{{ $table['name'] }}</td>
                                    <td>{{ $table['size'] }}</td>
                                    <td>{{ number_format($table['rows']) }}</td>
                                    <td>
                                        <span class="badge badge-soft-{{ $table['avg_query_time'] > 100 ? 'danger' : ($table['avg_query_time'] > 50 ? 'warning' : 'success') }}">
                                            {{ number_format($table['avg_query_time'], 1) }}ms
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No data available</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="header-title mb-3">Performance Actions</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <button type="button" class="btn btn-primary w-100 mb-2" onclick="warmUpCache()">
                                <i class="mdi mdi-fire me-1"></i> Warm Up Cache
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-warning w-100 mb-2" onclick="clearCache()">
                                <i class="mdi mdi-delete-sweep me-1"></i> Clear Cache
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-info w-100 mb-2" onclick="optimizeDatabase()">
                                <i class="mdi mdi-database-settings me-1"></i> Optimize DB
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-success w-100 mb-2" onclick="generateReport()">
                                <i class="mdi mdi-file-chart me-1"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/vendor/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Performance trends chart
    var performanceTrendsOptions = {
        series: [
            {
                name: 'Response Time (ms)',
                data: @json($chartData['response_time'] ?? [])
            },
            {
                name: 'Memory Usage (MB)',
                data: @json($chartData['memory_usage'] ?? [])
            },
            {
                name: 'Query Count',
                data: @json($chartData['query_count'] ?? [])
            }
        ],
        chart: {
            type: 'line',
            height: 350,
            toolbar: {
                show: true
            }
        },
        colors: ['#727cf5', '#0acf97', '#fa5c7c'],
        xaxis: {
            categories: @json($chartData['labels'] ?? [])
        },
        yaxis: [
            {
                title: {
                    text: 'Response Time (ms)'
                }
            },
            {
                opposite: true,
                title: {
                    text: 'Memory Usage (MB)'
                }
            }
        ],
        legend: {
            position: 'top'
        }
    };

    var performanceTrendsChart = new ApexCharts(
        document.querySelector("#performance-trends-chart"), 
        performanceTrendsOptions
    );
    performanceTrendsChart.render();

    // Small charts for cards
    renderSparklineChart('#response-time-chart', @json($sparklineData['response_time'] ?? []), '#727cf5');
    renderSparklineChart('#cache-hit-chart', @json($sparklineData['cache_hit'] ?? []), '#0acf97');
    renderSparklineChart('#query-count-chart', @json($sparklineData['query_count'] ?? []), '#fa5c7c');
    renderSparklineChart('#memory-usage-chart', @json($sparklineData['memory_usage'] ?? []), '#ffbc00');
});

function renderSparklineChart(selector, data, color) {
    var options = {
        series: [{
            data: data
        }],
        chart: {
            type: 'line',
            width: 80,
            height: 35,
            sparkline: {
                enabled: true
            }
        },
        stroke: {
            width: 2,
            curve: 'smooth'
        },
        colors: [color],
        tooltip: {
            enabled: false
        }
    };

    var chart = new ApexCharts(document.querySelector(selector), options);
    chart.render();
}

// Performance action functions
function warmUpCache() {
    if (confirm('This will warm up the cache with frequently accessed data. Continue?')) {
        fetch('{{ route("admin.volunteering.performance.warm-cache") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Cache warmed up successfully!', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification('Failed to warm up cache: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error: ' + error.message, 'error');
        });
    }
}

function clearCache() {
    if (confirm('This will clear all volunteering cache data. Continue?')) {
        fetch('{{ route("admin.volunteering.performance.clear-cache") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Cache cleared successfully!', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification('Failed to clear cache: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error: ' + error.message, 'error');
        });
    }
}

function optimizeDatabase() {
    if (confirm('This will optimize database indexes and analyze tables. Continue?')) {
        fetch('{{ route("admin.volunteering.performance.optimize-db") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Database optimized successfully!', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showNotification('Failed to optimize database: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error: ' + error.message, 'error');
        });
    }
}

function generateReport() {
    window.open('{{ route("admin.volunteering.performance.report") }}', '_blank');
}

function showNotification(message, type) {
    // Implement your notification system here
    alert(message);
}
</script>
@endpush