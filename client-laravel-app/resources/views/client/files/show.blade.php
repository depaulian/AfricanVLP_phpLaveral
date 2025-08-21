@extends('layouts.client')

@section('title', 'File Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center mb-6">
            <a href="{{ route('files.index') }}" class="text-blue-600 hover:text-blue-800 mr-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">File Details</h1>
        </div>

        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- File Preview -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Preview</h2>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            @if($file->isImage())
                                <img src="{{ $file->getMediumUrl() ?: $file->getFileUrl() }}" 
                                     alt="{{ $file->original_filename }}" 
                                     class="w-full h-auto max-h-96 object-contain">
                            @else
                                <div class="h-64 flex items-center justify-center bg-gray-50">
                                    <div class="text-center">
                                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($file->isDocument())
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            @elseif($file->isVideo())
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            @elseif($file->isAudio())
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            @endif
                                        </svg>
                                        <p class="text-gray-500">{{ ucfirst($file->file_category) }} File</p>
                                        <p class="text-sm text-gray-400">{{ strtoupper($file->file_type) }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- File Information -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Information</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">File Name</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $file->original_filename }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">File Size</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $file->getHumanReadableSize() }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">File Type</label>
                                <p class="mt-1 text-sm text-gray-900">{{ strtoupper($file->file_type) }} ({{ $file->mime_type }})</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Category</label>
                                <p class="mt-1 text-sm text-gray-900">{{ ucfirst($file->file_category) }}</p>
                            </div>

                            @if($file->isImage() && $file->width && $file->height)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Dimensions</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $file->width }} × {{ $file->height }} pixels</p>
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Upload Date</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $file->created->format('M j, Y g:i A') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Downloads</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $file->download_count ?? 0 }} times</p>
                            </div>

                            @if($file->description)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $file->description }}</p>
                                </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('files.download', $file) }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download
                            </a>

                            <a href="{{ route('files.edit', $file) }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </a>

                            @if($file->isImage())
                                <button onclick="openImageModal()" 
                                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                    </svg>
                                    View Full Size
                                </button>
                            @endif

                            <form method="POST" action="{{ route('files.destroy', $file) }}" class="inline" 
                                  onsubmit="return confirm('Are you sure you want to delete this file?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($file->isImage())
<!-- Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
    <div class="relative max-w-full max-h-full">
        <button onclick="closeImageModal()" 
                class="absolute top-4 right-4 text-white hover:text-gray-300 z-10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <img src="{{ $file->getLargeUrl() ?: $file->getFileUrl() }}" 
             alt="{{ $file->original_filename }}" 
             class="max-w-full max-h-full object-contain">
    </div>
</div>

<script>
function openImageModal() {
    document.getElementById('imageModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});

// Close modal on background click
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});
</script>
@endif
@endsection