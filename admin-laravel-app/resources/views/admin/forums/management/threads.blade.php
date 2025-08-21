@extends('layouts.admin')

@section('title', 'Thread Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Thread Management</h1>
            <p class="mb-0 text-muted">Manage forum threads and discussions</p>
        </div>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-secondary" onclick="showBulkActions()">
                <i class="fas fa-tasks"></i> Bulk Actions
            </button>
            <a href="{{ route('admin.forums.management.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search threads..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select class="form-control" name="forum">
                        <option value="">All Forums</option>
                        @foreach($forums as $forum)
                            <option value="{{ $forum->id }}" {{ request('forum') == $forum->id ? 'selected' : '' }}>
                                {{ $forum->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" name="status">
                        <option value="">All Status</option>
                        <option value="pinned" {{ request('status') == 'pinned' ? 'selected' : '' }}>Pinned</option>
                        <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>Locked</option>
                        <option value="solved" {{ request('status') == 'solved' ? 'selected' : '' }}>Solved</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.forums.management.threads') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Threads Table -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Forum Threads</h6>
        </div>
        <div class="card-body">
            @if($threads->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="threadsTable">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>Thread</th>
                                <th>Forum</th>
                                <th>Author</th>
                                <th>Posts</th>
                                <th>Votes</th>
                                <th>Status</th>
                                <th>Last Activity</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($threads as $thread)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="thread-checkbox" value="{{ $thread->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-start">
                                            <div class="mr-2">
                                                @if($thread->is_pinned)
                                                    <i class="fas fa-thumbtack text-warning" title="Pinned"></i>
                                                @endif
                                                @if($thread->is_locked)
                                                    <i class="fas fa-lock text-danger" title="Locked"></i>
                                                @endif
                                                @if($thread->is_solved)
                                                    <i class="fas fa-check-circle text-success" title="Solved"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">
                                                    <a href="{{ route('client.forums.threads.show', [$thread->forum, $thread]) }}" 
                                                       target="_blank" class="text-decoration-none">
                                                        {{ $thread->title }}
                                                    </a>
                                                </div>
                                                <div class="text-muted small">
                                                    {{ Str::limit(strip_tags($thread->content), 80) }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{ $thread->forum->name }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm mr-2">
                                                <img src="{{ $thread->user->avatar ?? '/images/default-avatar.png' }}" 
                                                     alt="{{ $thread->user->name }}" class="rounded-circle">
                                            </div>
                                            <span>{{ $thread->user->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{ number_format($thread->posts_count) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $thread->votes_count }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            @if($thread->is_pinned)
                                                <span class="badge badge-warning badge-sm mb-1">Pinned</span>
                                            @endif
                                            @if($thread->is_locked)
                                                <span class="badge badge-danger badge-sm mb-1">Locked</span>
                                            @endif
                                            @if($thread->is_solved)
                                                <span class="badge badge-success badge-sm">Solved</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-muted">
                                        @if($thread->latestPost)
                                            {{ $thread->latestPost->created_at->diffForHumans() }}
                                        @else
                                            {{ $thread->created_at->diffForHumans() }}
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical btn-group-sm" role="group">
                                            <button class="btn btn-outline-warning btn-sm" 
                                                    onclick="togglePin({{ $thread->id }})" 
                                                    title="{{ $thread->is_pinned ? 'Unpin' : 'Pin' }}">
                                                <i class="fas fa-thumbtack"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="toggleLock({{ $thread->id }})" 
                                                    title="{{ $thread->is_locked ? 'Unlock' : 'Lock' }}">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteThread({{ $thread->id }})" 
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Showing {{ $threads->firstItem() }} to {{ $threads->lastItem() }} of {{ $threads->total() }} results
                    </div>
                    {{ $threads->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-list fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-muted">No threads found</h5>
                    <p class="text-muted">No threads match your current filters.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Actions</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="bulkActionForm">
                    <div class="form-group">
                        <label for="bulkAction">Action</label>
                        <select class="form-control" id="bulkAction" name="action" required>
                            <option value="">Select Action</option>
                            <option value="pin">Pin Threads</option>
                            <option value="unpin">Unpin Threads</option>
                            <option value="lock">Lock Threads</option>
                            <option value="unlock">Unlock Threads</option>
                            <option value="delete">Delete Threads</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This action will be applied to all selected threads.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="executeBulkAction()">Execute</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.thread-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Toggle pin status
function togglePin(threadId) {
    fetch(`/admin/forums/threads/${threadId}/toggle-pin`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating thread status');
        }
    });
}

// Toggle lock status
function toggleLock(threadId) {
    fetch(`/admin/forums/threads/${threadId}/toggle-lock`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error updating thread status');
        }
    });
}

// Delete thread
function deleteThread(threadId) {
    if (confirm('Are you sure you want to delete this thread? This action cannot be undone.')) {
        fetch(`/admin/forums/threads/${threadId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting thread: ' + data.message);
            }
        });
    }
}

// Show bulk actions modal
function showBulkActions() {
    const selected = document.querySelectorAll('.thread-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select at least one thread');
        return;
    }
    $('#bulkActionsModal').modal('show');
}

// Execute bulk action
function executeBulkAction() {
    const selected = Array.from(document.querySelectorAll('.thread-checkbox:checked')).map(cb => cb.value);
    const action = document.getElementById('bulkAction').value;
    
    if (!action) {
        alert('Please select an action');
        return;
    }
    
    if (!confirm(`Are you sure you want to ${action} ${selected.length} thread(s)?`)) {
        return;
    }
    
    fetch('/admin/forums/bulk-action', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: action,
            type: 'threads',
            ids: selected
        })
    })
    .then(response => response.json())
    .then(data => {
        $('#bulkActionsModal').modal('hide');
        if (data.success) {
            alert(`Successfully processed ${data.processed} thread(s)`);
            location.reload();
        } else {
            alert('Error executing bulk action');
        }
    });
}
</script>
@endsection