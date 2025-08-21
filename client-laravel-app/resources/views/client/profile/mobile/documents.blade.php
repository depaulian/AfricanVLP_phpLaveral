@extends('layouts.mobile')

@section('title', 'My Documents')

@section('content')
<div class="mobile-documents">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('profile.mobile.dashboard') }}" class="text-blue-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="text-lg font-semibold">My Documents</h1>
            <button onclick="openUploadModal()" class="text-blue-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Upload Progress -->
    <div id="uploadProgress" class="hidden bg-blue-50 border-b border-blue-200 p-4">
        <div class="flex items-center space-x-3">
            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500"></div>
            <div class="flex-1">
                <div class="text-sm font-medium text-blue-800">Uploading document...</div>
                <div class="w-full bg-blue-200 rounded-full h-2 mt-1">
                    <div id="progressBar" class="bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Document Categories -->
    <div class="bg-white border-b border-gray-200">
        <div class="flex overflow-x-auto p-4 space-x-3">
            <button onclick="filterDocuments('all')" 
                    class="filter-btn active flex-shrink-0 px-4 py-2 bg-blue-500 text-white rounded-full text-sm font-medium">
                All
            </button>
            <button onclick="filterDocuments('resume')" 
                    class="filter-btn flex-shrink-0 px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-medium">
                Resume
            </button>
            <button onclick="filterDocuments('certificate')" 
                    class="filter-btn flex-shrink-0 px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-medium">
                Certificates
            </button>
            <button onclick="filterDocuments('id')" 
                    class="filter-btn flex-shrink-0 px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-medium">
                ID Documents
            </button>
            <button onclick="filterDocuments('other')" 
                    class="filter-btn flex-shrink-0 px-4 py-2 bg-gray-200 text-gray-700 rounded-full text-sm font-medium">
                Other
            </button>
        </div>
    </div>

    <!-- Documents List -->
    <div class="p-4 space-y-4">
        @forelse($user->documents()->latest()->get() as $document)
        <div class="document-item bg-white rounded-lg border border-gray-200 p-4" data-type="{{ $document->document_type }}">
            <div class="flex items-start space-x-3">
                <!-- Document Icon -->
                <div class="flex-shrink-0">
                    @if(in_array($document->mime_type, ['image/jpeg', 'image/png', 'image/gif']))
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @elseif($document->mime_type === 'application/pdf')
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @else
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    @endif
                </div>

                <!-- Document Info -->
                <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-gray-900 truncate">{{ $document->file_name }}</h3>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $document->document_type === 'resume' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $document->document_type === 'certificate' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $document->document_type === 'id' ? 'bg-purple-100 text-purple-800' : '' }}
                            {{ $document->document_type === 'other' ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ ucfirst($document->document_type) }}
                        </span>
                        <span class="text-xs text-gray-500">{{ $document->file_size_human }}</span>
                    </div>
                    <div class="flex items-center mt-2">
                        @if($document->verification_status === 'verified')
                            <span class="inline-flex items-center text-xs text-green-600">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Verified
                            </span>
                        @elseif($document->verification_status === 'pending')
                            <span class="inline-flex items-center text-xs text-yellow-600">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                Pending Review
                            </span>
                        @else
                            <span class="inline-flex items-center text-xs text-red-600">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                Rejected
                            </span>
                        @endif
                        <span class="text-xs text-gray-400 ml-2">{{ $document->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex-shrink-0">
                    <button onclick="showDocumentActions({{ $document->id }})" class="text-gray-400">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                        </svg>
                    </button>
                </div>
            </div>

            @if($document->verification_status === 'rejected' && $document->rejection_reason)
            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-700">
                    <strong>Rejection Reason:</strong> {{ $document->rejection_reason }}
                </p>
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No documents yet</h3>
            <p class="text-gray-500 mb-4">Upload your documents to verify your profile</p>
            <button onclick="openUploadModal()" 
                    class="bg-blue-500 text-white px-6 py-2 rounded-lg font-medium">
                Upload Document
            </button>
        </div>
        @endforelse
    </div>
</div>

<!-- Upload Modal -->
<div id="uploadModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-end justify-center min-h-screen">
        <div class="bg-white rounded-t-lg w-full max-w-md">
            <div class="p-4 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Upload Document</h3>
                    <button onclick="closeUploadModal()" class="text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <form id="uploadForm" class="p-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                    <select name="document_type" required 
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select document type</option>
                        <option value="resume">Resume/CV</option>
                        <option value="certificate">Certificate</option>
                        <option value="id">ID Document</option>
                        <option value="transcript">Transcript</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="space-y-3">
                    <button type="button" onclick="captureDocument()" 
                            class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Take Photo</span>
                    </button>
                    
                    <button type="button" onclick="selectFromFiles()" 
                            class="w-full bg-gray-500 text-white py-3 rounded-lg font-medium flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <span>Choose File</span>
                    </button>
                </div>

                <div id="selectedFile" class="hidden">
                    <div class="border border-gray-200 rounded-lg p-3">
                        <div class="flex items-center space-x-3">
                            <div id="fileIcon" class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div id="fileName" class="font-medium text-sm"></div>
                                <div id="fileSize" class="text-xs text-gray-500"></div>
                            </div>
                            <button type="button" onclick="clearSelectedFile()" class="text-red-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" id="uploadBtn" disabled
                        class="w-full bg-green-500 text-white py-3 rounded-lg font-medium disabled:bg-gray-300 disabled:cursor-not-allowed">
                    Upload Document
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Document Actions Modal -->
<div id="actionsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-end justify-center min-h-screen">
        <div class="bg-white rounded-t-lg w-full max-w-md">
            <div class="p-4 space-y-3">
                <button onclick="viewDocument()" 
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span>View Document</span>
                    </div>
                </button>
                
                <button onclick="downloadDocument()" 
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Download</span>
                    </div>
                </button>
                
                <button onclick="deleteDocument()" 
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 rounded-lg text-red-600">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>Delete</span>
                    </div>
                </button>
                
                <button onclick="closeActionsModal()" 
                        class="w-full text-left px-4 py-3 hover:bg-gray-50 rounded-lg text-gray-600">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<input type="file" id="cameraInput" accept="image/*" capture="camera" class="hidden">
<input type="file" id="fileInput" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" class="hidden">
@endsection

@push('scripts')
<script>
let selectedFile = null;
let currentDocumentId = null;

// Modal functions
function openUploadModal() {
    document.getElementById('uploadModal').classList.remove('hidden');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    clearSelectedFile();
}

function showDocumentActions(documentId) {
    currentDocumentId = documentId;
    document.getElementById('actionsModal').classList.remove('hidden');
}

function closeActionsModal() {
    document.getElementById('actionsModal').classList.add('hidden');
    currentDocumentId = null;
}

// File selection functions
function captureDocument() {
    const documentType = document.querySelector('select[name="document_type"]').value;
    if (!documentType) {
        alert('Please select a document type first');
        return;
    }
    document.getElementById('cameraInput').click();
}

function selectFromFiles() {
    const documentType = document.querySelector('select[name="document_type"]').value;
    if (!documentType) {
        alert('Please select a document type first');
        return;
    }
    document.getElementById('fileInput').click();
}

// File input handlers
document.getElementById('cameraInput').addEventListener('change', handleFileSelection);
document.getElementById('fileInput').addEventListener('change', handleFileSelection);

function handleFileSelection(event) {
    const file = event.target.files[0];
    if (!file) return;

    selectedFile = file;
    displaySelectedFile(file);
    document.getElementById('uploadBtn').disabled = false;
}

function displaySelectedFile(file) {
    const selectedFileDiv = document.getElementById('selectedFile');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const fileIcon = document.getElementById('fileIcon');

    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);

    // Update icon based on file type
    if (file.type.startsWith('image/')) {
        fileIcon.innerHTML = `
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        `;
    } else if (file.type === 'application/pdf') {
        fileIcon.innerHTML = `
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
        `;
    }

    selectedFileDiv.classList.remove('hidden');
}

function clearSelectedFile() {
    selectedFile = null;
    document.getElementById('selectedFile').classList.add('hidden');
    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('cameraInput').value = '';
    document.getElementById('fileInput').value = '';
}

// Upload form handler
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedFile) {
        alert('Please select a file to upload');
        return;
    }

    const documentType = document.querySelector('select[name="document_type"]').value;
    if (!documentType) {
        alert('Please select a document type');
        return;
    }

    const formData = new FormData();
    formData.append('document', selectedFile);
    formData.append('document_type', documentType);
    formData.append('_token', '{{ csrf_token() }}');

    uploadDocument(formData);
});

