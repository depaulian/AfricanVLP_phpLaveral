@extends('admin.layouts.app')

@section('title', 'View Slider')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Slider Details: {{ $slider->title }}</h3>
                    <div class="btn-group">
                        <a href="{{ route('admin.sliders.edit', $slider) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Sliders
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Slider Image -->
                            @if($slider->image_url)
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Slider Image</h5>
                                </div>
                                <div class="card-body text-center">
                                    <img src="{{ $slider->getOptimizedImageUrl(800, 400) }}" 
                                         alt="{{ $slider->title }}" 
                                         class="img-fluid rounded border shadow-sm">
                                </div>
                            </div>
                            @endif

                            <!-- Basic Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Basic Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Title:</strong>
                                            <p class="mb-3">{{ $slider->title }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Subtitle:</strong>
                                            <p class="mb-3">{{ $slider->subtitle ?: 'Not set' }}</p>
                                        </div>
                                    </div>
                                    
                                    @if($slider->description)
                                    <div class="row">
                                        <div class="col-12">
                                            <strong>Description:</strong>
                                            <p class="mb-0">{{ $slider->description }}</p>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Link Information -->
                            @if($slider->link_url || $slider->link_text)
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Link Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @if($slider->link_url)
                                        <div class="col-md-8">
                                            <strong>Link URL:</strong>
                                            <p class="mb-3">
                                                <a href="{{ $slider->link_url }}" target="_blank" rel="noopener noreferrer">
                                                    {{ $slider->link_url }}
                                                    <i class="fas fa-external-link-alt ms-1"></i>
                                                </a>
                                            </p>
                                        </div>
                                        @endif
                                        
                                        @if($slider->link_text)
                                        <div class="col-md-4">
                                            <strong>Link Text:</strong>
                                            <p class="mb-3">{{ $slider->link_text }}</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Preview -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Slider Preview</h5>
                                </div>
                                <div class="card-body">
                                    <div class="slider-preview position-relative" style="height: 300px; overflow: hidden;">
                                        @if($slider->image_url)
                                            <img src="{{ $slider->getOptimizedImageUrl(600, 300) }}" 
                                                 alt="{{ $slider->title }}" 
                                                 class="w-100 h-100" 
                                                 style="object-fit: cover;">
                                        @endif
                                        
                                        @if($slider->show_overlay)
                                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center" 
                                             style="background: rgba(0,0,0,0.4);">
                                            <div class="container">
                                                <div class="text-white text-{{ $slider->text_position }}">
                                                    <h2 class="mb-2">{{ $slider->title }}</h2>
                                                    @if($slider->subtitle)
                                                        <h4 class="mb-3 opacity-75">{{ $slider->subtitle }}</h4>
                                                    @endif
                                                    @if($slider->description)
                                                        <p class="mb-3">{{ Str::limit($slider->description, 150) }}</p>
                                                    @endif
                                                    @if($slider->link_text && $slider->link_url)
                                                        <a href="{{ $slider->link_url }}" 
                                                           class="btn btn-primary" 
                                                           target="{{ $slider->getLinkTarget() }}">
                                                            {{ $slider->link_text }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    <small class="text-muted">
                                        This is a preview of how the slider will appear on the website.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <!-- Status & Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Status & Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Status:</strong>
                                        <span class="badge bg-{{ $slider->status === 'active' ? 'success' : 'secondary' }} ms-2">
                                            {{ ucfirst($slider->status) }}
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Page:</strong>
                                        <p class="mb-0">{{ $slider->page->title ?? 'Unknown' }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Position:</strong>
                                        <p class="mb-0">{{ $slider->position }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Text Position:</strong>
                                        <p class="mb-0">{{ ucfirst($slider->text_position) }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Animation Type:</strong>
                                        <p class="mb-0">{{ ucfirst($slider->animation_type) }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Show Overlay:</strong>
                                        <span class="badge bg-{{ $slider->show_overlay ? 'success' : 'secondary' }} ms-2">
                                            {{ $slider->show_overlay ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Metadata -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Metadata</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Created:</strong>
                                        <p class="mb-1">{{ $slider->created_at->format('M d, Y H:i') }}</p>
                                        @if($slider->creator)
                                            <small class="text-muted">by {{ $slider->creator->name }}</small>
                                        @endif
                                    </div>

                                    <div class="mb-3">
                                        <strong>Last Updated:</strong>
                                        <p class="mb-1">{{ $slider->updated_at->format('M d, Y H:i') }}</p>
                                        @if($slider->updater)
                                            <small class="text-muted">by {{ $slider->updater->name }}</small>
                                        @endif
                                    </div>

                                    @if($slider->image_url)
                                    <div class="mb-3">
                                        <strong>Image URL:</strong>
                                        <p class="mb-0">
                                            <a href="{{ $slider->image_url }}" target="_blank" rel="noopener noreferrer" class="text-break">
                                                {{ Str::limit($slider->image_url, 50) }}
                                                <i class="fas fa-external-link-alt ms-1"></i>
                                            </a>
                                        </p>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('admin.sliders.edit', $slider) }}" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> Edit Slider
                                        </a>
                                        
                                        <button type="button" class="btn btn-warning" onclick="toggleStatus()">
                                            <i class="fas fa-toggle-{{ $slider->status === 'active' ? 'on' : 'off' }}"></i> 
                                            {{ $slider->status === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>

                                        <form action="{{ route('admin.sliders.duplicate', $slider) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-info w-100">
                                                <i class="fas fa-copy"></i> Duplicate
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-danger" onclick="deleteSlider()">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>

                                        <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to List
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this slider?</p>
                <p><strong>Title:</strong> {{ $slider->title }}</p>
                <p class="text-danger"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.sliders.destroy', $slider) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Slider</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Toggle status function
function toggleStatus() {
    if (confirm('Are you sure you want to change the status of this slider?')) {
        fetch(`{{ route('admin.sliders.toggle-status', $slider) }}`, {
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
                alert('Failed to update slider status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the slider status.');
        });
    }
}

// Delete slider function
function deleteSlider() {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>
@endpush
