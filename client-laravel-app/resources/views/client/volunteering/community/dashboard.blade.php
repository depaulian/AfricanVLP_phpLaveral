@extends('layouts.client')

@section('title', 'Volunteer Community Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Community Dashboard</h1>
        <p class="text-gray-600">Connect, learn, and grow with fellow volunteers</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Connections</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $dashboardData['connections']['stats']['total_connections'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Mentorships</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $dashboardData['mentorships']['stats']['as_mentor']['active'] + $dashboardData['mentorships']['stats']['as_mentee']['active'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Upcoming Events</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $dashboardData['events']['upcoming_events']->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Awards Earned</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $dashboardData['awards']['active_awards'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Activity Feed -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Activity</h2>
                </div>
                <div class="p-6">
                    @if(count($dashboardData['activity_feed']) > 0)
                        <div class="space-y-4">
                            @foreach($dashboardData['activity_feed'] as $activity)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-{{ $activity['color'] }}-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-{{ $activity['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($activity['icon'] === 'users')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                @elseif($activity['icon'] === 'trophy')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                                @elseif($activity['icon'] === 'calendar')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                @endif
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">{{ $activity['title'] }}</p>
                                        <p class="text-sm text-gray-500">{{ $activity['description'] }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ $activity['timestamp']->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">No recent activity to show.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('client.volunteering.community.connections') }}" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                        Find Volunteers to Connect With
                    </a>
                    <a href="{{ route('client.volunteering.community.events') }}" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                        Browse Community Events
                    </a>
                    <a href="{{ route('client.volunteering.community.resources') }}" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                        Explore Resources
                    </a>
                    <a href="{{ route('client.volunteering.community.mentorships') }}" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-md">
                        Find a Mentor
                    </a>
                </div>
            </div>

            <!-- Recommendations -->
            @if(count($dashboardData['recommendations']['connections']) > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Suggested Connections</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach(array_slice($dashboardData['recommendations']['connections'], 0, 3) as $suggestion)
                            <div class="flex items-center space-x-3">
                                <img class="w-8 h-8 rounded-full" src="{{ $suggestion['user']->avatar ?? '/images/default-avatar.png' }}" alt="{{ $suggestion['user']->name }}">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $suggestion['user']->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $suggestion['common_interests_count'] }} shared interests</p>
                                </div>
                                <button class="text-xs text-blue-600 hover:text-blue-800">Connect</button>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('client.volunteering.community.connections') }}" class="text-sm text-blue-600 hover:text-blue-800">View all suggestions →</a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Achievement Level -->
            @if(isset($dashboardData['awards']['achievement_level']))
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Achievement Level</h3>
                </div>
                <div class="p-6">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background-color: {{ $dashboardData['awards']['achievement_level']['current_level']['color'] }}20;">
                            <svg class="w-8 h-8" style="color: {{ $dashboardData['awards']['achievement_level']['current_level']['color'] }}" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                        </div>
                        <h4 class="text-lg font-semibold text-gray-900">{{ $dashboardData['awards']['achievement_level']['current_level']['name'] }}</h4>
                        <p class="text-sm text-gray-600">{{ $dashboardData['awards']['achievement_level']['current_points'] }} points</p>
                        
                        @if($dashboardData['awards']['achievement_level']['next_level'])
                            <div class="mt-4">
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span>Progress to {{ $dashboardData['awards']['achievement_level']['next_level']['name'] }}</span>
                                    <span>{{ $dashboardData['awards']['achievement_level']['progress_percentage'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $dashboardData['awards']['achievement_level']['progress_percentage'] }}%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">{{ $dashboardData['awards']['achievement_level']['points_to_next'] }} points to go</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection