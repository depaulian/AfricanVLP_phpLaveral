@extends('layouts.admin')

@section('title', 'Document Verification Queue')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Document Verification Queue</h1>
            <p class="mb-0 text-muted">Review and verify user-submitted documents</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkActionModal">
                <i class="fas fa-tasks"></i> Bulk Actions
            </button>
            <a href="{{ route('admin.documents.verification.history') }}" class="btn btn-outline-secondary">
                <i class="fas fa-history"></i> History
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Verification
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['pending_verification'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
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
                                Verified Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['verified_today'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                Avg. Processing Time
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ round($statistics['average_verification_time_hours'] ?? 0, 1) }}h
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-stopwatch fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Documents
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($statistics['total_documents'] ?? 0) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.documents.verification.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label for="category" class="form-label">Category</label>
                        <select name="category" id="category" class="form-select">
                            <option value="all">All Categories</option>
                            @foreach($categories as $key => $category)
                                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>
                                    {{ $category['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="user_search" class="form-label">User Search</label>
                        <input type="text" name="user_search" id="user_search" class="form-control" 
                               placeholder="Name or email" value="{{ request('user_search') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="sort_by" class="form-label">Sort By</label>
                        <select name="sort_by" id="sort_by" class="form-select">
                            <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Upload Date</option>
                            <option value="file_size" {{ request('sort_by') === 'file_size' ? 'selected' : '' }}>File Size</option>
                            <option value="user_name" {{ request('sort_by') === 'user_name' ? 'selected' : '' }}>User Name</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('admin.documents.verification.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Pending Documents</h6>
            <div class="d-flex align-items-center">
                <label class="me-2">
                    <input type="checkbox" id="selectAll" class="form-check-input"> Select All
                </label>
                <button type="button" class="btn btn-sm btn-success me-2" id="bulkApprove" disabled>
                    <i class="fas fa-check"></i> Approve Selected
                </button>
                <button type="button" class="btn btn-sm btn-danger" id="bulkReject" disabled>
                    <i class="fas fa-times"></i> Reject Selected
                </button>
            </div>
        </div>
        <div class="card-body">
            @if($documents->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="documentsTable">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="selectAllHeader" class="form-check-input">
                                </th>
                                <th>Document</th>
                                <th>User</th>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Uploaded</th>
                                <th>Priority</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $document)
                                <tr data-document-id="{{ $document->id }}">
                                    <td>
                                        <input type="checkbox" class="form-check-input document-checkbox" 
                                               value="{{ $document->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="file-icon me-2">
                                                @if($document->isImage())
                                                    <i class="fas fa-image text-info"></i>
                                                @elseif($document->isPdf())
                                                    <i class="fas fa-file-pdf text-danger"></i>
                                                @else
                                                    <i class="fas fa-file text-secondary"></i>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-weight-bold">{{ $document->name ?: $document->file_name }}</div>
                                                <small class="text-muted">{{ $document->file_name }}</small>
                                                @if($document->is_sensitive)
                                                    <span class="badge badge-warning badge-sm ms-1">Sensitive</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="font-weight-bold">{{ $document->user->name }}</div>
                                            <small class="text-muted">{{ $document->user->email }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            {{ $categories[$document->category]['name'] ?? ucfirst($document->category) }}
                                        </span>
                                    </td>
                                    <td>{{ $document->file_size_human }}</td>
                                    <td>
                                        <div>{{ $document->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $document->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $priority = 'normal';
                                            $priorityClass = 'secondary';
                                            $daysWaiting = $document->created_at->diffInDays(now());
                                            
                                            if ($daysWaiting > 7) {
                                                $priority = 'high';
                                                $priorityClass = 'danger';
                                            } elseif ($daysWaiting > 3) {
                                                $priority = 'medium';
                                                $priorityClass = 'warning';
                                            }
                                        @endphp
                                        <span class="badge badge-{{ $priorityClass }}">
                                            {{ ucfirst($priority) }}
                                        </span>
                                        @if($daysWaiting > 0)
                                            <small class="d-block text-muted">{{ $daysWaiting }}d waiting</small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.documents.verification.show', $document) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Review">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.documents.download', $document) }}" 
                                               class="btn btn-sm btn-outline-secondary" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success quick-approve" 
                                                    data-document-id="{{ $document->id }}" title="Quick Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger quick-reject" 
                                                    data-document-id="{{ $document->id }}" title="Quick Reject">
                                                <i class="fas fa-times"></i>
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
                    <div>
                        Showing {{ $documents->firstItem() }} to {{ $documents->lastItem() }} 
                        of {{ $documents->total() }} results
                    </div>
                    {{ $documents->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">No documents pending verification</h5>
                    <p class="text-muted">All documents have been processed!</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Bulk Action Modal -->
<div class="modal fade" id="bulkActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Document Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkActionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="bulkAction" class="form-label">Action</label>
                        <select id="bulkAction" class="form-select" required>
                            <option value="">Select action...</option>
                            <option value="approve">Approve Selected</option>
                            <option value="reject">Reject Selected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="bulkNotes" class="form-label">Notes (optional)</label>
                        <textarea id="bulkNotes" class="form-control" rows="3" 
                                  placeholder="Add notes for this action..."></textarea>
                    </div>
                    <div id="selectedDocuments" class="mb-3">
                        <strong>Selected Documents:</strong>
                        <div id="selectedDocumentsList" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Execute Action</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Reject Modal -->
<div class="modal fade" id="quickRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickRejectForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejectReason" class="form-label">Reason for rejection <span class="text-danger">*</span></label>
                        <select id="rejectReason" class="form-select" required>
                            <option value="">Select reason...</option>
                            <option value="poor_quality">Poor image quality</option>
                            <option value="incomplete">Incomplete document</option>
                            <option value="expired">Document expired</option>
                            <option value="wrong_type">Wrong document type</option>
                            <option value="unreadable">Document unreadable</option>
                            <option value="fraudulent">Suspected fraudulent document</option>
                            <option value="other">Other (specify below)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="rejectNotes" class="form-label">Additional notes</label>
                        <textarea id="rejectNotes" class="form-control" rows="3" 
                                  placeholder="Provide specific feedback to help the user..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Document</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all functionality
    $('#selectAll, #selectAllHeader').change(function() {
        const isChecked = $(this).is(':checked');
        $('.document-checkbox').prop('checked', isChecked);
        updateBulkActionButtons();
    });

    // Individual checkbox change
    $('.document-checkbox').change(function() {
        updateBulkActionButtons();
        updateSelectAllState();
    });

    // Update bulk action button states
    function updateBulkActionButtons() {
        const selectedCount = $('.document-checkbox:checked').length;
        $('#bulkApprove, #bulkReject').prop('disabled', selectedCount === 0);
    }

    // Update select all checkbox state
    function updateSelectAllState() {
        const totalCheckboxes = $('.document-checkbox').length;
        const checkedCheckboxes = $('.document-checkbox:checked').length;
        
        $('#selectAll, #selectAllHeader').prop('checked', checkedCheckboxes === totalCheckboxes);
    }

    // Quick approve
    $('.quick-approve').click(function() {
        const documentId = $(this).data('document-id');
        
        if (confirm('Are you sure you want to approve this document?')) {
            performQuickAction(documentId, 'approve');
        }
    });

    // Quick reject
    $('.quick-reject').click(function() {
        const documentId = $(this).data('document-id');
        $('#quickRejectForm').data('document-id', documentId);
        $('#quickRejectModal').modal('show');
    });

    // Quick reject form submission
    $('#quickRejectForm').submit(function(e) {
        e.preventDefault();
        
        const documentId = $(this).data('document-id');
        const reason = $('#rejectReason').val();
        const notes = $('#rejectNotes').val();
        
        if (!reason) {
            alert('Please select a reason for rejection.');
            return;
        }
        
        const fullNotes = reason === 'other' ? notes : reason + (notes ? ': ' + notes : '');
        
        performQuickAction(documentId, 'reject', fullNotes);
        $('#quickRejectModal').modal('hide');
    });

    // Bulk actions
    $('#bulkApprove').click(function() {
        showBulkActionModal('approve');
    });

    $('#bulkReject').click(function() {
        showBulkActionModal('reject');
    });

    // Show bulk action modal
    function showBulkActionModal(action) {
        const selectedIds = $('.document-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            alert('Please select documents to process.');
            return;
        }

        $('#bulkAction').val(action);
        updateSelectedDocumentsList(selectedIds);
        $('#bulkActionModal').modal('show');
    }

    // Update selected documents list in modal
    function updateSelectedDocumentsList(selectedIds) {
        const list = $('#selectedDocumentsList');
        list.empty();

        selectedIds.forEach(function(id) {
            const row = $(`tr[data-document-id="${id}"]`);
            const documentName = row.find('td:nth-child(2) .font-weight-bold').text();
            const userName = row.find('td:nth-child(3) .font-weight-bold').text();
            
            list.append(`
                <div class="d-flex justify-content-between align-items-center border-bottom py-1">
                    <span>${documentName}</span>
                    <small class="text-muted">${userName}</small>
                </div>
            `);
        });
    }

    // Bulk action form submission
    $('#bulkActionForm').submit(function(e) {
        e.preventDefault();
        
        const action = $('#bulkAction').val();
        const notes = $('#bulkNotes').val();
        const selectedIds = $('.document-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (!action || selectedIds.length === 0) {
            alert('Please select an action and documents to process.');
            return;
        }

        performBulkAction(selectedIds, action, notes);
        $('#bulkActionModal').modal('hide');
    });

    // Perform quick action
    function performQuickAction(documentId, action, notes = '') {
        $.ajax({
            url: `/admin/documents/${documentId}/verify`,
            method: 'POST',
            data: {
                action: action,
                notes: notes,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showAlert('success', `Document ${action}d successfully.`);
                $(`tr[data-document-id="${documentId}"]`).fadeOut();
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || `Failed to ${action} document.`;
                showAlert('error', message);
            }
        });
    }

    // Perform bulk action
    function performBulkAction(documentIds, action, notes) {
        $.ajax({
            url: '{{ route("admin.documents.verification.bulk-verify") }}',
            method: 'POST',
            data: {
                document_ids: documentIds,
                action: action,
                notes: notes,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showAlert('success', response.message);
                
                // Remove processed documents from table
                documentIds.forEach(function(id) {
                    $(`tr[data-document-id="${id}"]`).fadeOut();
                });
                
                // Reset checkboxes
                $('.document-checkbox').prop('checked', false);
                $('#selectAll, #selectAllHeader').prop('checked', false);
                updateBulkActionButtons();
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to process documents.';
                showAlert('error', message);
            }
        });
    }

    // Show alert message
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('.container-fluid').prepend(alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endpush

@push('styles')
<style>
.file-icon {
    font-size: 1.2em;
}

.badge-sm {
    font-size: 0.7em;
}

.table td {
    vertical-align: middle;
}

.btn-group .btn {
    border-radius: 0.25rem;
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

#selectedDocumentsList {
    max-height: 200px;
    overflow-y: auto;
}
</style>
@endpush