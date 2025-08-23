@extends('layouts.app')

@section('title', 'Edit Slider')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Edit Slider: {{ $slider->title }}</h3>
                    <div class="btn-group">
                        <a href="{{ route('admin.sliders.show', $slider) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Sliders
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.sliders.update', $slider) }}" method="POST" enctype="multipart/form-data" id="slider-form">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Basic Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Basic Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="{{ old('title', $slider->title) }}" required maxlength="255">
                                        </div>

                                        <div class="mb-3">
                                            <label for="subtitle" class="form-label">Subtitle</label>
                                            <input type="text" class="form-control" id="subtitle" name="subtitle" 
                                                   value="{{ old('subtitle', $slider->subtitle) }}" maxlength="255">
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" 
                                                      rows="4">{{ old('description', $slider->description) }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Current Image & Upload -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Slider Image</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($slider->image_url)
                                            <div class="mb-3">
                                                <label class="form-label">Current Image</label>
                                                <div class="current-image">
                                                    <img src="{{ $slider->getOptimizedImageUrl(400, 200) }}" 
                                                         alt="{{ $slider->title }}" 
                                                         class="img-fluid rounded border" 
                                                         style="max-height: 200px;">
                                                </div>
                                            </div>
                                        @endif

                                        <div class="mb-3">
                                            <label for="image" class="form-label">
                                                {{ $slider->image_url ? 'Replace Image' : 'Upload Image' }}
                                                @if(!$slider->image_url)<span class="text-danger">*</span>@endif
                                            </label>
                                            <input type="file" class="form-control" id="image" name="image" 
                                                   accept="image/*" {{ !$slider->image_url ? 'required' : '' }}>
                                            <div class="form-text">
                                                Recommended size: 1920x800px. Max file size: 5MB. 
                                                Supported formats: JPEG, PNG, JPG, GIF, WebP.
                                                @if($slider->image_url)
                                                    <br><strong>Leave empty to keep current image.</strong>
                                                @endif
                                            </div>
                                        </div>

                                        <div id="image-preview" class="mt-3" style="display: none;">
                                            <label class="form-label">New Image Preview</label>
                                            <div>
                                                <img id="preview-img" src="" alt="Preview" class="img-fluid rounded border" style="max-height: 200px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Link Information -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Link Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="link_url" class="form-label">Link URL</label>
                                            <input type="url" class="form-control" id="link_url" name="link_url" 
                                                   value="{{ old('link_url', $slider->link_url) }}" maxlength="255">
                                            <div class="form-text">Optional: URL to redirect when slider is clicked</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="link_text" class="form-label">Link Text</label>
                                            <input type="text" class="form-control" id="link_text" name="link_text" 
                                                   value="{{ old('link_text', $slider->link_text) }}" maxlength="100">
                                            <div class="form-text">Text for the call-to-action button</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- Settings -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Settings</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="page_id" class="form-label">Page <span class="text-danger">*</span></label>
                                            <select class="form-select" id="page_id" name="page_id" required>
                                                <option value="">Select Page</option>
                                                @foreach($pages as $page)
                                                    <option value="{{ $page->id }}" 
                                                            {{ old('page_id', $slider->page_id) == $page->id ? 'selected' : '' }}>
                                                        {{ $page->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="active" {{ old('status', $slider->status) == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status', $slider->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="position" class="form-label">Position</label>
                                            <input type="number" class="form-control" id="position" name="position" 
                                                   value="{{ old('position', $slider->position) }}" min="0">
                                            <div class="form-text">Order of appearance in the slider</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="text_position" class="form-label">Text Position</label>
                                            <select class="form-select" id="text_position" name="text_position">
                                                <option value="left" {{ old('text_position', $slider->text_position) == 'left' ? 'selected' : '' }}>Left</option>
                                                <option value="center" {{ old('text_position', $slider->text_position) == 'center' ? 'selected' : '' }}>Center</option>
                                                <option value="right" {{ old('text_position', $slider->text_position) == 'right' ? 'selected' : '' }}>Right</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="animation_type" class="form-label">Animation Type</label>
                                            <select class="form-select" id="animation_type" name="animation_type">
                                                <option value="fade" {{ old('animation_type', $slider->animation_type) == 'fade' ? 'selected' : '' }}>Fade</option>
                                                <option value="slide" {{ old('animation_type', $slider->animation_type) == 'slide' ? 'selected' : '' }}>Slide</option>
                                                <option value="zoom" {{ old('animation_type', $slider->animation_type) == 'zoom' ? 'selected' : '' }}>Zoom</option>
                                            </select>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="show_overlay" name="show_overlay" 
                                                   value="1" {{ old('show_overlay', $slider->show_overlay) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="show_overlay">
                                                Show Text Overlay
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Metadata -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Metadata</h5>
                                    </div>
                                    <div class="card-body">
                                        <small class="text-muted">
                                            <strong>Created:</strong> {{ $slider->created_at->format('M d, Y H:i') }}<br>
                                            @if($slider->creator)
                                                <strong>By:</strong> {{ $slider->creator->name }}<br>
                                            @endif
                                            <strong>Updated:</strong> {{ $slider->updated_at->format('M d, Y H:i') }}<br>
                                            @if($slider->updater)
                                                <strong>By:</strong> {{ $slider->updater->name }}
                                            @endif
                                        </small>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Update Slider
                                            </button>
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
                                            <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Image preview functionality
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');

    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            imagePreview.style.display = 'none';
        }
    });

    // Form validation
    const form = document.getElementById('slider-form');
    form.addEventListener('submit', function(e) {
        const title = document.getElementById('title').value.trim();
        const pageId = document.getElementById('page_id').value;

        if (!title) {
            e.preventDefault();
            alert('Please enter a title for the slider.');
            return;
        }

        if (!pageId) {
            e.preventDefault();
            alert('Please select a page for the slider.');
            return;
        }

        // Check file size if new image is selected
        const image = document.getElementById('image').files[0];
        if (image && image.size > 5 * 1024 * 1024) {
            e.preventDefault();
            alert('Image file size must be less than 5MB.');
            return;
        }
    });
});

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
</script>
@endpush
