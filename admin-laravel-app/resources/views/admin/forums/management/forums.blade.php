@extends('layouts.admin')

@section('title', 'Forum Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Forum Management</h1>
            <p class="mb-0 text-muted">Manage forums, settings, and permissions</p>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.forums.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Forum
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="showBulkActions()">
                <i class="fas fa-tasks"></i> Bulk Actions
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search forums..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select class="form-control" name="organization">
                        <option value="">All Organizations</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ request('organization') == $org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-control" name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.forums.management.forums') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Forums Table -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Forums</h6>
        </div>
        <div class="card-body">
            @if($forums->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="forumsTable">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>Forum</th>
                                <th>Organization</th>
                                <th>Threads</th>
                                <th>Posts</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($forums as $forum)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="forum-checkbox" value="{{ $forum->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3">
                                                @if($forum->icon)
                                                    <i class="{{ $forum->icon }} fa-2x text-primary"></i>
                                                @else
                                                    <i class="fas fa-comments fa-2x text-gray-300"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">{{ $forum->name }}</div>
                                                <div class="text-muted small">{{ Str::limit($forum->description, 60) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($forum->organization)
                                            <span class="badge badge-info">{{ $forum->organization->name }}</span>
                                        @else
                                            <span class="text-muted">Global</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{ number_format($forum->threads_count) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{ number_format($forum->posts_count) }}</span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm {{ $forum->is_active ? 'btn-success' : 'btn-danger' }}" 
                                                onclick="toggleForumStatus({{ $forum->id }})">
                                            {{ $forum->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="text-muted">
                                        {{ $forum->created_at->format('M j, Y') }}
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.forums.show', $forum) }}" 
                                               class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.forums.edit', $forum) }}" 
                                               class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteForum({{ $forum->id }})" title="Delete">
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
                        Showing {{ $forums->firstItem() }} to {{ $forums->lastItem() }} of {{ $forums->total() }} results
                    </div>
                    {{ $forums->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-comments fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-muted">No forums found</h5>
                    <p class="text-muted">Create your first forum to get started.</p>
                    <a href="{{ route('admin.forums.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Forum
                    </a>
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
                            <option value="activate">Activate Forums</option>
                            <option value="deactivate">Deactivate Forums</option>
                            <option value="delete">Delete Forums</option>
                        </select>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This action will be applied to all selected forums.
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
    const checkboxes = document.querySelectorAll('.forum-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Toggle forum status
function toggleForumStatus(forumId) {
    fetch(`/admin/forums/${forumId}/toggle-status`, {
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
            alert('Error updating forum status');
        }
    });
}

// Delete forum
function deleteForum(forumId) {
    if (confirm('Are you sure you want to delete this forum? This action cannot be undone.')) {
        fetch(`/admin/forums/${forumId}`, {
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
                alert('Error deleting forum: ' + data.message);
            }
        });
    }
}

// Show bulk actions modal
function showBulkActions() {
    const selected = document.querySelectorAll('.forum-checkbox:checked');
    if (selected.length === 0) {
        alert('Please select at least one forum');
        return;
    }
    $('#bulkActionsModal').modal('show');
}

// Execute bulk action
function executeBulkAction() {
    const selected = Array.from(document.querySelectorAll('.forum-checkbox:checked')).map(cb => cb.value);
    const action = document.getElementById('bulkAction').value;
    
    if (!action) {
        alert('Please select an action');
        return;
    }
    
    if (!confirm(`Are you sure you want to ${action} ${selected.length} forum(s)?`)) {
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
            type: 'forums',
            ids: selected
        })
    })
    .then(response => response.json())
    .then(data => {
        $('#bulkActionsModal').modal('hide');
        if (data.success) {
            alert(`Successfully processed ${data.processed} forum(s)`);
            location.reload();
        } else {
            alert('Error executing bulk action');
        }
    });
}
</script>
@endsection