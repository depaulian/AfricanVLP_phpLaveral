@extends('layouts.admin')

@section('title', 'Time Log Approvals')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Time Log Approvals</h1>
            <p class="text-muted">Review and approve volunteer time entries</p>
        </div>
        <div class="d-flex align-items-center">
            <div class="mr-3">
                <span class="badge badge-warning badge-pill">{{ $pendingCount }}</span>
                <small class="text-muted">Pending Approval</small>
            </div>
            <button type="button" class="btn btn-success" onclick="bulkApprove()" id="bulk-approve-btn" disabled>
                <i class="fas fa-check"></i> Bulk Approve
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label for="assignment_filter" class="form-label">Assignment</label>
                    <select name="assignment" id="assignment_filter" class="form-control">
                        <option value="">All Assignments</option>
                        @foreach($assignments as $assignment)
                            <option value="{{ $assignment->id }}" {{ request('assignment') == $assignment->id ? 'selected' : '' }}>
                                {{ $assignment->opportunity->title }} - {{ $assignment->application->user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status_filter" class="form-label">Status</label>
                    <select name="status" id="status_filter" class="form-control">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="{{ route('admin.volunteering.time-logs.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <button type="button" class="btn btn-info" onclick="exportTimeLogs()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Time Logs Table -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Time Log Entries</h6>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="select-all">
                    <label class="form-check-label" for="select-all">Select All</label>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($timeLogs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="select-all-header">
                                </th>
                                <th>Date</th>
                                <th>Volunteer</th>
                                <th>Assignment</th>
                                <th>Time</th>
                                <th>Hours</th>
                                <th>Activity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timeLogs as $log)
                                <tr class="time-log-row" data-id="{{ $log->id }}">
                                    <td>
                                        @if(!$log->supervisor_approved)
                                            <input type="checkbox" class="time-log-checkbox" value="{{ $log->id }}">
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $log->date->format('M d, Y') }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $log->date->format('l') }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm mr-2">
                                                <img src="{{ $log->assignment->application->user->avatar_url ?? '/img/default-avatar.png' }}" 
                                                     alt="{{ $log->assignment->application->user->name }}" 
                                                     class="avatar-img rounded-circle">
                                            </div>
                                            <div>
                                                <strong>{{ $log->assignment->application->user->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $log->assignment->application->user->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $log->assignment->opportunity->title }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $log->assignment->opportunity->organization->name }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-light">
                                            {{ $log->start_time->format('g:i A') }} - {{ $log->end_time->format('g:i A') }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ number_format($log->hours, 1) }}</strong>
                                    </td>
                                    <td>
                                        @if($log->activity_description)
                                            <div class="activity-description" title="{{ $log->activity_description }}">
                                                {{ Str::limit($log->activity_description, 50) }}
                                            </div>
                                        @else
                                            <span class="text-muted">No description</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($log->supervisor_approved)
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Approved
                                            </span>
                                            @if($log->approved_at)
                                                <br>
                                                <small class="text-muted">{{ $log->approved_at->format('M d, Y') }}</small>
                                            @endif
                                        @else
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                            <br>
                                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="viewTimeLogDetails({{ $log->id }})" 
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            @if(!$log->supervisor_approved)
                                                <button type="button" class="btn btn-sm btn-success" 
                                                        onclick="approveTimeLog({{ $log->id }})" 
                                                        title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" 
                                                        onclick="rejectTimeLog({{ $log->id }})" 
                                                        title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        onclick="unapproveTimeLog({{ $log->id }})" 
                                                        title="Unapprove">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="card-footer">
                    {{ $timeLogs->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Time Logs Found</h5>
                    <p class="text-muted">No time log entries match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Time Log Details Modal -->
<div class="modal fade" id="timeLogModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Time Log Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="timeLogModalBody">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Time Log</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="rejectForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Reason for Rejection</label>
                        <textarea class="form-control" id="rejection_reason" name="reason" rows="3" 
                                  placeholder="Please provide a reason for rejecting this time log entry..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedTimeLogs = [];
let currentTimeLogId = null;

// Select all functionality
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.time-log-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkApproveButton();
});

document.getElementById('select-all-header').addEventListener('change', function() {
    document.getElementById('select-all').checked = this.checked;
    document.getElementById('select-all').dispatchEvent(new Event('change'));
});

// Individual checkbox handling
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('time-log-checkbox')) {
        updateBulkApproveButton();
    }
});

function updateBulkApproveButton() {
    const checkedBoxes = document.querySelectorAll('.time-log-checkbox:checked');
    const bulkApproveBtn = document.getElementById('bulk-approve-btn');
    
    if (checkedBoxes.length > 0) {
        bulkApproveBtn.disabled = false;
        bulkApproveBtn.innerHTML = `<i class="fas fa-check"></i> Approve Selected (${checkedBoxes.length})`;
    } else {
        bulkApproveBtn.disabled = true;
        bulkApproveBtn.innerHTML = '<i class="fas fa-check"></i> Bulk Approve';
    }
}

function bulkApprove() {
    const checkedBoxes = document.querySelectorAll('.time-log-checkbox:checked');
    const timeLogIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (timeLogIds.length === 0) {
        alert('Please select time logs to approve.');
        return;
    }
    
    if (confirm(`Are you sure you want to approve ${timeLogIds.length} time log entries?`)) {
        fetch('{{ route("admin.volunteering.time-logs.bulk-approve") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ time_log_ids: timeLogIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error approving time logs: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while approving time logs.');
        });
    }
}

function approveTimeLog(timeLogId) {
    if (confirm('Are you sure you want to approve this time log entry?')) {
        fetch(`/admin/volunteering/time-logs/${timeLogId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error approving time log: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while approving the time log.');
        });
    }
}

function rejectTimeLog(timeLogId) {
    currentTimeLogId = timeLogId;
    $('#rejectModal').modal('show');
}

function unapproveTimeLog(timeLogId) {
    if (confirm('Are you sure you want to unapprove this time log entry?')) {
        fetch(`/admin/volunteering/time-logs/${timeLogId}/unapprove`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error unapproving time log: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while unapproving the time log.');
        });
    }
}

function viewTimeLogDetails(timeLogId) {
    fetch(`/admin/volunteering/time-logs/${timeLogId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('timeLogModalBody').innerHTML = html;
            $('#timeLogModal').modal('show');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading time log details.');
        });
}

function exportTimeLogs() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = '{{ route("admin.volunteering.time-logs.export") }}?' + params.toString();
}

// Reject form submission
document.getElementById('rejectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const reason = document.getElementById('rejection_reason').value;
    
    fetch(`/admin/volunteering/time-logs/${currentTimeLogId}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#rejectModal').modal('hide');
            location.reload();
        } else {
            alert('Error rejecting time log: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while rejecting the time log.');
    });
});

// Auto-refresh pending count every 30 seconds
setInterval(function() {
    fetch('{{ route("admin.volunteering.time-logs.pending-count") }}')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.badge-warning');
            if (badge && data.count !== undefined) {
                badge.textContent = data.count;
            }
        })
        .catch(error => console.error('Error updating pending count:', error));
}, 30000);
</script>
@endpush
@endsection