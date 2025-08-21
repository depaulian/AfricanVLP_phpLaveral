@extends('layouts.admin')

@section('title', 'Forum Management Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Forum Management</h1>
            <p class="mb-0 text-muted">Comprehensive forum administration and moderation</p>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.forums.management.forums') }}" class="btn btn-outline-primary">
                <i class="fas fa-comments"></i> Manage Forums
            </a>
            <a href="{{ route('admin.forums.management.reports') }}" class="btn btn-outline-warning">
                <i class="fas fa-flag"></i> Reports
                @if($stats['pending_reports'] > 0)
                    <span class="badge badge-warning ml-1">{{ $stats['pending_reports'] }}</span>
                @endif
            </a>
            <a href="{{ route('admin.forums.analytics') }}" class="btn btn-outline-info">
                <i class="fas fa-chart-bar"></i> Analytics
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Forums
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_forums']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Threads
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_threads']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-list fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Posts
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total_posts']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comment fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Active Users (30d)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['active_users']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Activity -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Forum Activity</h6>
                    <a href="{{ route('admin.forums.management.threads') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @if($recentActivity->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Forum</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentActivity as $activity)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm mr-2">
                                                        <img src="{{ $activity->user->avatar ?? '/images/default-avatar.png' }}" 
                                                             alt="{{ $activity->user->name }}" class="rounded-circle">
                                                    </div>
                                                    <span>{{ $activity->user->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">Posted in</span>
                                                <a href="#" class="text-decoration-none">
                                                    {{ Str::limit($activity->thread->title, 30) }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $activity->thread->forum->name }}</span>
                                            </td>
                                            <td class="text-muted">
                                                {{ $activity->created_at->diffForHumans() }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-comments fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">No recent forum activity</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pending Reports -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-warning">Pending Reports</h6>
                    <a href="{{ route('admin.forums.management.reports') }}" class="btn btn-sm btn-outline-warning">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    @if($pendingReports->count() > 0)
                        @foreach($pendingReports as $report)
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-grow-1">
                                    <div class="font-weight-bold text-sm">
                                        {{ ucfirst($report->reason) }}
                                    </div>
                                    <div class="text-muted text-xs">
                                        Reported by {{ $report->reporter->name }}
                                    </div>
                                    <div class="text-muted text-xs">
                                        {{ $report->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="handleReport({{ $report->id }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-flag fa-3x text-gray-300 mb-3"></i>
                            <p class="text-muted">No pending reports</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Top Forums -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Most Active Forums</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($topForums as $forum)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card border-left-primary">
                                    <div class="card-body py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="font-weight-bold mb-1">{{ $forum->name }}</h6>
                                                <div class="text-muted small">
                                                    {{ $forum->threads_count }} threads, {{ $forum->posts_count }} posts
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <a href="{{ route('admin.forums.management.forums') }}?forum={{ $forum->id }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" role="dialog" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Handle Report</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="reportDetails"></div>
                <form id="reportForm">
                    <div class="form-group">
                        <label for="action">Action</label>
                        <select class="form-control" id="action" name="action" required>
                            <option value="">Select Action</option>
                            <option value="dismiss">Dismiss Report</option>
                            <option value="warn">Issue Warning</option>
                            <option value="suspend">Suspend User</option>
                            <option value="ban">Ban User</option>
                            <option value="delete_content">Delete Content</option>
                        </select>
                    </div>
                    <div class="form-group" id="durationGroup" style="display: none;">
                        <label for="duration">Duration (days)</label>
                        <input type="number" class="form-control" id="duration" name="duration" min="1" max="365">
                    </div>
                    <div class="form-group">
                        <label for="reason">Reason</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitReport()">Submit</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let currentReportId = null;

function handleReport(reportId) {
    currentReportId = reportId;
    // Load report details via AJAX
    fetch(`/admin/forums/reports/${reportId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('reportDetails').innerHTML = `
                <div class="card mb-3">
                    <div class="card-body">
                        <h6>Report Details</h6>
                        <p><strong>Reason:</strong> ${data.reason}</p>
                        <p><strong>Reporter:</strong> ${data.reporter.name}</p>
                        <p><strong>Reported Content:</strong> ${data.content_preview}</p>
                        <p><strong>Date:</strong> ${data.created_at}</p>
                    </div>
                </div>
            `;
            $('#reportModal').modal('show');
        });
}

function submitReport() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    
    fetch(`/admin/forums/reports/${currentReportId}/handle`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#reportModal').modal('hide');
            location.reload();
        } else {
            alert('Error handling report: ' + data.message);
        }
    });
}

document.getElementById('action').addEventListener('change', function() {
    const durationGroup = document.getElementById('durationGroup');
    if (this.value === 'suspend' || this.value === 'ban') {
        durationGroup.style.display = 'block';
    } else {
        durationGroup.style.display = 'none';
    }
});
</script>
@endsection