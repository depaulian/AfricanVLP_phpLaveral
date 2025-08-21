@extends('layouts.client')

@section('title', 'Leaderboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Leaderboard</h1>
                <p class="text-gray-600 mt-2">Top performers on the platform</p>
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

    <!-- User's Position -->
    @if($userRank)
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-8">
            <div class="flex items-center">
                <div class="flex-shrink-0 mr-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <span class="text-lg font-bold text-blue-600">#{{ $userRank }}</span>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Your Current Position</h3>
                    <p class="text-gray-600">You're ranked #{{ $userRank }} out of all users</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Leaderboard -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Top 50 Users</h2>
        </div>

        @if($leaderboard->isNotEmpty())
            <div class="divide-y divide-gray-200">
                @foreach($leaderboard as $leader)
                    <div class="p-6 hover:bg-gray-50 transition-colors {{ $leader['user']->id === $user->id ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}">
                        <div class="flex items-center">
                            <!-- Rank -->
                            <div class="flex-shrink-0 mr-6">
                                @if($leader['rank'] <= 3)
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center {{ $leader['rank'] === 1 ? 'bg-yellow-100' : ($leader['rank'] === 2 ? 'bg-gray-100' : 'bg-orange-100') }}">
                                        <i class="fas fa-{{ $leader['rank'] === 1 ? 'crown' : 'medal' }} text-xl {{ $leader['rank'] === 1 ? 'text-yellow-600' : ($leader['rank'] === 2 ? 'text-gray-600' : 'text-orange-600') }}"></i>
                                    </div>
                                @else
                                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                        <span class="text-lg font-bold text-gray-600">#{{ $leader['rank'] }}</span>
                                    </div>
                                @endif
                            </div>

                            <!-- User Info -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 {{ $leader['user']->id === $user->id ? 'text-blue-900' : '' }}">
                                            {{ $leader['user']->full_name }}
                                            @if($leader['user']->id === $user->id)
                                                <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    You
                                                </span>
                                            @endif
                                        </h3>
                                        <div class="flex items-center space-x-4 mt-1">
                                            <span class="text-sm text-gray-600">
                                                <i class="fas fa-chart-line mr-1"></i>
                                                {{ $leader['strength_level'] }}
                                            </span>
                                            <span class="text-sm text-gray-600">
                                                <i class="fas fa-trophy mr-1"></i>
                                                {{ $leader['achievements_count'] }} achievements
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Score -->
                                    <div class="flex-shrink-0 text-right">
                                        <div class="text-2xl font-bold text-blue-600">{{ $leader['score'] }}</div>
                                        <div class="text-sm text-gray-500">points</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="p-12 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-trophy text-6xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Rankings Available</h3>
                <p class="text-gray-600">The leaderboard will appear once users start earning scores.</p>
            </div>
        @endif
    </div>

    <!-- Leaderboard Info -->
    <div class="mt-8 bg-gray-50 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">How Rankings Work</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Scoring Factors</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Profile completion (25%)</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Content quality (25%)</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Platform engagement (25%)</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Information verification (25%)</li>
                </ul>
            </div>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Ranking Updates</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li><i class="fas fa-info-circle text-blue-500 mr-2"></i>Rankings update daily</li>
                    <li><i class="fas fa-info-circle text-blue-500 mr-2"></i>Scores recalculate automatically</li>
                    <li><i class="fas fa-info-circle text-blue-500 mr-2"></i>New achievements boost your score</li>
                    <li><i class="fas fa-info-circle text-blue-500 mr-2"></i>Active participation matters most</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection