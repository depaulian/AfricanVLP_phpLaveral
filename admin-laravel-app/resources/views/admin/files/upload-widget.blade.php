{{-- File Upload Widget Component --}}
<div class="file-upload-widget" data-folder="{{ $folder ?? 'resources' }}" data-resource-id="{{ $resourceId ?? '' }}">
    <div class="upload-area" id="upload-area-{{ $uploadId ?? 'default' }}">
        <div class="upload-dropzone" ondrop="dropHandler(event, '{{ $uploadId ?? 'default' }}');" ondragover="dragOverHandler(event);" ondragenter="dragEnterHandler(event);" ondragleave="dragLeaveHandler(event);">
            <div class="upload-icon">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
            </div>
            <div class="upload-text">
                <p class="text-lg font-medium text-gray-700">Drop files here or click to browse</p>
                <p class="text-sm text-gray-500">Supports images, documents, videos, and audio files</p>
                <p class="text-xs text-gray-400 mt-2">Maximum file size: {{ config('cloudinary.max_file_size') / 1024 / 1024 }}MB</p>
            </div>
            <input type="file" id="file-input-{{ $uploadId ?? 'default' }}" class="hidden" {{ isset($multiple) && $multiple ? 'multiple' : '' }} accept="{{ $accept ?? '*/*' }}">
        </div>
    </div>
    
    <div class="upload-progress hidden" id="upload-progress-{{ $uploadId ?? 'default' }}">
        <div class="progress-bar">
            <div class="progress-fill" style="width: 0%"></div>
        </div>
        <div class="progress-text">
            <span class="progress-percentage">0%</span>
            <span class="progress-status">Uploading...</span>
        </div>
    </div>
    
    <div class="upload-results hidden" id="upload-results-{{ $uploadId ?? 'default' }}">
        <div class="uploaded-files"></div>
        <div class="upload-errors"></div>
    </div>
</div>

<style>
.file-upload-widget {
    @apply w-full;
}

.upload-dropzone {
    @apply border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer transition-colors duration-200;
}

.upload-dropzone:hover {
    @apply border-blue-400 bg-blue-50;
}

.upload-dropzone.dragover {
    @apply border-blue-500 bg-blue-100;
}

.upload-icon {
    @apply flex justify-center mb-4;
}

.progress-bar {
    @apply w-full bg-gray-200 rounded-full h-2 mb-2;
}

.progress-fill {
    @apply bg-blue-600 h-2 rounded-full transition-all duration-300;
}

.progress-text {
    @apply flex justify-between text-sm text-gray-600;
}

.uploaded-files {
    @apply space-y-2;
}

.uploaded-file {
    @apply flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg;
}

.upload-errors {
    @apply space-y-2 mt-4;
}

.upload-error {
    @apply p-3 bg-red-50 border border-red-200 rounded-lg text-red-700;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadId = '{{ $uploadId ?? "default" }}';
    const uploadArea = document.getElementById(`upload-area-${uploadId}`);
    const fileInput = document.getElementById(`file-input-${uploadId}`);
    const dropzone = uploadArea.querySelector('.upload-dropzone');
    
    // Click to browse files
    dropzone.addEventListener('click', function() {
        fileInput.click();
    });
    
    // Handle file selection
    fileInput.addEventListener('change', function(e) {
        handleFiles(e.target.files, uploadId);
    });
});

function dragOverHandler(ev) {
    ev.preventDefault();
}

function dragEnterHandler(ev) {
    ev.preventDefault();
    ev.target.closest('.upload-dropzone').classList.add('dragover');
}

function dragLeaveHandler(ev) {
    ev.preventDefault();
    ev.target.closest('.upload-dropzone').classList.remove('dragover');
}

function dropHandler(ev, uploadId) {
    ev.preventDefault();
    ev.target.closest('.upload-dropzone').classList.remove('dragover');
    
    const files = ev.dataTransfer.files;
    handleFiles(files, uploadId);
}

function handleFiles(files, uploadId) {
    if (files.length === 0) return;
    
    const widget = document.querySelector(`#upload-area-${uploadId}`).closest('.file-upload-widget');
    const folder = widget.dataset.folder;
    const resourceId = widget.dataset.resourceId;
    
    const formData = new FormData();
    
    if (files.length === 1) {
        formData.append('file', files[0]);
        uploadSingleFile(formData, folder, resourceId, uploadId);
    } else {
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        uploadMultipleFiles(formData, folder, resourceId, uploadId);
    }
}

function uploadSingleFile(formData, folder, resourceId, uploadId) {
    formData.append('folder', folder);
    if (resourceId) {
        formData.append('resource_id', resourceId);
    }
    
    showProgress(uploadId);
    
    fetch('{{ route("admin.files.upload.single") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        hideProgress(uploadId);
        if (data.success) {
            showUploadResult(uploadId, [data.data], []);
        } else {
            showUploadResult(uploadId, [], [data.message]);
        }
    })
    .catch(error => {
        hideProgress(uploadId);
        showUploadResult(uploadId, [], ['Upload failed: ' + error.message]);
    });
}

function uploadMultipleFiles(formData, folder, resourceId, uploadId) {
    formData.append('folder', folder);
    if (resourceId) {
        formData.append('resource_id', resourceId);
    }
    
    showProgress(uploadId);
    
    fetch('{{ route("admin.files.upload.multiple") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        hideProgress(uploadId);
        if (data.success) {
            showUploadResult(uploadId, data.data.uploaded, data.data.errors);
        } else {
            showUploadResult(uploadId, [], [data.message]);
        }
    })
    .catch(error => {
        hideProgress(uploadId);
        showUploadResult(uploadId, [], ['Upload failed: ' + error.message]);
    });
}

function showProgress(uploadId) {
    document.getElementById(`upload-area-${uploadId}`).classList.add('hidden');
    document.getElementById(`upload-progress-${uploadId}`).classList.remove('hidden');
}

function hideProgress(uploadId) {
    document.getElementById(`upload-progress-${uploadId}`).classList.add('hidden');
    document.getElementById(`upload-results-${uploadId}`).classList.remove('hidden');
}

function showUploadResult(uploadId, uploaded, errors) {
    const resultsContainer = document.getElementById(`upload-results-${uploadId}`);
    const uploadedContainer = resultsContainer.querySelector('.uploaded-files');
    const errorsContainer = resultsContainer.querySelector('.upload-errors');
    
    // Clear previous results
    uploadedContainer.innerHTML = '';
    errorsContainer.innerHTML = '';
    
    // Show uploaded files
    uploaded.forEach(file => {
        const fileDiv = document.createElement('div');
        fileDiv.className = 'uploaded-file';
        fileDiv.innerHTML = `
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="font-medium">${file.original_filename}</span>
            </div>
            <span class="text-sm text-gray-500">${formatFileSize(file.bytes)}</span>
        `;
        uploadedContainer.appendChild(fileDiv);
    });
    
    // Show errors
    errors.forEach(error => {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'upload-error';
        errorDiv.textContent = typeof error === 'string' ? error : error.error;
        errorsContainer.appendChild(errorDiv);
    });
    
    // Reset upload area after 3 seconds
    setTimeout(() => {
        document.getElementById(`upload-area-${uploadId}`).classList.remove('hidden');
        document.getElementById(`upload-results-${uploadId}`).classList.add('hidden');
    }, 3000);
}

function formatFileSize(bytes) {
    if (bytes >= 1073741824) {
        return (bytes / 1073741824).toFixed(2) + ' GB';
    } else if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    } else {
        return bytes + ' bytes';
    }
}
</script>