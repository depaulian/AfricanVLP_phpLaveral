@extends('admin.layouts.app')

@section('title', 'Slider Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Slider Management</h3>
                    <a href="{{ route('admin.sliders.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Slider
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Page</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sliders-table">
                                @forelse($sliders as $slider)
                                    <tr data-slider-id="{{ $slider->id }}">
                                        <td>
                                            @if($slider->image_url)
                                                <img src="{{ $slider->getOptimizedImageUrl(100, 60) }}" 
                                                     alt="{{ $slider->title }}" 
                                                     class="img-thumbnail" 
                                                     style="max-width: 100px; max-height: 60px;">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                                     style="width: 100px; height: 60px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $slider->title }}</strong>
                                            @if($slider->subtitle)
                                                <br><small class="text-muted">{{ $slider->subtitle }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $slider->page->title ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $slider->position }}</span>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm toggle-status {{ $slider->status === 'active' ? 'btn-success' : 'btn-secondary' }}"
                                                    data-slider-id="{{ $slider->id }}"
                                                    data-status="{{ $slider->status }}">
                                                {{ ucfirst($slider->status) }}
                                            </button>
                                        </td>
                                        <td>
                                            <small>
                                                {{ $slider->created_at->format('M d, Y') }}<br>
                                                <span class="text-muted">by {{ $slider->creator->name ?? 'System' }}</span>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.sliders.show', $slider) }}" 
                                                   class="btn btn-sm btn-outline-info" 
                                                   title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.sliders.edit', $slider) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('admin.sliders.duplicate', $slider) }}" 
                                                   class="btn btn-sm btn-outline-secondary" 
                                                   title="Duplicate">
                                                    <i class="fas fa-copy"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger delete-slider" 
                                                        data-slider-id="{{ $slider->id }}"
                                                        data-slider-title="{{ $slider->title }}"
                                                        title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-images fa-3x mb-3"></i>
                                                <p>No sliders found. <a href="{{ route('admin.sliders.create') }}">Create your first slider</a>.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($sliders->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $sliders->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the slider "<span id="slider-title"></span>"?</p>
                <p class="text-muted">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-form" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle status functionality
    document.querySelectorAll('.toggle-status').forEach(button => {
        button.addEventListener('click', function() {
            const sliderId = this.dataset.sliderId;
            const currentStatus = this.dataset.status;
            
            fetch(`/admin/sliders/${sliderId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.dataset.status = data.status;
                    this.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                    this.className = `btn btn-sm toggle-status ${data.status === 'active' ? 'btn-success' : 'btn-secondary'}`;
                    
                    // Show success message
                    showAlert('success', data.message);
                } else {
                    showAlert('error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'An error occurred while updating the slider status.');
            });
        });
    });

    // Delete functionality
    document.querySelectorAll('.delete-slider').forEach(button => {
        button.addEventListener('click', function() {
            const sliderId = this.dataset.sliderId;
            const sliderTitle = this.dataset.sliderTitle;
            
            document.getElementById('slider-title').textContent = sliderTitle;
            document.getElementById('delete-form').action = `/admin/sliders/${sliderId}`;
            
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });
    });

    // Make table rows sortable (if you want drag-and-drop reordering)
    // This would require additional JavaScript library like Sortable.js
});

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const container = document.querySelector('.card-body');
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = container.querySelector('.alert');
        if (alert) {
            alert.remove();
        }
    }, 5000);
}
</script>
@endpush