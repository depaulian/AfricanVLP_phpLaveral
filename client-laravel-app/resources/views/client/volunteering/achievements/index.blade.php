@extends('layouts.client')

@section('title', 'My Achievements')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">My Achievements</h1>
                <p class="text-gray-600">Track your volunteer milestones and accomplishments</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('client.volunteering.portfolio') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-user mr-2"></i>
                    View Portfolio
                </a>
            </div>
        </div>
    </div>

    <!-- Achievement Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-trophy text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Achievements Earned</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_earned'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-percentage text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Completion Rate</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['completion_percentage'] }}%</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-star text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Points</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_points']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-calendar text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">This Month</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['recent_achievements'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button class="achievement-tab-btn border-b-2 border-blue-500 py-2 px-1 text-sm font-medium text-blue-600" 
                    data-tab="earned">
                Earned Achievements ({{ $userAchievements->count() }})
            </button>
            <button class="achievement-tab-btn border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300" 
                    data-tab="available">
                Available Achievements ({{ $availableAchievements->where('has_earned', false)->count() }})
            </button>
            <button class="achievement-tab-btn border-b-2 border-transparent py-2 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300" 
                    data-tab="progress">
                Progress Tracker
            </button>
        </nav>
    </div>

    <!-- Earned Achievements Tab -->
    <div id="earned-tab" class="achievement-tab-content">
        @if($userAchievements->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($userAchievements as $userAchievement)
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center">
                                    @if($userAchievement->achievement->icon)
                                        <i class="{{ $userAchievement->achievement->icon }} text-2xl text-yellow-500 mr-3"></i>
                                    @else
                                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-trophy text-yellow-600"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $userAchievement->achievement->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $userAchievement->achievement->points }} points</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button class="toggle-featured-btn text-gray-400 hover:text-yellow-500 transition-colors"
                                            data-achievement-id="{{ $userAchievement->id }}"
                                            data-featured="{{ $userAchievement->is_featured ? 'true' : 'false' }}"
                                            title="{{ $userAchievement->is_featured ? 'Remove from featured' : 'Add to featured' }}">
                                        <i class="fas fa-star {{ $userAchievement->is_featured ? 'text-yellow-500' : '' }}"></i>
                                    </button>
                                    <button class="toggle-public-btn text-gray-400 hover:text-blue-500 transition-colors"
                                            data-achievement-id="{{ $userAchievement->id }}"
                                            data-public="{{ $userAchievement->is_public ? 'true' : 'false' }}"
                                            title="{{ $userAchievement->is_public ? 'Make private' : 'Make public' }}">
                                        <i class="fas {{ $userAchievement->is_public ? 'fa-eye' : 'fa-eye-slash' }} {{ $userAchievement->is_public ? 'text-blue-500' : '' }}"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <p class="text-gray-600 text-sm mb-4">{{ $userAchievement->achievement->description }}</p>
                            
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">
                                    Earned {{ $userAchievement->earned_at->diffForHumans() }}
                                </span>
                                @if($userAchievement->is_featured)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-star mr-1"></i>
                                        Featured
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="px-6 py-3 bg-gray-50 border-t">
                            <button class="share-achievement-btn text-sm text-blue-600 hover:text-blue-800 font-medium"
                                    data-achievement-id="{{ $userAchievement->id }}">
                                <i class="fas fa-share mr-1"></i>
                                Share Achievement
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trophy text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No achievements yet</h3>
                <p class="text-gray-500 mb-6">Start volunteering to earn your first achievement!</p>
                <a href="{{ route('client.volunteering.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>
                    Find Opportunities
                </a>
            </div>
        @endif
    </div>

    <!-- Available Achievements Tab -->
    <div id="available-tab" class="achievement-tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($availableAchievements->where('has_earned', false) as $item)
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden opacity-75">
                    <div class="p-6">
                        <div class="flex items-start mb-4">
                            @if($item['achievement']->icon)
                                <i class="{{ $item['achievement']->icon }} text-2xl text-gray-400 mr-3"></i>
                            @else
                                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-trophy text-gray-400"></i>
                                </div>
                            @endif
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $item['achievement']->name }}</h3>
                                <p class="text-sm text-gray-500">{{ $item['achievement']->points }} points</p>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-4">{{ $item['achievement']->description }}</p>
                        
                        @if($item['progress']['current'] !== null && $item['progress']['target'] !== null)
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $item['progress']['current'] }} / {{ $item['progress']['target'] }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $item['progress']['progress'] }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ $item['progress']['progress'] }}% complete</p>
                            </div>
                        @endif
                        
                        <div class="text-xs text-gray-500">
                            Type: {{ ucfirst($item['achievement']->type) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Progress Tracker Tab -->
    <div id="progress-tab" class="achievement-tab-content hidden">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Achievement Progress Overview</h3>
            
            <div class="space-y-6">
                @foreach($availableAchievements->groupBy('achievement.type') as $type => $achievements)
                    <div>
                        <h4 class="text-md font-medium text-gray-900 mb-3 capitalize">{{ $type }} Achievements</h4>
                        <div class="space-y-3">
                            @foreach($achievements as $item)
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        @if($item['achievement']->icon)
                                            <i class="{{ $item['achievement']->icon }} text-lg {{ $item['has_earned'] ? 'text-yellow-500' : 'text-gray-400' }} mr-3"></i>
                                        @else
                                            <div class="w-8 h-8 {{ $item['has_earned'] ? 'bg-yellow-100' : 'bg-gray-100' }} rounded-lg flex items-center justify-center mr-3">
                                                <i class="fas fa-trophy {{ $item['has_earned'] ? 'text-yellow-600' : 'text-gray-400' }} text-sm"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $item['achievement']->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $item['achievement']->points }} points</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center">
                                        @if($item['has_earned'])
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>
                                                Earned
                                            </span>
                                        @elseif($item['progress']['current'] !== null && $item['progress']['target'] !== null)
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-gray-900">{{ $item['progress']['progress'] }}%</p>
                                                <p class="text-xs text-gray-500">{{ $item['progress']['current'] }} / {{ $item['progress']['target'] }}</p>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-500">Not started</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="shareModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Share Achievement</h3>
                    <button id="closeShareModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div id="shareContent" class="space-y-4">
                    <!-- Share content will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.achievement-tab-btn');
    const tabContents = document.querySelectorAll('.achievement-tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            
            // Update button states
            tabButtons.forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            this.classList.add('border-blue-500', 'text-blue-600');
            this.classList.remove('border-transparent', 'text-gray-500');
            
            // Update content visibility
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(tabName + '-tab').classList.remove('hidden');
        });
    });
    
    // Toggle featured status
    document.querySelectorAll('.toggle-featured-btn').forEach(button => {
        button.addEventListener('click', function() {
            const achievementId = this.dataset.achievementId;
            const isFeatured = this.dataset.featured === 'true';
            
            fetch(`/client/volunteering/achievements/${achievementId}/toggle-featured`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const icon = this.querySelector('i');
                    if (data.is_featured) {
                        icon.classList.add('text-yellow-500');
                        this.dataset.featured = 'true';
                        this.title = 'Remove from featured';
                    } else {
                        icon.classList.remove('text-yellow-500');
                        this.dataset.featured = 'false';
                        this.title = 'Add to featured';
                    }
                    
                    // Show notification
                    showNotification(data.message, 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        });
    });
    
    // Toggle public status
    document.querySelectorAll('.toggle-public-btn').forEach(button => {
        button.addEventListener('click', function() {
            const achievementId = this.dataset.achievementId;
            const isPublic = this.dataset.public === 'true';
            
            fetch(`/client/volunteering/achievements/${achievementId}/toggle-public`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const icon = this.querySelector('i');
                    if (data.is_public) {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye', 'text-blue-500');
                        this.dataset.public = 'true';
                        this.title = 'Make private';
                    } else {
                        icon.classList.remove('fa-eye', 'text-blue-500');
                        icon.classList.add('fa-eye-slash');
                        this.dataset.public = 'false';
                        this.title = 'Make public';
                    }
                    
                    // Show notification
                    showNotification(data.message, 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        });
    });
    
    // Share achievement
    document.querySelectorAll('.share-achievement-btn').forEach(button => {
        button.addEventListener('click', function() {
            const achievementId = this.dataset.achievementId;
            
            fetch(`/client/volunteering/achievements/${achievementId}/share`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showShareModal(data.sharing_data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        });
    });
    
    // Share modal functionality
    const shareModal = document.getElementById('shareModal');
    const closeShareModal = document.getElementById('closeShareModal');
    
    closeShareModal.addEventListener('click', function() {
        shareModal.classList.add('hidden');
    });
    
    shareModal.addEventListener('click', function(e) {
        if (e.target === shareModal) {
            shareModal.classList.add('hidden');
        }
    });
    
    function showShareModal(sharingData) {
        const shareContent = document.getElementById('shareContent');
        shareContent.innerHTML = `
            <div class="text-center mb-4">
                <h4 class="font-medium text-gray-900">${sharingData.title}</h4>
                <p class="text-sm text-gray-600 mt-1">${sharingData.description}</p>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <button onclick="shareToFacebook('${sharingData.url}', '${sharingData.title}')" 
                        class="flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fab fa-facebook-f mr-2"></i>
                    Facebook
                </button>
                
                <button onclick="shareToTwitter('${sharingData.url}', '${sharingData.title}', '${sharingData.hashtags.join(' ')}')" 
                        class="flex items-center justify-center px-4 py-2 bg-blue-400 text-white rounded-lg hover:bg-blue-500 transition-colors">
                    <i class="fab fa-twitter mr-2"></i>
                    Twitter
                </button>
                
                <button onclick="shareToLinkedIn('${sharingData.url}', '${sharingData.title}', '${sharingData.description}')" 
                        class="flex items-center justify-center px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition-colors">
                    <i class="fab fa-linkedin-in mr-2"></i>
                    LinkedIn
                </button>
                
                <button onclick="copyToClipboard('${sharingData.url}')" 
                        class="flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-copy mr-2"></i>
                    Copy Link
                </button>
            </div>
        `;
        
        shareModal.classList.remove('hidden');
    }
    
    // Social sharing functions
    window.shareToFacebook = function(url, title) {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank', 'width=600,height=400');
    };
    
    window.shareToTwitter = function(url, title, hashtags) {
        const text = `${title} ${hashtags}`;
        window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`, '_blank', 'width=600,height=400');
    };
    
    window.shareToLinkedIn = function(url, title, description) {
        window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`, '_blank', 'width=600,height=400');
    };
    
    window.copyToClipboard = function(url) {
        navigator.clipboard.writeText(url).then(function() {
            showNotification('Link copied to clipboard!', 'success');
            shareModal.classList.add('hidden');
        });
    };
    
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});
</script>
@endpush
@endsection