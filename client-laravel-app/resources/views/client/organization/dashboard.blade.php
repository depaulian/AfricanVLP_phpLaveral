@extends('layouts.app')

@section('title', $organization->name . ' - Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Organization Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    @if($organization->logo)
                        <img class="h-16 w-16 rounded-full" src="{{ $organization->logo_url }}" alt="{{ $organization->name }}">
                    @else
                        <div class="h-16 w-16 rounded-full bg-gray-300 flex items-center justify-center">
                            <span class="text-xl font-medium text-gray-700">
                                {{ substr($organization->name, 0, 2) }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $organization->name }}</h1>
                    @if($organization->description)
                        <p class="text-sm text-gray-600 mt-1">{{ Str::limit($organization->description, 150) }}</p>
                    @endif
                    <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                        @if($organization->city && $organization->country)
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $organization->city->name }}, {{ $organization->country->name }}
                            </span>
                        @endif
                        @if($organization->website)
                            <a href="{{ $organization->website }}" target="_blank" class="flex items-center hover:text-blue-600">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                Website
                            </a>
                        @endif
                    </div>
                </div>
                <div class="flex-shrink-0">
                    @if($userRole === 'admin')
                        <a href="{{ route('organizations.manage', $organization) }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Manage
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white shadow rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <a href="{{ route('organizations.dashboard', $organization) }}" 
                   class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Dashboard
                </a>
                <a href="{{ route('organizations.members', $organization) }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Members ({{ $stats['total_members'] }})
                </a>
                <a href="{{ route('organizations.events', $organization) }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Events ({{ $stats['active_events'] }})
                </a>
                <a href="{{ route('organizations.news', $organization) }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    News ({{ $stats['total_news'] }})
                </a>
                <a href="{{ route('organizations.forum.index', $organization) }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Forum
                </a>
                @if($stats['alumni_count'] > 0)
                    <a href="{{ route('organizations.alumni', $organization) }}" 
                       class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        Alumni ({{ $stats['alumni_count'] }})
                    </a>
                @endif
            </nav>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total Members -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Members</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['total_members'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('organizations.members', $organization) }}" class="font-medium text-blue-600 hover:text-blue-500">
                        View members
                    </a>
                </div>
            </div>
        </div>

        <!-- Active Events -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Events</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['active_events'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('organizations.events', $organization) }}" class="font-medium text-green-600 hover:text-green-500">
                        View events
                    </a>
                </div>
            </div>
        </div>

        <!-- Volunteer Opportunities -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Volunteer Opportunities</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['volunteer_opportunities'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('volunteer.opportunities', ['organization_id' => $organization->id]) }}" class="font-medium text-purple-600 hover:text-purple-500">
                        View opportunities
                    </a>
                </div>
            </div>
        </div>

        <!-- Alumni -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Alumni</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['alumni_count'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    @if($stats['alumni_count'] > 0)
                        <a href="{{ route('organizations.alumni', $organization) }}" class="font-medium text-yellow-600 hover:text-yellow-500">
                            View alumni
                        </a>
                    @else
                        <span class="text-gray-500">No alumni yet</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Recent News -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent News</h3>
                    <div class="space-y-4">
                        @forelse($recentNews as $news)
                            <div class="border-l-4 border-blue-400 pl-4">
                                <h4 class="text-sm font-medium text-gray-900">
                                    <a href="{{ route('news.show', $news) }}" class="hover:text-blue-600">
                                        {{ $news->title }}
                                    </a>
                                </h4>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ Str::limit($news->description ?? $news->content, 100) }}
                                </p>
                                <p class="text-xs text-gray-500 mt-2">
                                    {{ $news->published_at->format('M j, Y') }}
                                </p>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">No recent news.</p>
                        @endforelse
                    </div>
                    @if($recentNews->count() > 0)
                        <div class="mt-6">
                            <a href="{{ route('organizations.news', $organization) }}" class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                View all news
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div>
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Upcoming Events</h3>
                    <div class="space-y-4">
                        @forelse($upcomingEvents as $event)
                            <div class="border rounded-lg p-3 hover:bg-gray-50">
                                <h4 class="text-sm font-medium text-gray-900">
                                    <a href="{{ route('events.show', $event) }}" class="hover:text-blue-600">
                                        {{ $event->title }}
                                    </a>
                                </h4>
                                <div class="flex items-center text-xs text-gray-500 mt-1">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $event->start_date->format('M j, Y') }}
                                </div>
                                @if($event->location)
                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        {{ $event->location }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">No upcoming events.</p>
                        @endforelse
                    </div>
                    @if($upcomingEvents->count() > 0)
                        <div class="mt-6">
                            <a href="{{ route('organizations.events', $organization) }}" class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                View all events
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Admin Panel for Pending Applications -->
            @if($userRole === 'admin' && $recentApplications->count() > 0)
                <div class="bg-white shadow rounded-lg mt-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Pending Applications</h3>
                        <div class="space-y-3">
                            @foreach($recentApplications as $application)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $application->user->full_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $application->volunteeringOpportunity->event->title ?? 'Volunteer Opportunity' }}
                                        </p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button class="text-green-600 hover:text-green-900 text-xs font-medium">
                                            Accept
                                        </button>
                                        <button class="text-red-600 hover:text-red-900 text-xs font-medium">
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('organizations.manage', $organization) }}" class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Manage all applications
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection