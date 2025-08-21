@extends('layouts.client')

@section('title', 'Profile Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Profile Dashboard</h1>
                <p class="text-gray-600 mt-2">Track your progress and achievements</p>
            </div>
            <div class="flex space-x-4">
                <button 
                    onclick="recalculateScore()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                >
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh Score
                </button>
                <a 
                    href="{{ route('profile.gamification.leaderboard') }}" 
                    class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition-colors"
                >
                    <i class="fas fa-trophy mr-2"></i>
                    Leaderboard
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Profile Score & Progress -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Profile Score Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Profile Strength</h2>
                    @if($userRank)
                        <div class="text-sm text-gray-600">
                            Rank #{{ $userRank }} globally
                        </div>
                    @endif
                </div>

                @if($profileScore)
                    <!-- Overall Score -->
                    <div class="text-center mb-6">
                        <div class="relative inline-flex items-center justify-center w-32 h-32 mb-4">
                            <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                                <circle 
                                    cx="50" cy="50" r="40" 
                                    stroke="{{ $profileScore->getStrengthColor() === 'green' ? '#10b981' : ($profileScore->getStrengthColor() === 'blue' ? '#3b82f6' : ($profileScore->getStrengthColor() === 'yellow' ? '#f59e0b' : ($profileScore->getStrengthColor() === 'orange' ? '#f97316' : '#ef4444'))) }}" 
                                    stroke-width="8" 
                                    fill="none"
                                    stroke-dasharray="{{ 2 * pi() * 40 }}"
                                    stroke-dashoffset="{{ 2 * pi() * 40 * (1 - $profileScore->total_score / 100) }}"
                                    class="transition-all duration-1000 ease-out"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-gray-900">{{ $profileScore->total_score }}</div>
                                    <div class="text-sm text-gray-600">Score</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-lg font-semibold text-{{ $profileScore->getStrengthColor() }}-600">
                            {{ $profileScore->getStrengthLevel() }}
                        </div>
                    </div>

                    <!-- Score Breakdown -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $profileScore->completion_score }}</div>
                            <div class="text-sm text-gray-600">Completion</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $profileScore->quality_score }}</div>
                            <div class="text-sm text-gray-600">Quality</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">{{ $profileScore->engagement_score }}</div>
                            <div class="text-sm text-gray-600">Engagement</div>
                        </div>
                        <div class="text-center p-3 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold text-orange-600">{{ $profileScore->verification_score }}</div>
                            <div class="text-sm text-gray-600">Verification</div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-500 mb-4">
                            <i class="fas fa-chart-line text-4xl"></i>
                        </div>
                        <p class="text-gray-600">Your profile score will appear here once calculated.</p>
                        <button 
                            onclick="recalculateScore()" 
                            class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
                        >
                            Calculate Score
                        </button>
                    </div>
                @endif
            </div>

            <!-- Profile Completion Progress -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Profile Completion</h2>
                
                <div class="space-y-4" id="completion-progress">
                    <!-- Progress will be loaded via JavaScript -->
                </div>
            </div>

            <!-- Improvement Suggestions -->
            @if(!empty($suggestions))
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>
                        Suggestions for Improvement
                    </h2>
                    
                    <div class="space-y-4">
                        @foreach($suggestions as $suggestion)
                            <div class="flex items-start p-4 bg-{{ $suggestion['priority'] === 'high' ? 'red' : ($suggestion['priority'] === 'medium' ? 'yellow' : 'blue') }}-50 rounded-lg border border-{{ $suggestion['priority'] === 'high' ? 'red' : ($suggestion['priority'] === 'medium' ? 'yellow' : 'blue') }}-200">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-8 h-8 bg-{{ $suggestion['priority'] === 'high' ? 'red' : ($suggestion['priority'] === 'medium' ? 'yellow' : 'blue') }}-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-{{ $suggestion['priority'] === 'high' ? 'exclamation' : ($suggestion['priority'] === 'medium' ? 'info' : 'check') }} text-{{ $suggestion['priority'] === 'high' ? 'red' : ($suggestion['priority'] === 'medium' ? 'yellow' : 'blue') }}-600 text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1">{{ $suggestion['title'] }}</h3>
                                    <p class="text-gray-600 text-sm mb-3">{{ $suggestion['description'] }}</p>
                                    <a 
                                        href="{{ $suggestion['url'] }}" 
                                        class="inline-flex items-center text-sm font-medium text-{{ $suggestion['priority'] === 'high' ? 'red' : ($suggestion['priority'] === 'medium' ? 'yellow' : 'blue') }}-600 hover:text-{{ $suggestion['priority'] === 'high' ? 'red' : ($suggestion['priority'] === 'medium' ? 'yellow' : 'blue') }}-800"
                                    >
                                        {{ $suggestion['action'] }}
                                        <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Achievements & Stats -->
        <div class="space-y-6">
            <!-- Achievement Stats -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Achievement Stats</h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Total Achievements</span>
                        <span class="font-semibold text-gray-900">{{ $achievementStats['total_achievements'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Total Points</span>
                        <span class="font-semibold text-blue-600">{{ $achievementStats['total_points'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Featured Badges</span>
                        <span class="font-semibold text-yellow-600">{{ $achievementStats['featured_achievements'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">This Month</span>
                        <span class="font-semibold text-green-600">{{ $achievementStats['recent_achievements'] }}</span>
                    </div>
                </div>

                <div class="mt-6">
                    <a 
                        href="{{ route('profile.gamification.achievements') }}" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-4 rounded-lg transition-colors block"
                    >
                        View All Achievements
                    </a>
                </div>
            </div>

            <!-- Recent Achievements -->
            @if($user->recentAchievements->isNotEmpty())
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Recent Achievements</h2>
                    
                    <div class="space-y-4">
                        @foreach($user->recentAchievements->take(3) as $achievement)
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0 mr-3">
                                    <div class="w-10 h-10 bg-{{ $achievement->badge_color }}-100 rounded-full flex items-center justify-center">
                                        <i class="{{ $achievement->badge_icon }} text-{{ $achievement->badge_color }}-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $achievement->achievement_name }}</p>
                                    <p class="text-sm text-gray-600">{{ $achievement->earned_at->diffForHumans() }}</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        +{{ $achievement->points_awarded }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Top Performers -->
            @if($leaderboard->isNotEmpty())
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Top Performers</h2>
                    
                    <div class="space-y-3">
                        @foreach($leaderboard->take(5) as $leader)
                            <div class="flex items-center">
                                <div class="flex-shrink-0 mr-3">
                                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-600">#{{ $leader['rank'] }}</span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate">{{ $leader['user']->full_name }}</p>
                                    <p class="text-sm text-gray-600">{{ $leader['strength_level'] }}</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="font-semibold text-blue-600">{{ $leader['score'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <a 
                            href="{{ route('profile.gamification.leaderboard') }}" 
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                            View Full Leaderboard →
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Load completion progress
function loadCompletionProgress() {
    fetch('{{ route("profile.gamification.completion-progress") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderCompletionProgress(data.data);
            }
        })
        .catch(error => console.error('Error loading completion progress:', error));
}

function renderCompletionProgress(data) {
    const container = document.getElementById('completion-progress');
    
    // Overall progress
    const overallHtml = `
        <div class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-gray-900">Overall Completion</span>
                <span class="font-semibold text-blue-600">${data.overall_percentage}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-3">
                <div class="bg-blue-600 h-3 rounded-full transition-all duration-1000" style="width: ${data.overall_percentage}%"></div>
            </div>
        </div>
    `;
    
    // Section progress
    let sectionsHtml = '';
    Object.entries(data.sections).forEach(([key, section]) => {
        const color = section.percentage === 100 ? 'green' : (section.percentage > 50 ? 'yellow' : 'red');
        sectionsHtml += `
            <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-gray-900">${section.label}</span>
                        <span class="text-sm font-semibold text-${color}-600">${section.percentage}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-${color}-500 h-2 rounded-full transition-all duration-500" style="width: ${section.percentage}%"></div>
                    </div>
                    ${section.count !== undefined ? `<div class="text-xs text-gray-500 mt-1">${section.count} items${section.verified_count !== undefined ? ` (${section.verified_count} verified)` : ''}</div>` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = overallHtml + sectionsHtml;
}

// Recalculate score
function recalculateScore() {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Calculating...';
    button.disabled = true;
    
    fetch('{{ route("profile.gamification.recalculate-score") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('Profile score updated successfully!', 'success');
            // Reload page to show updated data
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showNotification('Failed to update profile score', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating score', 'error');
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function showNotification(message, type) {
    // Simple notification implementation
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCompletionProgress();
});
</script>
@endpush
@endsection