@extends('layouts.client')

@section('title', 'Upload Files')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center mb-6">
            <a href="{{ route('files.index') }}" class="text-blue-600 hover:text-blue-800 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Upload Files</h1>
        </div>

        <div class="bg-white rounded-lg shadow-lg">
            <div class="p-6">
                <form method="POST" action="{{ route('files.store') }}" enctype="multipart/form-data" id="upload-form">
                    @csrf
                    
                    <!-- File Upload Area -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Files</label>
                        <div class="file-upload-area border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 hover:bg-blue-50 transition-colors cursor-pointer" 
                             id="upload-area">
                            <div class="upload-icon mb-4">
                                <svg class="w-16 h-16 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <div class="upload-text">
                                <p class="text-xl font-medium text-gray-700 mb-2">Drop files here or click to browse</p>
                                <p class="text-sm text-gray-500 mb-2">You can upload multiple files at once</p>
                                <p class="text-xs text-gray-400">Maximum file size: {{ config('cloudinary.max_file_size') / 1024 / 1024 }}MB per file</p>
                            </div>
                            <input type="file" name="files[]" id="file-input" class="hidden" multiple 
                                   accept="image/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,video/*,audio/*">
                        </div>
                        
                        <!-- Selected Files Preview -->
                        <div id="selected-files" class="mt-4 hidden">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Selected Files:</h4>
                            <div id="files-list" class="space-y-2"></div>
                        </div>
                        
                        @error('files')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('files.*')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- File Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category (Optional)</label>
                            <input type="text" name="category" id="category" value="{{ old('category') }}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g., Documents, Photos, Projects">
                            @error('category')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_public" value="1" {{ old('is_public', true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Make files public</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">Public files can be viewed by other users</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                        <textarea name="description" id="description" rows="3" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Add a description for these files...">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Upload Progress -->
                    <div id="upload-progress" class="mb-6 hidden">
                        <div class="bg-gray-200 rounded-full h-2 mb-2">
                            <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span id="progress-text">Uploading...</span>
                            <span id="progress-percentage">0%</span>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('files.index') }}" 
                           class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" id="upload-btn" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="upload-btn-text">Upload Files</span>
                            <svg id="upload-spinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');
    const selectedFiles = document.getElementById('selected-files');
    const filesList = document.getElementById('files-list');
    const uploadForm = document.getElementById('upload-form');
    const uploadBtn = document.getElementById('upload-btn');
    const uploadBtnText = document.getElementById('upload-btn-text');
    const uploadSpinner = document.getElementById('upload-spinner');
    const uploadProgress = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const progressPercentage = document.getElementById('progress-percentage');

    // Click to browse files
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });

    // Drag and drop functionality
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('border-blue-500', 'bg-blue-100');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-500', 'bg-blue-100');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('border-blue-500', 'bg-blue-100');
        
        const files = e.dataTransfer.files;
        fileInput.files = files;
        displaySelectedFiles(files);
    });

    // Handle file selection
    fileInput.addEventListener('change', function(e) {
        displaySelectedFiles(e.target.files);
    });

    function displaySelectedFiles(files) {
        if (files.length === 0) {
            selectedFiles.classList.add('hidden');
            return;
        }

        selectedFiles.classList.remove('hidden');
        filesList.innerHTML = '';

        Array.from(files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
            
            const fileInfo = document.createElement('div');
            fileInfo.className = 'flex items-center';
            
            const fileIcon = getFileIcon(file.type);
            const fileName = document.createElement('span');
            fileName.className = 'font-medium text-gray-900 ml-3';
            fileName.textContent = file.name;
            
            const fileSize = document.createElement('span');
            fileSize.className = 'text-sm text-gray-500 ml-2';
            fileSize.textContent = `(${formatFileSize(file.size)})`;
            
            fileInfo.appendChild(fileIcon);
            fileInfo.appendChild(fileName);
            fileInfo.appendChild(fileSize);
            
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'text-red-500 hover:text-red-700';
            removeBtn.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            `;
            removeBtn.addEventListener('click', function() {
                removeFile(index);
            });
            
            fileItem.appendChild(fileInfo);
            fileItem.appendChild(removeBtn);
            filesList.appendChild(fileItem);
        });
    }

    function getFileIcon(mimeType) {
        const icon = document.createElement('div');
        icon.className = 'w-8 h-8 flex items-center justify-center rounded';
        
        if (mimeType.startsWith('image/')) {
            icon.className += ' bg-green-100 text-green-600';
            icon.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            `;
        } else if (mimeType.startsWith('video/')) {
            icon.className += ' bg-purple-100 text-purple-600';
            icon.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            `;
        } else if (mimeType.startsWith('audio/')) {
            icon.className += ' bg-yellow-100 text-yellow-600';
            icon.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                </svg>
            `;
        } else {
            icon.className += ' bg-blue-100 text-blue-600';
            icon.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            `;
        }
        
        return icon;
    }

    function removeFile(index) {
        const dt = new DataTransfer();
        const files = Array.from(fileInput.files);
        
        files.splice(index, 1);
        
        files.forEach(file => {
            dt.items.add(file);
        });
        
        fileInput.files = dt.files;
        displaySelectedFiles(fileInput.files);
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

    // Handle form submission
    uploadForm.addEventListener('submit', function(e) {
        if (fileInput.files.length === 0) {
            e.preventDefault();
            alert('Please select at least one file to upload.');
            return;
        }

        // Show upload progress
        uploadBtn.disabled = true;
        uploadBtnText.textContent = 'Uploading...';
        uploadSpinner.classList.remove('hidden');
        uploadProgress.classList.remove('hidden');

        // Simulate progress (in real implementation, you'd track actual upload progress)
        let progress = 0;
        const progressInterval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            
            progressBar.style.width = progress + '%';
            progressPercentage.textContent = Math.round(progress) + '%';
            
            if (progress >= 90) {
                clearInterval(progressInterval);
                progressText.textContent = 'Processing files...';
            }
        }, 200);
    });
});
</script>
@endsection