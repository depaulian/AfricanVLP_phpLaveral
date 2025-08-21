@extends('layouts.mobile')

@section('title', 'My Profile')

@section('content')
<div class="mobile-profile-dashboard">
    <!-- Profile Header -->
    <div class="profile-header bg-gradient-to-r from-blue-500 to-purple-600 text-white p-4">
        <div class="flex items-center space-x-4">
            <div class="relative">
                <img src="{{ $user->profile?->profile_image_url ?? asset('images/default-avatar.png') }}" 
                     alt="{{ $user->name }}" 
                     class="w-16 h-16 rounded-full border-2 border-white">
                <button class="absolute -bottom-1 -right-1 bg-white text-blue-500 rounded-full p-1 shadow-lg"
                        onclick="openImageUpload()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </button>
            </div>
            <div class="flex-1">
                <h1 class="text-xl font-bold">{{ $user->name }}</h1>
                <p class="text-blue-100 text-sm">{{ $user->email }}</p>
                <div class="flex items-center mt-1">
                    <div class="bg-white bg-opacity-20 rounded-full px-2 py-1">
                        <span class="text-xs">{{ $statistics['profile_completion'] }}% Complete</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mt-4">
            <div class="bg-white bg-opacity-20 rounded-full h-2">
                <div class="bg-white rounded-full h-2 transition-all duration-300" 
                     style="width: {{ $statistics['profile_completion'] }}%"></div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-3 gap-4 p-4 bg-white">
        <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $statistics['skills_count'] }}</div>
            <div class="text-xs text-gray-500">Skills</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-green-600">{{ $statistics['total_volunteering_hours'] }}</div>
            <div class="text-xs text-gray-500">Hours</div>
        </div>
        <div class="text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $statistics['interests_count'] }}</div>
            <div class="text-xs text-gray-500">Interests</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="p-4 bg-gray-50">
        <h2 class="text-lg font-semibold mb-3">Quick Actions</h2>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('profile.mobile.edit') }}" 
               class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 text-center">
                <div class="text-blue-500 mb-2">
                    <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium">Edit Profile</span>
            </a>
            
            <a href="{{ route('profile.mobile.documents') }}" 
               class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 text-center">
                <div class="text-green-500 mb-2">
                    <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium">Documents</span>
            </a>
            
            <a href="{{ route('profile.mobile.skills') }}" 
               class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 text-center">
                <div class="text-purple-500 mb-2">
                    <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium">Skills</span>
            </a>
            
            <a href="{{ route('profile.mobile.history') }}" 
               class="bg-white rounded-lg p-4 shadow-sm border border-gray-200 text-center">
                <div class="text-orange-500 mb-2">
                    <svg class="w-6 h-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <span class="text-sm font-medium">History</span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="p-4">
        <h2 class="text-lg font-semibold mb-3">Recent Activity</h2>
        <div class="space-y-3">
            @forelse($user->volunteeringHistory()->latest()->take(3)->get() as $history)
            <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div class="flex-1">
                        <h3 class="font-medium text-sm">{{ $history->role_title }}</h3>
                        <p class="text-gray-600 text-xs">{{ $history->organization_name }}</p>
                        <p class="text-gray-500 text-xs mt-1">{{ $history->duration }}</p>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <p class="text-sm">No volunteering history yet</p>
                <a href="{{ route('profile.mobile.history.create') }}" 
                   class="text-blue-500 text-sm font-medium">Add your first experience</a>
            </div>
            @endforelse
        </div>
    </div>

    <!-- Matching Opportunities -->
    @if($matchingOpportunities->count() > 0)
    <div class="p-4 bg-gray-50">
        <h2 class="text-lg font-semibold mb-3">Recommended for You</h2>
        <div class="space-y-3">
            @foreach($matchingOpportunities->take(2) as $opportunity)
            <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                <h3 class="font-medium text-sm">{{ $opportunity->title }}</h3>
                <p class="text-gray-600 text-xs">{{ $opportunity->organization->name }}</p>
                <div class="flex items-center justify-between mt-2">
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                        {{ $opportunity->category->name }}
                    </span>
                    <a href="{{ route('volunteering.show', $opportunity) }}" 
                       class="text-blue-500 text-xs font-medium">View</a>
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-3 text-center">
            <a href="{{ route('volunteering.index') }}" 
               class="text-blue-500 text-sm font-medium">View All Opportunities</a>
        </div>
    </div>
    @endif
</div>

<!-- Image Upload Modal -->
<div id="imageUploadModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-end justify-center min-h-screen">
        <div class="bg-white rounded-t-lg w-full max-w-md">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Update Profile Photo</h3>
            </div>
            <div class="p-4 space-y-4">
                <button onclick="capturePhoto()" 
                        class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium">
                    Take Photo
                </button>
                <button onclick="selectFromGallery()" 
                        class="w-full bg-gray-500 text-white py-3 rounded-lg font-medium">
                    Choose from Gallery
                </button>
                <button onclick="closeImageUpload()" 
                        class="w-full bg-gray-200 text-gray-800 py-3 rounded-lg font-medium">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<input type="file" id="imageInput" accept="image/*" capture="camera" class="hidden">
<input type="file" id="galleryInput" accept="image/*" class="hidden">
@endsection

@push('scripts')
<script>
function openImageUpload() {
    document.getElementById('imageUploadModal').classList.remove('hidden');
}

function closeImageUpload() {
    document.getElementById('imageUploadModal').classList.add('hidden');
}

function capturePhoto() {
    document.getElementById('imageInput').click();
    closeImageUpload();
}

function selectFromGallery() {
    document.getElementById('galleryInput').click();
    closeImageUpload();
}

// Handle image upload
document.getElementById('imageInput').addEventListener('change', handleImageUpload);
document.getElementById('galleryInput').addEventListener('change', handleImageUpload);

function handleImageUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);
    formData.append('_token', '{{ csrf_token() }}');

    // Show loading state
    const loadingToast = showToast('Uploading image...', 'info');

    fetch('{{ route("profile.mobile.upload-image") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideToast(loadingToast);
        if (data.success) {
            // Update profile image
            document.querySelector('.profile-header img').src = data.url;
            showToast('Profile image updated successfully!', 'success');
        } else {
            showToast('Failed to upload image. Please try again.', 'error');
        }
    })
    .catch(error => {
        hideToast(loadingToast);
        showToast('An error occurred. Please try again.', 'error');
    });
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
    
    return toast;
}

function hideToast(toast) {
    if (toast && toast.parentNode) {
        toast.remove();
    }
}
</script>
@endpush