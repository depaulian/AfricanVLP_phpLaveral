@extends('layouts.app')

@section('title', 'Document Management')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Document Management</h1>
                    <p class="text-gray-600 mt-1">Upload and manage your verification documents</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="openUploadModal()" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Upload Document
                    </button>
                    <a href="{{ route('profile.index') }}" 
                       class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        Back to Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Document Categories -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Categories Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg sticky top-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Document Types</h3>
                    </div>
                    <div class="p-4">
                        <div class="space-y-2">
                            <button onclick="filterDocuments('all')" 
                                    class="w-full text-left px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors document-filter active"
                                    data-category="all">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">All Documents</span>
                                    <span class="text-sm text-gray-500">{{ $documents->count() }}</span>
                                </div>
                            </button>
                            
                            @foreach($documentTypes as $type)
                                <button onclick="filterDocuments('{{ $type->slug }}')" 
                                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors document-filter"
                                        data-category="{{ $type->slug }}">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-700">{{ $type->name }}</span>
                                        <span class="text-sm text-gray-500">{{ $type->documents_count }}</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Documents Grid -->
            <div class="lg:col-span-3">
                <!-- Upload Progress (if any) -->
                <div id="uploadProgress" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center">
                        <svg class="animate-spin h-5 w-5 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-blue-800">Uploading document...</p>
                            <div class="w-full bg-blue-200 rounded-full h-2 mt-2">
                                <div id="progressBar" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($documents->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="documentsGrid">
                        @foreach($documents as $document)
                            <div class="document-card bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition-shadow" 
                                 data-category="{{ $document->type->slug }}">
                                <!-- Document Preview -->
                                <div class="aspect-w-16 aspect-h-9 bg-gray-100">
                                    @if($document->isImage())
                                        <img src="{{ $document->thumbnail_url }}" 
                                             alt="{{ $document->name }}" 
                                             class="w-full h-48 object-cover">
                                    @else
                                        <div class="flex items-center justify-center h-48">
                                            <div class="text-center">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <p class="mt-2 text-sm text-gray-500">{{ strtoupper($document->file_extension) }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Document Info -->
                                <div class="p-4">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 truncate">{{ $document->name }}</h4>
                                            <p class="text-sm text-gray-500">{{ $document->type->name }}</p>
                                        </div>
                                        
                                        <!-- Verification Status -->
                                        <div class="ml-2">
                                            @if($document->verification_status === 'verified')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Verified
                                                </span>
                                            @elseif($document->verification_status === 'pending')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Pending
                                                </span>
                                            @elseif($document->verification_status === 'rejected')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Rejected
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Not Reviewed
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Document Details -->
                                    <div class="mt-3 space-y-2">
                                        <div class="flex items-center text-sm text-gray-500">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2"></path>
                                            </svg>
                                            <span>{{ $document->file_size_human }}</span>
                                        </div>
                                        
                                        <div class="flex items-center text-sm text-gray-500">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>{{ $document->created_at->format('M j, Y') }}</span>
                                        </div>

                                        @if($document->expiry_date)
                                            <div class="flex items-center text-sm {{ $document->isExpiringSoon() ? 'text-red-500' : 'text-gray-500' }}">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                </svg>
                                                <span>Expires {{ $document->expiry_date->format('M j, Y') }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Rejection Reason -->
                                    @if($document->verification_status === 'rejected' && $document->rejection_reason)
                                        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <p class="text-sm text-red-800">
                                                <span class="font-medium">Rejection Reason:</span>
                                                {{ $document->rejection_reason }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Actions -->
                                    <div class="mt-4 flex items-center justify-between">
                                        <div class="flex space-x-2">
                                            <a href="{{ $document->download_url }}" 
                                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                Download
                                            </a>
                                            <button onclick="viewDocument({{ $document->id }})" 
                                                    class="text-green-600 hover:text-green-800 text-sm font-medium">
                                                View
                                            </button>
                                        </div>
                                        
                                        <div class="flex space-x-1">
                                            <button onclick="editDocument({{ $document->id }})" 
                                                    class="text-gray-400 hover:text-gray-600 p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            
                                            <form action="{{ route('profile.documents.destroy', $document) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        onclick="return confirm('Are you sure you want to delete this document?')"
                                                        class="text-red-400 hover:text-red-600 p-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($documents->hasPages())
                        <div class="mt-8">
                            {{ $documents->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No documents uploaded</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by uploading your first document.</p>
                        <div class="mt-6">
                            <button onclick="openUploadModal()" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Upload Your First Document
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div id="uploadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">Upload Document</h3>
            <button onclick="closeUploadModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="uploadForm" method="POST" enctype="multipart/form-data" action="{{ route('profile.documents.store') }}" class="space-y-6">
            @csrf
            
            <!-- File Upload Area -->
            <div class="border-2 border-dashed border-gray-300 rounded-lg p-6" id="dropZone">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                    <div class="mt-4">
                        <label for="file-upload" class="cursor-pointer">
                            <span class="mt-2 block text-sm font-medium text-gray-900">
                                Drop files here or click to upload
                            </span>
                            <input id="file-upload" name="file" type="file" class="sr-only" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                        </label>
                        <p class="mt-2 text-xs text-gray-500">
                            PDF, DOC, DOCX, JPG, PNG up to 10MB
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Document Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="document_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Document Type *
                    </label>
                    <select id="document_type_id" name="document_type_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Document Type</option>
                        @foreach($documentTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Document Name *
                    </label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., Driver's License">
                </div>
            </div>
            
            <!-- Optional Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Issue Date
                    </label>
                    <input type="date" id="issue_date" name="issue_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Expiry Date
                    </label>
                    <input type="date" id="expiry_date" name="expiry_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea id="description" name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Additional information about this document..."></textarea>
            </div>
            
            <!-- Privacy Settings -->
            <div class="border-t border-gray-200 pt-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Privacy Settings</h4>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="radio" name="visibility" value="private" checked
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">Private - Only visible to you and administrators</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="visibility" value="organization"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">Organization - Visible to organizations you apply to</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="visibility" value="public"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <span class="ml-2 text-sm text-gray-700">Public - Visible on your public profile</span>
                    </label>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeUploadModal()"
                        class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Upload Document
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Document Viewer Modal -->
<div id="viewerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-screen overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900" id="viewerTitle">Document Viewer</h3>
            <button onclick="closeViewerModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="p-6 max-h-96 overflow-auto" id="viewerContent">
            <!-- Document content will be loaded here -->
        </div>
    </div>
</div>

@push('scripts')
<script>
// Modal management
function openUploadModal() {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    document.getElementById('uploadModal').classList.remove('flex');
    document.getElementById('uploadForm').reset();
}

function closeViewerModal() {
    document.getElementById('viewerModal').classList.add('hidden');
    document.getElementById('viewerModal').classList.remove('flex');
}

// Document filtering
function filterDocuments(category) {
    const documentCards = document.querySelectorAll('.document-card');
    const filterButtons = document.querySelectorAll('.document-filter');
    
    // Update active filter
    filterButtons.forEach(btn => {
        btn.classList.remove('active', 'bg-blue-100', 'text-blue-800');
        if (btn.dataset.category === category) {
            btn.classList.add('active', 'bg-blue-100', 'text-blue-800');
        }
    });
    
    // Filter documents
    documentCards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Document viewer
function viewDocument(documentId) {
    fetch(`/profile/documents/${documentId}/view`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('viewerTitle').textContent = data.name;
            
            if (data.is_image) {
                document.getElementById('viewerContent').innerHTML = `
                    <img src="${data.url}" alt="${data.name}" class="max-w-full h-auto">
                `;
            } else {
                document.getElementById('viewerContent').innerHTML = `
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">${data.name}</h3>
                        <p class="mt-1 text-sm text-gray-500">This document type cannot be previewed.</p>
                        <div class="mt-6">
                            <a href="${data.download_url}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Download Document
                            </a>
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('viewerModal').classList.remove('hidden');
            document.getElementById('viewerModal').classList.add('flex');
        })
        .catch(error => {
            console.error('Error loading document:', error);
            alert('Error loading document');
        });
}

// Edit document (placeholder)
function editDocument(documentId) {
    // This would open an edit modal similar to upload
    alert('Edit functionality would be implemented here');
}

// Drag and drop functionality
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('file-upload');

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        updateFileDisplay(files[0]);
    }
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        updateFileDisplay(e.target.files[0]);
    }
});

function updateFileDisplay(file) {
    const fileName = document.querySelector('#dropZone .text-gray-900');
    if (fileName) {
        fileName.textContent = `Selected: ${file.name}`;
    }
}

// Form submission with progress
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const progressContainer = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    
    // Show progress
    progressContainer.classList.remove('hidden');
    closeUploadModal();
    
    // Upload with progress tracking
    const xhr = new XMLHttpRequest();
    
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            progressBar.style.width = percentComplete + '%';
        }
    });
    
    xhr.addEventListener('load', function() {
        progressContainer.classList.add('hidden');
        if (xhr.status === 200) {
            location.reload(); // Reload to show new document
        } else {
            alert('Upload failed. Please try again.');
        }
    });
    
    xhr.addEventListener('error', function() {
        progressContainer.classList.add('hidden');
        alert('Upload failed. Please try again.');
    });
    
    xhr.open('POST', this.action);
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    xhr.send(formData);
});
</script>
@endpush
@endsection@extends('layouts.app')

@section('title', 'Document Management')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Document Management</h1>
                    <p class="text-gray-600 mt-1">Upload and manage your verification documents</p>
                </div>
                <a href="{{ route('profile.index') }}" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    Back to Profile
                </a>
            </div>
        </div>

        <!-- Upload Area -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Upload New Document</h3>
                <p class="text-sm text-gray-600 mt-1">Drag and drop files or click to browse</p>
            </div>
            <div class="p-6">
                <div id="dropZone" class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors">
                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="mt-4">
                        <p class="text-lg text-gray-600">Drop files here or <button type="button" class="text-blue-600 hover:text-blue-500">browse</button></p>
                        <p class="text-sm text-gray-500 mt-2">Supports: PDF, DOC, DOCX, JPG, PNG (Max 10MB)</p>
                    </div>
                    <input type="file" id="fileInput" class="hidden" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                
                <!-- Upload Form -->
                <form id="uploadForm" class="mt-6 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="document_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Document Type *
                            </label>
                            <select id="document_type" name="document_type" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Type</option>
                                <option value="id_document">ID Document</option>
                                <option value="resume">Resume/CV</option>
                                <option value="certificate">Certificate</option>
                                <option value="reference_letter">Reference Letter</option>
                                <option value="background_check">Background Check</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="document_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Document Name
                            </label>
                            <input type="text" id="document_name" name="document_name"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter document name">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description (Optional)
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Add any additional notes about this document..."></textarea>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="cancelUpload()" 
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Upload Document
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Documents List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Your Documents</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $documents->count() }} {{ Str::plural('document', $documents->count()) }} uploaded</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Filter -->
                        <select id="typeFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Types</option>
                            <option value="id_document">ID Documents</option>
                            <option value="resume">Resumes</option>
                            <option value="certificate">Certificates</option>
                            <option value="reference_letter">Reference Letters</option>
                            <option value="background_check">Background Checks</option>
                            <option value="other">Other</option>
                        </select>
                        
                        <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="verified">Verified</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                @if($documents->count() > 0)
                    <div class="grid grid-cols-1 gap-4" id="documentsGrid">
                        @foreach($documents as $document)
                            <div class="document-item border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors"
                                 data-type="{{ $document->document_type }}" data-status="{{ $document->verification_status }}">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-4 flex-1">
                                        <!-- File Icon -->
                                        <div class="flex-shrink-0">
                                            @php
                                                $extension = pathinfo($document->file_path, PATHINFO_EXTENSION);
                                                $iconClass = match(strtolower($extension)) {
                                                    'pdf' => 'text-red-600',
                                                    'doc', 'docx' => 'text-blue-600',
                                                    'jpg', 'jpeg', 'png' => 'text-green-600',
                                                    default => 'text-gray-600'
                                                };
                                            @endphp
                                            <div class="w-12 h-12 {{ $iconClass }} bg-gray-100 rounded-lg flex items-center justify-center">
                                                @if(in_array(strtolower($extension), ['jpg', 'jpeg', 'png']))
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Document Info -->
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <h4 class="text-lg font-medium text-gray-900">
                                                    {{ $document->document_name ?: $document->original_filename }}
                                                </h4>
                                                
                                                <!-- Document Type Badge -->
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ ucwords(str_replace('_', ' ', $document->document_type)) }}
                                                </span>
                                                
                                                <!-- Verification Status -->
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    {{ $document->verification_status === 'verified' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $document->verification_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $document->verification_status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                                    @if($document->verification_status === 'verified')
                                                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    @endif
                                                    {{ ucfirst($document->verification_status) }}
                                                </span>
                                            </div>
                                            
                                            <!-- File Details -->
                                            <div class="mt-2 text-sm text-gray-600">
                                                <div class="flex items-center space-x-4">
                                                    <span>{{ strtoupper($extension) }}</span>
                                                    <span>•</span>
                                                    <span>{{ number_format($document->file_size / 1024, 1) }} KB</span>
                                                    <span>•</span>
                                                    <span>Uploaded {{ $document->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                            
                                            <!-- Description -->
                                            @if($document->description)
                                                <p class="mt-2 text-sm text-gray-700">{{ $document->description }}</p>
                                            @endif
                                            
                                            <!-- Verification Notes -->
                                            @if($document->verification_notes)
                                                <div class="mt-3 p-3 bg-gray-50 rounded border">
                                                    <p class="text-sm font-medium text-gray-700">Verification Notes:</p>
                                                    <p class="text-sm text-gray-600 mt-1">{{ $document->verification_notes }}</p>
                                                    @if($document->verified_by)
                                                        <p class="text-xs text-gray-500 mt-2">
                                                            Verified by {{ $document->verifier->name }} on {{ $document->verified_at->format('M d, Y') }}
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        <a href="{{ route('profile.documents.download', $document) }}" 
                                           class="text-blue-600 hover:text-blue-800 p-2" title="Download">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-4-4m4 4l4-4m-6 4h8a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </a>
                                        
                                        <a href="{{ route('profile.documents.view', $document) }}" target="_blank"
                                           class="text-green-600 hover:text-green-800 p-2" title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                        
                                        <button onclick="editDocument({{ $document->id }})" 
                                                class="text-yellow-600 hover:text-yellow-800 p-2" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        
                                        <form action="{{ route('profile.documents.destroy', $document) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    onclick="return confirm('Are you sure you want to delete this document?')"
                                                    class="text-red-600 hover:text-red-800 p-2" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Pagination -->
                    @if($documents->hasPages())
                        <div class="mt-6">
                            {{ $documents->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No documents uploaded yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by uploading your first document above.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// File upload handling
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const uploadForm = document.getElementById('uploadForm');
let selectedFiles = [];

// Click to browse
dropZone.addEventListener('click', () => fileInput.click());

// Drag and drop
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-blue-400', 'bg-blue-50');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-blue-400', 'bg-blue-50');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-blue-400', 'bg-blue-50');
    handleFiles(e.dataTransfer.files);
});

fileInput.addEventListener('change', (e) => {
    handleFiles(e.target.files);
});

function handleFiles(files) {
    selectedFiles = Array.from(files);
    if (selectedFiles.length > 0) {
        showUploadForm();
    }
}

function showUploadForm() {
    uploadForm.classList.remove('hidden');
    // Auto-fill document name if only one file
    if (selectedFiles.length === 1) {
        const fileName = selectedFiles[0].name.replace(/\.[^/.]+$/, "");
        document.getElementById('document_name').value = fileName;
    }
}

function cancelUpload() {
    uploadForm.classList.add('hidden');
    uploadForm.reset();
    fileInput.value = '';
    selectedFiles = [];
}

// Form submission
uploadForm.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const formData = new FormData();
    selectedFiles.forEach(file => formData.append('files[]', file));
    formData.append('document_type', document.getElementById('document_type').value);
    formData.append('document_name', document.getElementById('document_name').value);
    formData.append('description', document.getElementById('description').value);
    formData.append('_token', '{{ csrf_token() }}');
    
    // Show loading state
    const submitBtn = uploadForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Uploading...';
    submitBtn.disabled = true;
    
    fetch('{{ route("profile.documents.store") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Upload failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        alert('Upload failed. Please try again.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// Filtering
document.getElementById('typeFilter').addEventListener('change', filterDocuments);
document.getElementById('statusFilter').addEventListener('change', filterDocuments);

function filterDocuments() {
    const typeFilter = document.getElementById('typeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const documentItems = document.querySelectorAll('.document-item');
    
    documentItems.forEach(item => {
        let showItem = true;
        
        if (typeFilter && item.dataset.type !== typeFilter) {
            showItem = false;
        }
        
        if (statusFilter && item.dataset.status !== statusFilter) {
            showItem = false;
        }
        
        item.style.display = showItem ? 'block' : 'none';
    });
}

// Edit document (placeholder - would open a modal)
function editDocument(documentId) {
    // This would typically open a modal to edit document details
    alert('Edit functionality would be implemented here');
}
</script>
@endpush
@endsection