function uploadDocument(formData) {
    // Show progress
    document.getElementById('uploadProgress').classList.remove('hidden');
    closeUploadModal();

    // Simulate progress (in real implementation, use XMLHttpRequest for progress tracking)
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 30;
        if (progress > 90) progress = 90;
        document.getElementById('progressBar').style.width = progress + '%';
    }, 200);

    fetch('{{ route("profile.mobile.documents.upload") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(progressInterval);
        document.getElementById('progressBar').style.width = '100%';
        
        setTimeout(() => {
            document.getElementById('uploadProgress').classList.add('hidden');
            
            if (data.success) {
                showToast('Document uploaded successfully!', 'success');
                // Reload page to show new document
                window.location.reload();
            } else {
                showToast(data.message || 'Failed to upload document', 'error');
            }
        }, 500);
    })
    .catch(error => {
        clearInterval(progressInterval);
        document.getElementById('uploadProgress').classList.add('hidden');
        showToast('An error occurred while uploading', 'error');
    });
}

// Document filtering
function filterDocuments(type) {
    const documents = document.querySelectorAll('.document-item');
    const buttons = document.querySelectorAll('.filter-btn');

    // Update button states
    buttons.forEach(btn => {
        btn.classList.remove('active', 'bg-blue-500', 'text-white');
        btn.classList.add('bg-gray-200', 'text-gray-700');
    });
    
    event.target.classList.add('active', 'bg-blue-500', 'text-white');
    event.target.classList.remove('bg-gray-200', 'text-gray-700');

    // Filter documents
    documents.forEach(doc => {
        if (type === 'all' || doc.dataset.type === type) {
            doc.style.display = 'block';
        } else {
            doc.style.display = 'none';
        }
    });
}

// Document actions
function viewDocument() {
    if (currentDocumentId) {
        window.open(`{{ route('profile.documents.view', '') }}/${currentDocumentId}`, '_blank');
    }
    closeActionsModal();
}

function downloadDocument() {
    if (currentDocumentId) {
        window.location.href = `{{ route('profile.documents.download', '') }}/${currentDocumentId}`;
    }
    closeActionsModal();
}

function deleteDocument() {
    if (!currentDocumentId) return;
    
    if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
        fetch(`{{ route('profile.documents.destroy', '') }}/${currentDocumentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Document deleted successfully', 'success');
                // Remove document from DOM
                document.querySelector(`[data-document-id="${currentDocumentId}"]`)?.remove();
            } else {
                showToast('Failed to delete document', 'error');
            }
        })
        .catch(error => {
            showToast('An error occurred', 'error');
        });
    }
    
    closeActionsModal();
}

// Utility functions
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 left-4 right-4 p-3 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}
</script>
@endpush