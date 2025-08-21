@extends('admin.layouts.app')

@section('title', 'Create New Slider')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Create New Slider</h3>
                    <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Sliders
                    </a>
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

                    <form action="{{ route('admin.sliders.store') }}" method="POST" enctype="multipart/form-data" id="slider-form">
                        @csrf
                        
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
                                                   value="{{ old('title') }}" required maxlength="255">
                                        </div>

                                        <div class="mb-3">
                                            <label for="subtitle" class="form-label">Subtitle</label>
                                            <input type="text" class="form-control" id="subtitle" name="subtitle" 
                                                   value="{{ old('subtitle') }}" maxlength="255">
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description</label>
                                            <textarea class="form-control" id="description" name="description" 
                                                      rows="4">{{ old('description') }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Upload -->
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Slider Image</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="image" class="form-label">Image <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" id="image" name="image" 
                                                   accept="image/*" required>
                                            <div class="form-text">
                                                Recommended size: 1920x800px. Max file size: 5MB. 
                                                Supported formats: JPEG, PNG, JPG, GIF, WebP.
                                            </div>
                                        </div>

                                        <div id="image-preview" class="mt-3" style="display: none;">
                                            <img id="preview-img" src="" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
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
                                                   value="{{ old('link_url') }}" maxlength="255">
                                            <div class="form-text">Optional: URL to redirect when slider is clicked</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="link_text" class="form-label">Link Text</label>
                                            <input type="text" class="form-control" id="link_text" name="link_text" 
                                                   value="{{ old('link_text') }}" maxlength="100">
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
                                                    <option value="{{ $page->id }}" {{ old('page_id') == $page->id ? 'selected' : '' }}>
                                                        {{ $page->title }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="position" class="form-label">Position</label>
                                            <input type="number" class="form-control" id="position" name="position" 
                                                   value="{{ old('position') }}" min="0">
                                            <div class="form-text">Leave empty to add at the end</div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="text_position" class="form-label">Text Position</label>
                                            <select class="form-select" id="text_position" name="text_position">
                                                <option value="left" {{ old('text_position') == 'left' ? 'selected' : '' }}>Left</option>
                                                <option value="center" {{ old('text_position') == 'center' ? 'selected' : '' }}>Center</option>
                                                <option value="right" {{ old('text_position') == 'right' ? 'selected' : '' }}>Right</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="animation_type" class="form-label">Animation Type</label>
                                            <select class="form-select" id="animation_type" name="animation_type">
                                                <option value="fade" {{ old('animation_type') == 'fade' ? 'selected' : '' }}>Fade</option>
                                                <option value="slide" {{ old('animation_type') == 'slide' ? 'selected' : '' }}>Slide</option>
                                                <option value="zoom" {{ old('animation_type') == 'zoom' ? 'selected' : '' }}>Zoom</option>
                                            </select>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="show_overlay" name="show_overlay" 
                                                   value="1" {{ old('show_overlay') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="show_overlay">
                                                Show Text Overlay
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save"></i> Create Slider
                                            </button>
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
        const image = document.getElementById('image').files[0];
        const pageId = document.getElementById('page_id').value;

        if (!title) {
            e.preventDefault();
            alert('Please enter a title for the slider.');
            return;
        }

        if (!image) {
            e.preventDefault();
            alert('Please select an image for the slider.');
            return;
        }

        if (!pageId) {
            e.preventDefault();
            alert('Please select a page for the slider.');
            return;
        }

        // Check file size (5MB limit)
        if (image && image.size > 5 * 1024 * 1024) {
            e.preventDefault();
            alert('Image file size must be less than 5MB.');
            return;
        }
    });
});
</script>
@endpush
