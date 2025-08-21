@extends('layouts.app')

@section('title', 'Profile Image Gallery')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Profile Image Gallery</h1>
                <p class="text-gray-600 mt-2">Manage your profile images and select your current photo</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="openUploadModal()" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Upload New Image
                </button>
                <button onclick="openBulkUploadModal()" 
                        class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Bulk Upload
                </button>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button onclick="filterImages('all')" 
                        class="filter-tab active py-2 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                    All Images ({{ $images->count() }})
                </button>
                <button onclick="filterImages('approved')" 
                        class="filter-tab py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Approved ({{ $images->where('status', 'approved')->count() }})
                </button>
                <button onclick="filterImages('pending')" 
                        class="filter-tab py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Pending ({{ $images->where('status', 'pending')->count() }})
                </button>
                <button onclick="filterImages('rejected')" 
                        class="filter-tab py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Rejected ({{ $images->where('status', 'rejected')->count() }})
                </button>
            </nav>
        </div>

        <!-- Current Profile Image -->
        @if($currentImage)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Current Profile Image</h2>
            <div class="flex items-center space-x-6">
                <div class="relative">
                    <img src="{{ $currentImage->large_url }}" 
                         alt="Current profile image" 
                         class="w-32 h-32 rounded-full object-cover border-4 border-blue-500">
                    <div class="absolute -top-2 -right-2 bg-blue-500 text-white rounded-full p-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="font-medium text-gray-900">{{ $currentImage->original_filename }}</h3>
                    <p class="text-sm text-gray-500">{{ $currentImage->file_size_human }} • Uploaded {{ $currentImage->created_at->diffForHumans() }}</p>
                    <div class="flex items-center mt-2 space-x-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Current
                        </span>
                        <button onclick="cropImage({{ $currentImage->id }})" 
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Crop Image
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif  
      <!-- Image Gallery Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6" id="imageGallery">
            @forelse($images as $image)
            <div class="image-item bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden" 
                 data-status="{{ $image->status }}" data-id="{{ $image->id }}">
                
                <!-- Image -->
                <div class="relative aspect-square">
                    <img src="{{ $image->medium_url }}" 
                         alt="{{ $image->original_filename }}" 
                         class="w-full h-full object-cover cursor-pointer"
                         onclick="viewImage({{ $image->id }})">
                    
                    <!-- Status Badge -->
                    <div class="absolute top-2 left-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            {{ $image->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $image->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $image->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                            @if($image->status === 'approved')
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @elseif($image->status === 'pending')
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            {{ $image->status_label }}
                        </span>
                    </div>
                    
                    <!-- Current Badge -->
                    @if($image->is_current)
                    <div class="absolute top-2 right-2">
                        <span class="bg-blue-500 text-white rounded-full p-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </span>
                    </div>
                    @endif
                    
                    <!-- Actions Overlay -->
                    <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center opacity-0 hover:opacity-100">
                        <div class="flex space-x-2">
                            @if($image->isApproved() && !$image->is_current)
                            <button onclick="setAsCurrent({{ $image->id }})" 
                                    class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-full transition-colors"
                                    title="Set as current">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </button>
                            @endif
                            
                            <button onclick="cropImage({{ $image->id }})" 
                                    class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-full transition-colors"
                                    title="Crop image">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                            
                            <button onclick="deleteImage({{ $image->id }})" 
                                    class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-full transition-colors"
                                    title="Delete image">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Image Info -->
                <div class="p-4">
                    <h3 class="font-medium text-gray-900 truncate" title="{{ $image->original_filename }}">
                        {{ $image->original_filename }}
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $image->file_size_human }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $image->created_at->format('M j, Y') }}</p>
                    
                    @if($image->isRejected() && $image->moderation_reason)
                    <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                        <strong>Rejected:</strong> {{ $image->moderation_reason }}
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No profile images yet</h3>
                <p class="text-gray-500 mb-4">Upload your first profile image to get started</p>
                <button onclick="openUploadModal()" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
                    Upload Image
                </button>
            </div>
            @endforelse
        </div>
    </div>
</div>