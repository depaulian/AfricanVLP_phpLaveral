@extends('layouts.admin')

@section('title', 'Resource Files Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Resource Files Management</h1>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-upload"></i> Upload Files
            </button>
        </div>
    </div>

    <!-- File Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Files</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-files">{{ $stats['total_files'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Images</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['images_count'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-image fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Documents</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['documents_count'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Size</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format(($stats['total_size'] ?? 0) / 1024 / 1024, 1) }}MB</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Files</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="{{ request('search') }}" placeholder="Search files...">
                </div>
                
                <div class="col-md-2">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <option value="images" {{ request('category') == 'images' ? 'selected' : '' }}>Images</option>
                        <option value="documents" {{ request('category') == 'documents' ? 'selected' : '' }}>Documents</option>
                        <option value="videos" {{ request('category') == 'videos' ? 'selected' : '' }}>Videos</option>
                        <option value="audio" {{ request('category') == 'audio' ? 'selected' : '' }}>Audio</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="resource_id" class="form-label">Resource</label>
                    <select class="form-select" id="resource_id" name="resource_id">
                        <option value="">All Resources</option>
                        @foreach($resources as $resource)
                            <option value="{{ $resource->id }}" {{ request('resource_id') == $resource->id ? 'selected' : '' }}>
                                {{ $resource->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    @if(request()->hasAny(['search', 'category', 'resource_id', 'status']))
                        <a href="{{ route('admin.resources.files') }}" class="btn btn-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Files Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Files</h6>
        </div>
        <div class="card-body">
            @if($files->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="filesTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Preview</th>
                                <th>File Name</th>
                                <th>Resource</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Downloads</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $file)
                                <tr>
                                    <td class="text-center" style="width: 80px;">
                                        @if($file->isImage())
                                            <img src="{{ $file->getThumbnailUrl() }}" alt="{{ $file->original_filename }}" 
                                                 class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: #f8f9fc; border-radius: 0.35rem;">
                                                @if($file->isDocument())
                                                    <i class="fas fa-file-alt fa-2x text-primary"></i>
                                                @elseif($file->isVideo())
                                                    <i class="fas fa-video fa-2x text-info"></i>
                                                @elseif($file->isAudio())
                                                    <i class="fas fa-music fa-2x text-warning"></i>
                                                @else
                                                    <i class="fas fa-file fa-2x text-secondary"></i>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $file->original_filename }}</div>
                                        @if($file->description)
                                            <small class="text-muted">{{ Str::limit($file->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($file->resource)
                                            <a href="{{ route('admin.resources.show', $file->resource) }}" class="text-decoration-none">
                                                {{ $file->resource->title }}
                                            </a>
                                        @else
                                            <span class="text-muted">No Resource</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $file->isImage() ? 'success' : ($file->isDocument() ? 'primary' : ($file->isVideo() ? 'info' : 'warning')) }}">
                                            {{ strtoupper($file->file_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $file->getHumanReadableSize() }}</td>
                                    <td>{{ $file->download_count ?? 0 }}</td>
                                    <td>
                                        <span class="badge badge-{{ $file->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($file->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $file->created->format('M j, Y') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ $file->getFileUrl() }}" target="_blank" 
                                               class="btn btn-sm btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ $file->getFileUrl() }}" download 
                                               class="btn btn-sm btn-outline-success" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="editFile({{ $file->id }})" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteFile({{ $file->id }})" title="Delete">
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
                <div class="d-flex justify-content-center">
                    {{ $files->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-file fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">No files found</h5>
                    <p class="text-muted">Upload some files to get started.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="fas fa-upload"></i> Upload Files
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadModalLabel">Upload Files</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @include('admin.files.upload-widget', ['uploadId' => 'modal', 'multiple' => true, 'folder' => 'resources'])
            </div>
        </div>
    </div>
</div>

<!-- Edit File Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="edit-file-id">
                    
                    <div class="mb-3">
                        <label for="edit-description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit-description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-resource-id" class="form-label">Assign to Resource</label>
                        <select class="form-select" id="edit-resource-id">
                            <option value="">No Resource</option>
                            @foreach($resources as $resource)
                                <option value="{{ $resource->id }}">{{ $resource->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-status" class="form-label">Status</label>
                        <select class="form-select" id="edit-status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editFile(fileId) {
    // Fetch file data and populate edit modal
    fetch(`/admin/files/${fileId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit-file-id').value = data.id;
            document.getElementById('edit-description').value = data.description || '';
            document.getElementById('edit-resource-id').value = data.resource_id || '';
            document.getElementById('edit-status').value = data.status;
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load file data');
        });
}

function deleteFile(fileId) {
    if (confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
        fetch(`/admin/files/${fileId}`, {
            method: 'DELETE',
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
                alert('Failed to delete file: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to delete file');
        });
    }
}

// Handle edit form submission
document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const fileId = document.getElementById('edit-file-id').value;
    const formData = {
        description: document.getElementById('edit-description').value,
        resource_id: document.getElementById('edit-resource-id').value || null,
        status: document.getElementById('edit-status').value
    };
    
    fetch(`/admin/files/${fileId}`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            location.reload();
        } else {
            alert('Failed to update file: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update file');
    });
});
</script>
@endsection