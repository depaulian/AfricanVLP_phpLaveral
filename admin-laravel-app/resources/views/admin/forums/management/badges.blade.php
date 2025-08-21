@extends('layouts.admin')

@section('title', 'Forum Badges Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Forum Badges</h1>
            <p class="mb-0 text-muted">Manage forum badges and achievements</p>
        </div>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.forums.management.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <button type="button" class="btn btn-primary" onclick="createBadge()">
                <i class="fas fa-plus"></i> Create Badge
            </button>
        </div>
    </div>

    <!-- Badge Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Badges
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $badges->count() }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-medal fa-2x text-gray-300"></i>
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
                                Badges Awarded
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_awarded'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-trophy fa-2x text-gray-300"></i>
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
                                Active Users with Badges
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['users_with_badges'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                Most Popular Badge
                            </div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">
                                {{ $stats['most_popular_badge'] ?? 'None' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-star fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Badges Grid -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Forum Badges</h6>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    Filter by Type
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#" onclick="filterBadges('all')">All Types</a></li>
                    <li><a class="dropdown-item" href="#" onclick="filterBadges('achievement')">Achievement</a></li>
                    <li><a class="dropdown-item" href="#" onclick="filterBadges('participation')">Participation</a></li>
                    <li><a class="dropdown-item" href="#" onclick="filterBadges('special')">Special</a></li>
                    <li><a class="dropdown-item" href="#" onclick="filterBadges('milestone')">Milestone</a></li>
                </ul>
            </div>
        </div>
        <div class="card-body">
            @if($badges->count() > 0)
                <div class="row" id="badgesGrid">
                    @foreach($badges as $badge)
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4 badge-item" data-type="{{ $badge->type }}">
                            <div class="card h-100 border-left-{{ $badge->rarity === 'legendary' ? 'warning' : ($badge->rarity === 'epic' ? 'info' : ($badge->rarity === 'rare' ? 'success' : 'secondary')) }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="badge-icon">
                                            @if($badge->icon)
                                                <i class="{{ $badge->icon }} fa-2x text-{{ $badge->rarity === 'legendary' ? 'warning' : ($badge->rarity === 'epic' ? 'info' : ($badge->rarity === 'rare' ? 'success' : 'secondary')) }}"></i>
                                            @else
                                                <i class="fas fa-medal fa-2x text-secondary"></i>
                                            @endif
                                        </div>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#" onclick="editBadge({{ $badge->id }})">
                                                    <i class="fas fa-edit text-primary"></i> Edit
                                                </a></li>
                                                <li><a class="dropdown-item" href="#" onclick="viewBadgeHolders({{ $badge->id }})">
                                                    <i class="fas fa-users text-info"></i> View Holders
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="#" onclick="duplicateBadge({{ $badge->id }})">
                                                    <i class="fas fa-copy text-secondary"></i> Duplicate
                                                </a></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteBadge({{ $badge->id }})">
                                                    <i class="fas fa-trash text-danger"></i> Delete
                                                </a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <h6 class="card-title font-weight-bold">{{ $badge->name }}</h6>
                                    <p class="card-text text-muted small">{{ $badge->description }}</p>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge badge-{{ $badge->type === 'achievement' ? 'primary' : ($badge->type === 'participation' ? 'success' : ($badge->type === 'special' ? 'warning' : 'info')) }}">
                                                {{ ucfirst($badge->type) }}
                                            </span>
                                            <span class="badge badge-{{ $badge->rarity === 'legendary' ? 'warning' : ($badge->rarity === 'epic' ? 'info' : ($badge->rarity === 'rare' ? 'success' : 'secondary')) }}">
                                                {{ ucfirst($badge->rarity) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    @if($badge->criteria)
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <strong>Criteria:</strong> {{ Str::limit($badge->criteria, 60) }}
                                            </small>
                                        </div>
                                    @endif
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-center">
                                            <div class="h6 mb-0 font-weight-bold text-primary">{{ $badge->user_badges_count }}</div>
                                            <small class="text-muted">Awarded</small>
                                        </div>
                                        <div class="text-center">
                                            <div class="h6 mb-0 font-weight-bold text-{{ $badge->points > 0 ? 'success' : 'secondary' }}">
                                                {{ $badge->points > 0 ? '+' . $badge->points : '0' }}
                                            </div>
                                            <small class="text-muted">Points</small>
                                        </div>
                                        <div class="text-center">
                                            <div class="h6 mb-0 font-weight-bold text-{{ $badge->is_active ? 'success' : 'danger' }}">
                                                <i class="fas fa-{{ $badge->is_active ? 'check' : 'times' }}"></i>
                                            </div>
                                            <small class="text-muted">{{ $badge->is_active ? 'Active' : 'Inactive' }}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Created {{ $badge->created_at->format('M j, Y') }}
                                        </small>
                                        <button class="btn btn-sm btn-outline-primary" onclick="previewBadge({{ $badge->id }})">
                                            <i class="fas fa-eye"></i> Preview
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-medal fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-muted">No Badges Created</h5>
                    <p class="text-muted">Create your first forum badge to get started with the gamification system.</p>
                    <button type="button" class="btn btn-primary" onclick="createBadge()">
                        <i class="fas fa-plus"></i> Create First Badge
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Badge Modal -->
<div class="modal fade" id="badgeModal" tabindex="-1" aria-labelledby="badgeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="badgeModalLabel">Create Badge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="badgeForm">
                <div class="modal-body">
                    <input type="hidden" id="badgeId" name="badge_id">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="badgeName" class="form-label">Badge Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="badgeName" name="name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="badgeDescription" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="badgeDescription" name="description" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="badgeCriteria" class="form-label">Criteria</label>
                                <textarea class="form-control" id="badgeCriteria" name="criteria" rows="2" 
                                          placeholder="Describe how users can earn this badge..."></textarea>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="badgeIcon" class="form-label">Icon</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="badgeIcon" name="icon" 
                                           placeholder="fas fa-medal">
                                    <button class="btn btn-outline-secondary" type="button" onclick="showIconPicker()">
                                        <i class="fas fa-icons"></i>
                                    </button>
                                </div>
                                <div class="form-text">FontAwesome icon class</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Preview</label>
                                <div class="text-center p-3 border rounded" id="badgePreview">
                                    <i class="fas fa-medal fa-3x text-secondary mb-2"></i>
                                    <div class="font-weight-bold">Badge Name</div>
                                    <small class="text-muted">Badge description</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="badgeType" class="form-label">Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="badgeType" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="achievement">Achievement</option>
                                    <option value="participation">Participation</option>
                                    <option value="special">Special</option>
                                    <option value="milestone">Milestone</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="badgeRarity" class="form-label">Rarity <span class="text-danger">*</span></label>
                                <select class="form-select" id="badgeRarity" name="rarity" required>
                                    <option value="">Select Rarity</option>
                                    <option value="common">Common</option>
                                    <option value="rare">Rare</option>
                                    <option value="epic">Epic</option>
                                    <option value="legendary">Legendary</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="badgePoints" class="form-label">Points</label>
                                <input type="number" class="form-control" id="badgePoints" name="points" 
                                       min="0" max="1000" value="0">
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="badgeActive" name="is_active" checked>
                                    <label class="form-check-label" for="badgeActive">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="badgeConditions" class="form-label">Auto-Award Conditions (JSON)</label>
                        <textarea class="form-control font-monospace" id="badgeConditions" name="conditions" rows="4" 
                                  placeholder='{"posts_count": 100, "reputation_points": 500}'></textarea>
                        <div class="form-text">JSON object defining automatic award conditions (optional)</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Badge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Badge Holders Modal -->
<div class="modal fade" id="badgeHoldersModal" tabindex="-1" aria-labelledby="badgeHoldersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="badgeHoldersModalLabel">Badge Holders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="badgeHoldersBody">
                <!-- Content loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Initialize page
$(document).ready(function() {
    initializeForms();
    updateBadgePreview();
});

// Form initialization
function initializeForms() {
    $('#badgeForm').submit(function(e) {
        e.preventDefault();
        submitBadge();
    });
    
    // Update preview when form fields change
    $('#badgeName, #badgeDescription, #badgeIcon, #badgeType, #badgeRarity').on('input change', function() {
        updateBadgePreview();
    });
}

// Badge management functions
function createBadge() {
    $('#badgeModalLabel').text('Create Badge');
    $('#badgeForm')[0].reset();
    $('#badgeId').val('');
    $('#badgeActive').prop('checked', true);
    updateBadgePreview();
    $('#badgeModal').modal('show');
}

function editBadge(badgeId) {
    $('#badgeModalLabel').text('Edit Badge');
    
    $.get(`/admin/forums/badges/${badgeId}/edit`)
        .done(function(badge) {
            $('#badgeId').val(badge.id);
            $('#badgeName').val(badge.name);
            $('#badgeDescription').val(badge.description);
            $('#badgeCriteria').val(badge.criteria);
            $('#badgeIcon').val(badge.icon);
            $('#badgeType').val(badge.type);
            $('#badgeRarity').val(badge.rarity);
            $('#badgePoints').val(badge.points);
            $('#badgeActive').prop('checked', badge.is_active);
            $('#badgeConditions').val(badge.conditions ? JSON.stringify(badge.conditions, null, 2) : '');
            
            updateBadgePreview();
            $('#badgeModal').modal('show');
        })
        .fail(function() {
            showNotification('error', 'Failed to load badge data');
        });
}

function submitBadge() {
    const formData = {
        badge_id: $('#badgeId').val(),
        name: $('#badgeName').val(),
        description: $('#badgeDescription').val(),
        criteria: $('#badgeCriteria').val(),
        icon: $('#badgeIcon').val(),
        type: $('#badgeType').val(),
        rarity: $('#badgeRarity').val(),
        points: $('#badgePoints').val(),
        is_active: $('#badgeActive').is(':checked'),
        conditions: $('#badgeConditions').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    const url = formData.badge_id ? `/admin/forums/badges/${formData.badge_id}` : '/admin/forums/badges';
    const method = formData.badge_id ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message);
                $('#badgeModal').modal('hide');
                location.reload(); // Reload to show updated badge
            } else {
                showNotification('error', response.message || 'Failed to save badge');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'An error occurred while saving the badge';
            showNotification('error', message);
        }
    });
}

function deleteBadge(badgeId) {
    if (!confirm('Are you sure you want to delete this badge? This action cannot be undone.')) {
        return;
    }
    
    $.ajax({
        url: `/admin/forums/badges/${badgeId}`,
        method: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showNotification('success', response.message);
                $(`.badge-item`).has(`[onclick*="${badgeId}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                showNotification('error', response.message || 'Failed to delete badge');
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON?.message || 'An error occurred while deleting the badge';
            showNotification('error', message);
        }
    });
}

function duplicateBadge(badgeId) {
    $.post(`/admin/forums/badges/${badgeId}/duplicate`, {
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.success) {
            showNotification('success', response.message);
            location.reload(); // Reload to show duplicated badge
        } else {
            showNotification('error', response.message || 'Failed to duplicate badge');
        }
    })
    .fail(function(xhr) {
        const message = xhr.responseJSON?.message || 'An error occurred while duplicating the badge';
        showNotification('error', message);
    });
}

function previewBadge(badgeId) {
    // This could open a modal showing how the badge appears to users
    $.get(`/admin/forums/badges/${badgeId}/preview`)
        .done(function(data) {
            // Show preview in a modal or popup
            alert('Badge preview functionality would be implemented here');
        })
        .fail(function() {
            showNotification('error', 'Failed to load badge preview');
        });
}

function viewBadgeHolders(badgeId) {
    $('#badgeHoldersBody').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    $('#badgeHoldersModal').modal('show');
    
    $.get(`/admin/forums/badges/${badgeId}/holders`)
        .done(function(data) {
            $('#badgeHoldersBody').html(data.html);
        })
        .fail(function() {
            $('#badgeHoldersBody').html('<div class="alert alert-danger">Failed to load badge holders.</div>');
        });
}

// Filter badges
function filterBadges(type) {
    const badges = $('.badge-item');
    
    if (type === 'all') {
        badges.show();
    } else {
        badges.hide();
        badges.filter(`[data-type="${type}"]`).show();
    }
}

// Update badge preview
function updateBadgePreview() {
    const name = $('#badgeName').val() || 'Badge Name';
    const description = $('#badgeDescription').val() || 'Badge description';
    const icon = $('#badgeIcon').val() || 'fas fa-medal';
    const rarity = $('#badgeRarity').val() || 'common';
    
    const rarityColors = {
        common: 'secondary',
        rare: 'success',
        epic: 'info',
        legendary: 'warning'
    };
    
    const color = rarityColors[rarity];
    
    $('#badgePreview').html(`
        <i class="${icon} fa-3x text-${color} mb-2"></i>
        <div class="font-weight-bold">${name}</div>
        <small class="text-muted">${description}</small>
    `);
}

// Icon picker (simplified version)
function showIconPicker() {
    const commonIcons = [
        'fas fa-medal', 'fas fa-trophy', 'fas fa-star', 'fas fa-crown',
        'fas fa-gem', 'fas fa-fire', 'fas fa-bolt', 'fas fa-heart',
        'fas fa-thumbs-up', 'fas fa-comments', 'fas fa-user-friends',
        'fas fa-graduation-cap', 'fas fa-rocket', 'fas fa-magic'
    ];
    
    let iconHtml = '<div class="row">';
    commonIcons.forEach(icon => {
        iconHtml += `
            <div class="col-3 text-center mb-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectIcon('${icon}')">
                    <i class="${icon}"></i>
                </button>
            </div>
        `;
    });
    iconHtml += '</div>';
    
    // This would typically open a proper icon picker modal
    // For now, just show an alert with the suggestion
    alert('Icon picker would show here. Common icons: ' + commonIcons.join(', '));
}

function selectIcon(icon) {
    $('#badgeIcon').val(icon);
    updateBadgePreview();
    // Close icon picker modal
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
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-secondary {
    border-left: 0.25rem solid #858796 !important;
}

.badge-item {
    transition: all 0.3s ease;
}

.badge-item:hover {
    transform: translateY(-2px);
}

.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.badge {
    font-size: 0.75em;
}

.font-monospace {
    font-family: 'Courier New', Courier, monospace;
}

.modal-lg {
    max-width: 900px;
}

@media (max-width: 768px) {
    .col-xl-3 {
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }
}
</style>
@endpush