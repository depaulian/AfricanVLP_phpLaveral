@extends('layouts.admin')

@section('title', 'Forum Users Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Forum Users</h1>
            <p class="mb-0 text-muted">Manage forum users and their reputation</p>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.forums.management.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="exportUsers()">
                <i class="fas fa-download"></i> Export Users
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.forums.management.users') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="rank" class="form-label">Rank</label>
                    <select name="rank" id="rank" class="form-select">
                        <option value="">All Ranks</option>
                        @foreach($ranks as $rank)
                            <option value="{{ $rank }}" {{ request('rank') === $rank ? 'selected' : '' }}>
                                {{ $rank }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="activity" class="form-label">Activity Level</label>
                    <select name="activity" id="activity" class="form-select">
                        <option value="">All Users</option>
                        <option value="active" {{ request('activity') === 'active' ? 'selected' : '' }}>Active (30 days)</option>
                        <option value="inactive" {{ request('activity') === 'inactive' ? 'selected' : '' }}>Inactive (30+ days)</option>
                        <option value="new" {{ request('activity') === 'new' ? 'selected' : '' }}>New (7 days)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Forum Users ({{ $users->total() }} total)
            </h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    Bulk Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('suspend')">
                        <i class="fas fa-user-clock text-warning"></i> Suspend Selected
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('unsuspend')">
                        <i class="fas fa-user-check text-success"></i> Unsuspend Selected
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('reset_reputation')">
                        <i class="fas fa-undo text-info"></i> Reset Reputation
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="bulkAction('export')">
                        <i class="fas fa-download text-info"></i> Export Selected
                    </a></li>
                </ul>
            </div>
        </div>
        <div class="card-body p-0">
            @if($users->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="selectAll" class="form-check-input">
                                </th>
                                <th>User</th>
                                <th>Forum Activity</th>
                                <th>Reputation</th>
                                <th>Rank</th>
                                <th>Status</th>
                                <th>Last Active</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr id="user-{{ $user->id }}">
                                    <td>
                                        <input type="checkbox" name="selected_users[]" 
                                               value="{{ $user->id }}" class="form-check-input user-checkbox">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-3">
                                                <img src="{{ $user->avatar ?? '/images/default-avatar.png' }}" 
                                                     alt="{{ $user->name }}" class="rounded-circle">
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">{{ $user->name }}</div>
                                                <div class="text-muted small">{{ $user->email }}</div>
                                                <div class="text-muted small">
                                                    Joined {{ $user->created_at->format('M Y') }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small text-muted">Posts</span>
                                            <span class="badge badge-primary">{{ $user->forum_posts_count }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small text-muted">Threads</span>
                                            <span class="badge badge-secondary">{{ $user->forum_threads_count }}</span>
                                        </div>
                                        @if($user->forum_posts_count > 0)
                                            <div class="text-muted small mt-1">
                                                Avg: {{ number_format($user->forum_posts_count / max($user->forum_threads_count, 1), 1) }} posts/thread
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->forumUserReputation)
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fas fa-star text-warning me-1"></i>
                                                <span class="font-weight-bold">{{ number_format($user->forumUserReputation->total_points) }}</span>
                                            </div>
                                            <div class="progress" style="height: 4px;">
                                                @php
                                                    $nextLevelPoints = ($user->forumUserReputation->rank_level + 1) * 100;
                                                    $currentLevelPoints = $user->forumUserReputation->rank_level * 100;
                                                    $progress = $currentLevelPoints > 0 ? 
                                                        (($user->forumUserReputation->total_points - $currentLevelPoints) / ($nextLevelPoints - $currentLevelPoints)) * 100 : 0;
                                                @endphp
                                                <div class="progress-bar bg-warning" style="width: {{ min(100, max(0, $progress)) }}%"></div>
                                            </div>
                                            <div class="text-muted small mt-1">
                                                Level {{ $user->forumUserReputation->rank_level }}
                                            </div>
                                        @else
                                            <span class="text-muted">No reputation</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->forumUserReputation)
                                            @php
                                                $rankColors = [
                                                    'Newcomer' => 'secondary',
                                                    'Member' => 'primary',
                                                    'Regular' => 'info',
                                                    'Veteran' => 'success',
                                                    'Expert' => 'warning',
                                                    'Master' => 'danger'
                                                ];
                                                $rankColor = $rankColors[$user->forumUserReputation->rank] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-{{ $rankColor }}">
                                                {{ $user->forumUserReputation->rank }}
                                            </span>
                                        @else
                                            <span class="badge badge-light">Unranked</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $isSuspended = $user->forumSuspensions()->where('expires_at', '>', now())->exists();
                                            $isBanned = $user->forumBans()->where('expires_at', '>', now())->orWhereNull('expires_at')->exists();
                                        @endphp
                                        
                                        @if($isBanned)
                                            <span class="badge badge-danger">Banned</span>
                                        @elseif($isSuspended)
                                            <span class="badge badge-warning">Suspended</span>
                                        @else
                                            <span class="badge badge-success">Active</span>
                                        @endif
                                        
                                        @if($user->email_verified_at)
                                            <div class="text-success small mt-1">
                                                <i class="fas fa-check-circle"></i> Verified
                                            </div>
                                        @else
                                            <div class="text-warning small mt-1">
                                                <i class="fas fa-exclamation-circle"></i> Unverified
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->last_login_at)
                                            <div class="text-sm">{{ $user->last_login_at->format('M j, Y') }}</div>
                                            <div class="text-muted small">{{ $user->last_login_at->diffForHumans() }}</div>
                                        @else
                                            <span class="text-muted">Never</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewUser({{ $user->id }})" title="View Profile">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="adjustReputation({{ $user->id }})">
                                                        <i class="fas fa-star text-warning"></i> Adjust Reputation
                                                    </a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="awardBadge({{ $user->id }})">
                                                        <i class="fas fa-medal text-success"></i> Award Badge
                                                    </a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    @if(!$isSuspended && !$isBanned)
                                                        <li><a class="dropdown-item" href="#" onclick="suspendUser({{ $user->id }})">
                                                            <i class="fas fa-user-clock text-warning"></i> Suspend User
                                                        </a></li>
                                                    @endif
                                                    @if($isSuspended)
                                                        <li><a class="dropdown-item" href="#" onclick="unsuspendUser({{ $user->id }})">
                                                            <i class="fas fa-user-check text-success"></i> Unsuspend User
                                                        </a></li>
                                                    @endif
                                                    @if(!$isBanned)
                                                        <li><a class="dropdown-item" href="#" onclick="banUser({{ $user->id }})">
                                                            <i class="fas fa-user-slash text-danger"></i> Ban User
                                                        </a></li>
                                                    @endif
                                                    @if($isBanned)
                                                        <li><a class="dropdown-item" href="#" onclick="unbanUser({{ $user->id }})">
                                                            <i class="fas fa-user-plus text-success"></i> Unban User
                                                        </a></li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="card-footer">
                    {{ $users->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-muted">No Users Found</h5>
                    <p class="text-muted">
                        @if(request()->hasAny(['rank', 'activity', 'search']))
                            No users match your current filters.
                        @else
                            No forum users found.
                        @endif
                    </p>
                    @if(request()->hasAny(['rank', 'activity', 'search']))
                        <a href="{{ route('admin.forums.management.users') }}" class="btn btn-outline-primary">
                            Clear Filters
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- User Profile Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">User Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="userModalBody">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Reputation Modal -->
<div class="modal fade" id="adjustReputationModal" tabindex="-1" aria-labelledby="adjustReputationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adjustReputationModalLabel">Adjust User Reputation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="adjustReputationForm">
                <div class="modal-body">
                    <input type="hidden" id="reputationUserId" name="user_id">
                    
                    <div class="mb-3">
                        <label for="reputationPoints" class="form-label">Points Adjustment</label>
                        <input type="number" class="form-control" id="reputationPoints" name="points" 
                               min="-1000" max="1000" placeholder="Enter positive or negative points" required>
                        <div class="form-text">Enter positive numbers to add points, negative to subtract</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reputationReason" class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reputationReason" name="reason" rows="3" 
                                  placeholder="Explain the reason for this adjustment..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-star"></i> Adjust Reputation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Award Badge Modal -->
<div class="modal fade" id="awardBadgeModal" tabindex="-1" aria-labelledby="awardBadgeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="awardBadgeModalLabel">Award Badge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="awardBadgeForm">
                <div class="modal-body">
                    <input type="hidden" id="badgeUserId" name="user_id">
                    
                    <div class="mb-3">
                        <label for="badgeSelect" class="form-label">Select Badge</label>
                        <select class="form-select" id="badgeSelect" name="badge_id" required>
                            <option value="">Choose a badge...</option>
                            <!-- Options loaded via AJAX -->
                        </select>
                    </div>
                    
                    <div id="badgePreview" class="mb-3" style="display: none;">
                        <!-- Badge preview loaded via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-medal"></i> Award Badge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Global variables
let selectedUsers = [];

// Initialize page
$(document).ready(function() {
    initializeCheckboxes();
    initializeForms();
});

// Checkbox handling
function initializeCheckboxes() {
    $('#selectAll').change(function() {
        $('.user-checkbox').prop('checked', this.checked);
        updateSelectedUsers();
    });
    
    $('.user-checkbox').change(function() {
        updateSelectedUsers();
        
        // Update select all checkbox
        const totalCheckboxes = $('.user-checkbox').length;
        const checkedCheckboxes = $('.user-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
}

function updateSelectedUsers() {
    selectedUsers = $('.user-checkbox:checked').map(function() {
        return parseInt($(this).val());
    }).get();
}

// Form initialization
function initializeForms() {
    $('#adjustReputationForm').submit(function(e) {
        e.preventDefault();
        submitReputationAdjustment();
    });
    
    $('#awardBadgeForm').submit(function(e) {
        e.preventDefault();
        submitBadgeAward();
    });
    
    $('#badgeSelect').change(function() {
        loadBadgePreview($(this).val());
    });
}

// View user profile
function viewUser(userId) {
    $('#userModalBody').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    $('#userModal').modal('show');
    
    $.get(`/admin/forums/users/${userId}`)
        .done(function(data) {
            $('#userModalBody').html(data.html);
        })
        .fail(function() {
            $('#userModalBody').html('<div class="alert alert-danger">Failed to load user profile.</div>');
        });
}

// Adjust reputation
function adjustReputation(userId) {
    $('#reputationUserId').val(userId);
    $('#adjustReputationForm')[0].reset();
    $('#adjustReputationModal').modal('show');
}

function submitReputationAdjustment() {
    const formData = {
        user_id: $('#reputationUserId').val(),
        points: $('#reputationPoints').val(),
        reason: $('#reputationReason').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    $.post('/admin/forums/users/adjust-reputation', formData)
        .done(function(response) {
            if (response.success) {
                showNotification('success', response.message);
                $('#adjustReputationModal').modal('hide');
                
                // Update the user row reputation display
                updateUserReputationDisplay(formData.user_id, response.new_total);
            } else {
                showNotification('error', response.message || 'Failed to adjust reputation');
            }
        })
        .fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'An error occurred while adjusting reputation';
            showNotification('error', message);
        });
}

// Award badge
function awardBadge(userId) {
    $('#badgeUserId').val(userId);
    $('#awardBadgeForm')[0].reset();
    $('#badgePreview').hide();
    
    // Load available badges
    loadAvailableBadges();
    
    $('#awardBadgeModal').modal('show');
}

function loadAvailableBadges() {
    $.get('/admin/forums/badges/available')
        .done(function(badges) {
            const select = $('#badgeSelect');
            select.empty().append('<option value="">Choose a badge...</option>');
            
            badges.forEach(function(badge) {
                select.append(`<option value="${badge.id}">${badge.name} (${badge.type})</option>`);
            });
        })
        .fail(function() {
            showNotification('error', 'Failed to load available badges');
        });
}

function loadBadgePreview(badgeId) {
    if (!badgeId) {
        $('#badgePreview').hide();
        return;
    }
    
    $.get(`/admin/forums/badges/${badgeId}/preview`)
        .done(function(data) {
            $('#badgePreview').html(data.html).show();
        })
        .fail(function() {
            $('#badgePreview').hide();
        });
}

function submitBadgeAward() {
    const formData = {
        user_id: $('#badgeUserId').val(),
        badge_id: $('#badgeSelect').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    $.post('/admin/forums/users/award-badge', formData)
        .done(function(response) {
            if (response.success) {
                showNotification('success', response.message);
                $('#awardBadgeModal').modal('hide');
            } else {
                showNotification('error', response.message || 'Failed to award badge');
            }
        })
        .fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'An error occurred while awarding badge';
            showNotification('error', message);
        });
}

// User moderation actions
function suspendUser(userId) {
    const reason = prompt('Enter reason for suspension:');
    if (!reason) return;
    
    const duration = prompt('Enter suspension duration in days:', '7');
    if (!duration || isNaN(duration)) return;
    
    moderateUser(userId, 'suspend', reason, parseInt(duration));
}

function unsuspendUser(userId) {
    const reason = prompt('Enter reason for unsuspension:');
    if (!reason) return;
    
    moderateUser(userId, 'unsuspend', reason);
}

function banUser(userId) {
    const reason = prompt('Enter reason for ban:');
    if (!reason) return;
    
    if (!confirm('Are you sure you want to ban this user? This is a serious action.')) return;
    
    moderateUser(userId, 'ban', reason);
}

function unbanUser(userId) {
    const reason = prompt('Enter reason for unban:');
    if (!reason) return;
    
    moderateUser(userId, 'unban', reason);
}

function moderateUser(userId, action, reason, duration = null) {
    const data = {
        action: action,
        reason: reason,
        duration: duration,
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    $.post(`/admin/forums/users/${userId}/moderate`, data)
        .done(function(response) {
            if (response.success) {
                showNotification('success', response.message);
                
                // Update user status in the table
                updateUserStatusDisplay(userId, action);
            } else {
                showNotification('error', response.message || 'Moderation action failed');
            }
        })
        .fail(function(xhr) {
            const message = xhr.responseJSON?.message || 'An error occurred during moderation';
            showNotification('error', message);
        });
}

// Update display functions
function updateUserReputationDisplay(userId, newTotal) {
    const row = $(`#user-${userId}`);
    const reputationCell = row.find('td:nth-child(4)');
    
    // Update the reputation points display
    reputationCell.find('.font-weight-bold').text(newTotal.toLocaleString());
    
    // Add visual feedback
    row.addClass('table-success').delay(2000).queue(function() {
        $(this).removeClass('table-success').dequeue();
    });
}

function updateUserStatusDisplay(userId, action) {
    const row = $(`#user-${userId}`);
    const statusCell = row.find('td:nth-child(6)');
    
    let statusBadge = '';
    switch(action) {
        case 'suspend':
            statusBadge = '<span class="badge badge-warning">Suspended</span>';
            break;
        case 'unsuspend':
            statusBadge = '<span class="badge badge-success">Active</span>';
            break;
        case 'ban':
            statusBadge = '<span class="badge badge-danger">Banned</span>';
            break;
        case 'unban':
            statusBadge = '<span class="badge badge-success">Active</span>';
            break;
    }
    
    statusCell.find('.badge').first().replaceWith(statusBadge);
    
    // Add visual feedback
    row.addClass('table-info').delay(2000).queue(function() {
        $(this).removeClass('table-info').dequeue();
    });
}

// Bulk actions
function bulkAction(action) {
    if (selectedUsers.length === 0) {
        showNotification('warning', 'Please select at least one user');
        return;
    }
    
    const actionText = action === 'suspend' ? 'suspend' : 
                      action === 'unsuspend' ? 'unsuspend' : 
                      action === 'reset_reputation' ? 'reset reputation for' : 'export';
    
    if (confirm(`Are you sure you want to ${actionText} ${selectedUsers.length} selected user(s)?`)) {
        processBulkAction(action);
    }
}

function processBulkAction(action) {
    const data = {
        action: action,
        user_ids: selectedUsers,
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    $.post('/admin/forums/users/bulk-action', data)
        .done(function(response) {
            if (response.success) {
                showNotification('success', response.message);
                
                if (action !== 'export') {
                    // Reload page to reflect changes
                    location.reload();
                }
            } else {
                showNotification('error', response.message || 'Bulk action failed');
            }
        })
        .fail(function() {
            showNotification('error', 'An error occurred during bulk action');
        });
}

// Export users
function exportUsers() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'true');
    
    window.location.href = `${window.location.pathname}?${params.toString()}`;
}

// Utility functions
function showNotification(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-danger';
    
    const alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('body').append(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alert.alert('close');
    }, 5000);
}
</script>
@endpush

@push('styles')
<style>
.avatar {
    width: 40px;
    height: 40px;
}

.avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.progress {
    background-color: #e9ecef;
}

.badge {
    font-size: 0.75em;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.btn-group .dropdown-menu {
    min-width: 160px;
}

.dropdown-item i {
    width: 16px;
    margin-right: 8px;
}

.text-sm {
    font-size: 0.875rem;
}

.modal-lg {
    max-width: 800px;
}

@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
    
    .avatar {
        width: 32px;
        height: 32px;
    }
}
</style>
@endpush