@extends('layouts.client')

@section('title', 'Upload Document')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Upload Document</h2>
                    <p class="text-muted mb-0">Add a new document to your profile</p>
                </div>
                <a href="{{ route('profile.documents.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Documents
                </a>
            </div>

            <!-- Upload Form -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Document Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.documents.store') }}" method="POST" enctype="multipart/form-data" id="documentUploadForm">
                        @csrf
                        
                        <!-- Document Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Document Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required
                                   placeholder="Enter a descriptive name for this document">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Choose a name that helps you identify this document later.</div>
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select @error('category') is-invalid @enderror" 
                                    id="category" name="category" required>
                                <option value="">Select a category...</option>
                                @foreach($categories as $key => $category)
                                    <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}
                                            data-description="{{ $category['description'] }}"
                                            data-max-files="{{ $category['max_files'] }}"
                                            data-requires-verification="{{ $category['required_verification'] ? 'true' : 'false' }}">
                                        {{ $category['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="categoryDescription" class="form-text"></div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3"
                                      placeholder="Optional description or notes about this document">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- File Upload -->
                        <div class="mb-3">
                            <label for="file" class="form-label">Document File <span class="text-danger">*</span></label>
                            <div class="file-upload-area border-2 border-dashed rounded p-4 text-center" 
                                 id="fileUploadArea">
                                <input type="file" class="form-control @error('file') is-invalid @enderror" 
                                       id="file" name="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                                       style="display: none;">
                                <div id="uploadPrompt">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h5>Drop your file here or click to browse</h5>
                                    <p class="text-muted mb-0">
                                        Supported formats: PDF, DOC, DOCX, JPG, PNG<br>
                                        Maximum file size: {{ config('documents.max_file_size', 10485760) / 1024 / 1024 }}MB
                                    </p>
                                </div>
                                <div id="filePreview" style="display: none;">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <div class="file-icon me-3">
                                            <i class="fas fa-file fa-2x text-primary"></i>
                                        </div>
                                        <div class="text-start">
                                            <div class="fw-bold" id="fileName"></div>
                                            <div class="text-muted" id="fileSize"></div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger ms-3" id="removeFile">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @error('file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Expiry Date -->
                        <div class="mb-3">
                            <label for="expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control @error('expiry_date') is-invalid @enderror" 
                                   id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}"
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                            @error('expiry_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Leave blank if the document doesn't expire.</div>
                        </div>

                        <!-- Sensitive Document -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_sensitive" name="is_sensitive" value="1"
                                       {{ old('is_sensitive') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_sensitive">
                                    This is a sensitive document
                                </label>
                            </div>
                            <div class="form-text">
                                Sensitive documents have additional privacy protections and cannot be shared.
                            </div>
                        </div>

                        <!-- Verification Notice -->
                        <div id="verificationNotice" class="alert alert-info" style="display: none;">
                            <i class="fas fa-info-circle"></i>
                            <strong>Verification Required:</strong> 
                            Documents in this category require admin verification before they become active.
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('profile.documents.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-upload"></i> Upload Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Upload Guidelines -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Upload Guidelines</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-success"><i class="fas fa-check-circle"></i> Best Practices</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i> Use clear, descriptive names</li>
                                <li><i class="fas fa-check text-success me-2"></i> Ensure documents are readable</li>
                                <li><i class="fas fa-check text-success me-2"></i> Upload high-quality scans</li>
                                <li><i class="fas fa-check text-success me-2"></i> Include all required pages</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger"><i class="fas fa-exclamation-triangle"></i> Avoid</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-times text-danger me-2"></i> Blurry or dark images</li>
                                <li><i class="fas fa-times text-danger me-2"></i> Partial or cropped documents</li>
                                <li><i class="fas fa-times text-danger me-2"></i> Expired documents</li>
                                <li><i class="fas fa-times text-danger me-2"></i> Documents with personal info of others</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Category change handler
    $('#category').change(function() {
        const selectedOption = $(this).find('option:selected');
        const description = selectedOption.data('description');
        const requiresVerification = selectedOption.data('requires-verification');
        
        $('#categoryDescription').text(description || '');
        
        if (requiresVerification) {
            $('#verificationNotice').show();
        } else {
            $('#verificationNotice').hide();
        }
    });

    // File upload area click handler
    $('#fileUploadArea').click(function(e) {
        if (e.target.id !== 'removeFile' && !$(e.target).closest('#removeFile').length) {
            $('#file').click();
        }
    });

    // Drag and drop handlers
    $('#fileUploadArea').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('border-primary bg-light');
    });

    $('#fileUploadArea').on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('border-primary bg-light');
    });

    $('#fileUploadArea').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('border-primary bg-light');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            $('#file')[0].files = files;
            handleFileSelect(files[0]);
        }
    });

    // File input change handler
    $('#file').change(function() {
        const file = this.files[0];
        if (file) {
            handleFileSelect(file);
        }
    });

    // Handle file selection
    function handleFileSelect(file) {
        // Validate file size
        const maxSize = {{ config('documents.max_file_size', 10485760) }};
        if (file.size > maxSize) {
            alert('File size exceeds the maximum allowed size of ' + (maxSize / 1024 / 1024) + 'MB');
            return;
        }

        // Update UI
        $('#fileName').text(file.name);
        $('#fileSize').text(formatFileSize(file.size));
        
        // Update file icon based on type
        const fileIcon = $('#filePreview .file-icon i');
        if (file.type.startsWith('image/')) {
            fileIcon.removeClass().addClass('fas fa-image fa-2x text-info');
        } else if (file.type === 'application/pdf') {
            fileIcon.removeClass().addClass('fas fa-file-pdf fa-2x text-danger');
        } else {
            fileIcon.removeClass().addClass('fas fa-file fa-2x text-primary');
        }
        
        $('#uploadPrompt').hide();
        $('#filePreview').show();
    }

    // Remove file handler
    $('#removeFile').click(function(e) {
        e.stopPropagation();
        $('#file').val('');
        $('#filePreview').hide();
        $('#uploadPrompt').show();
    });

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Form submission handler
    $('#documentUploadForm').submit(function() {
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');
    });

    // Auto-fill document name from file name
    $('#file').change(function() {
        const file = this.files[0];
        if (file && !$('#name').val()) {
            const fileName = file.name.replace(/\.[^/.]+$/, ""); // Remove extension
            const cleanName = fileName.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            $('#name').val(cleanName);
        }
    });
});
</script>
@endpush

@push('styles')
<style>
.file-upload-area {
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-upload-area:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa;
}

.file-upload-area.border-primary {
    border-color: #007bff !important;
}

#filePreview {
    width: 100%;
}

.file-icon {
    flex-shrink: 0;
}

.alert {
    border-radius: 0.5rem;
}

.form-check-label {
    font-weight: 500;
}

.card {
    border-radius: 0.5rem;
    border: none;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 0.5rem 0.5rem 0 0 !important;
}
</style>
@endpush