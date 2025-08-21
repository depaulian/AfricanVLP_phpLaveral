@extends('layouts.client')

@section('title', 'My Achievements')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Achievements</h1>
                <p class="text-gray-600 mt-2">Your earned badges and milestones</p>
            </div>
            <div>
                <a 
                    href="{{ route('profile.gamification.dashboard') }}" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Achievement Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 text-center">
            <div class="text-3xl font-bold text-blue-600 mb-2">{{ $achievementStats['total_achievements'] }}</div>
            <div class="text-gray-600">Total Achievements</div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6 text-center">
            <div class="text-3xl font-bold text-yellow-600 mb-2">{{ $achievementStats['total_points'] }}</div>
            <div class="text-gray-600">Total Points</div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6 text-center">
            <div class="text-3xl font-bold text-purple-600 mb-2">{{ $achievementStats['featured_achievements'] }}</div>
            <div class="text-gray-600">Featured Badges</div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6 text-center">
            <div class="text-3xl font-bold text-green-600 mb-2">{{ $achievementStats['recent_achievements'] }}</div>
            <div class="text-gray-600">This Month</div>
        </div>
    </div>

    <!-- Achievements List -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">All Achievements</h2>
        </div>

        @if($achievements->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($achievements as $achievement)
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start">
                            <!-- Badge Icon -->
                            <div class="flex-shrink-0 mr-4">
                                <div class="w-16 h-16 bg-{{ $achievement->badge_color }}-100 rounded-full flex items-center justify-center {{ $achievement->is_featured ? 'ring-4 ring-yellow-300' : '' }}">
                                    <i class="{{ $achievement->badge_icon }} text-2xl text-{{ $achievement->badge_color }}-600"></i>
                                </div>
                                @if($achievement->is_featured)
                                    <div class="text-center mt-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-star mr-1"></i>
                                            Featured
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- Achievement Details -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                            {{ $achievement->achievement_name }}
                                        </h3>
                                        <p class="text-gray-600 mb-2">{{ $achievement->achievement_description }}</p>
                                        
                                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                                            <span>
                                                <i class="fas fa-tag mr-1"></i>
                                                {{ $achievement->getTypeLabel() }}
                                            </span>
                                            <span>
                                                <i class="fas fa-calendar mr-1"></i>
                                                {{ $achievement->earned_at->format('M j, Y') }}
                                            </span>
                                            @if($achievement->isRecent())
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Recent
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Points Badge -->
                                    <div class="flex-shrink-0 ml-4">
                                        <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-coins mr-1"></i>
                                            +{{ $achievement->points_awarded }} points
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($achievements->hasPages())
                <div class="p-6 border-t border-gray-200">
                    {{ $achievements->links() }}
                </div>
            @endif
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-trophy text-6xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Achievements Yet</h3>
                <p class="text-gray-600 mb-6">Start completing your profile and engaging with the platform to earn your first achievements!</p>
                <a 
                    href="{{ route('profile.edit') }}" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition-colors"
                >
                    Complete Your Profile
                </a>
            </div>
        @endif
    </div>

    <!-- Achievement Categories -->
    @if($achievementStats['achievements_by_type']->isNotEmpty())
        <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Achievements by Category</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($achievementStats['achievements_by_type'] as $type => $count)
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-medium text-gray-900">
                                    {{ \App\Models\ProfileAchievement::TYPES[$type] ?? ucfirst(str_replace('_', ' ', $type)) }}
                                </h3>
                                <p class="text-sm text-gray-600">{{ $count }} achievement{{ $count !== 1 ? 's' : '' }}</p>
                            </div>
                            <div class="text-2xl font-bold text-blue-600">{{ $count }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection