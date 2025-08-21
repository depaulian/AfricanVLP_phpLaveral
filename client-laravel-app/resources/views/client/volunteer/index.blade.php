@extends('layouts.app')

@section('title', 'Volunteer Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Volunteer Dashboard
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Track your volunteering journey and discover new opportunities
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <a href="{{ route('volunteer.opportunities', ['recommended' => 1]) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                Recommended
            </a>
            <a href="{{ route('volunteer.opportunities') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Browse All
            </a>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Active Applications -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Applications</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['active_applications'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('volunteer.my-applications') }}" class="font-medium text-blue-600 hover:text-blue-500">
                        View applications
                    </a>
                </div>
            </div>
        </div>

        <!-- Completed Volunteering -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['completed_volunteering'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('volunteer.history') }}" class="font-medium text-green-600 hover:text-green-500">
                        View history
                    </a>
                </div>
            </div>
        </div>

        <!-- Total Hours -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Hours</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['total_hours'], 1) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <span class="text-gray-500">Volunteer hours completed</span>
                </div>
            </div>
        </div>

        <!-- Interests -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Interests</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $stats['interests_count'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-5 py-3">
                <div class="text-sm">
                    <a href="{{ route('volunteer.interests') }}" class="font-medium text-purple-600 hover:text-purple-500">
                        Manage interests
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Recent Activity -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Activity</h3>
                    <div class="flow-root">
                        <ul class="-my-5 divide-y divide-gray-200">
                            @forelse($recentHistory as $history)
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="h-8 w-8 rounded-full flex items-center justify-center bg-{{ $history->getStatusColor() }}-100">
                                                <svg class="w-4 h-4 text-{{ $history->getStatusColor() }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    @if($history->status === 'completed')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    @elseif($history->status === 'active')
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    @endif
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                {{ $history->volunteeringOpportunity->event->title ?? 'Volunteer Opportunity' }}
                                            </p>
                                            <p class="text-sm text-gray-500 truncate">
                                                {{ $history->organization->name ?? 'Unknown Organization' }} • 
                                                {{ $history->getStatusText() }}
                                            </p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span class="text-sm text-gray-500">
                                                {{ $history->created->diffForHumans() }}
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="py-4 text-center text-gray-500">
                                    No recent activity. Start by applying for volunteer opportunities!
                                </li>
                            @endforelse
                        </ul>
                    </div>
                    @if($recentHistory->count() > 0)
                        <div class="mt-6">
                            <a href="{{ route('volunteer.my-applications') }}" class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                View all applications
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recommended Opportunities -->
        <div>
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Recommended for You</h3>
                        @if($stats['interests_count'] === 0)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Set interests
                            </span>
                        @endif
                    </div>
                    <div class="space-y-4">
                        @forelse($recommendedOpportunities as $opportunity)
                            <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900">
                                            <a href="{{ route('volunteer.opportunities.show', $opportunity) }}" class="hover:text-blue-600">
                                                {{ $opportunity->event->title ?? 'Volunteer Opportunity' }}
                                            </a>
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $opportunity->volunteeringRole->name ?? 'Various Roles' }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $opportunity->organization->name ?? 'Organization' }}
                                        </p>
                                    </div>
                                    <div class="ml-2">
                                        @php $urgency = $opportunity->getUrgencyLevel(); @endphp
                                        @if($urgency === 'urgent')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Urgent
                                            </span>
                                        @elseif($urgency === 'soon')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Soon
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center justify-between mt-3">
                                    <span class="text-xs text-gray-500">
                                        {{ $opportunity->getRemainingSpots() }} spots left
                                    </span>
                                    @if($opportunity->end_date)
                                        <span class="text-xs text-gray-500">
                                            Until {{ $opportunity->end_date->format('M j') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                                <p class="text-sm text-gray-500 mt-2 mb-2">No recommendations yet</p>
                                <a href="{{ route('volunteer.interests') }}" class="text-sm text-blue-600 hover:text-blue-500">
                                    Set your interests to get personalized recommendations
                                </a>
                            </div>
                        @endforelse
                    </div>
                    @if($recommendedOpportunities->count() > 0)
                        <div class="mt-6 space-y-2">
                            <a href="{{ route('volunteer.opportunities', ['recommended' => 1]) }}" class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                View all recommended
                            </a>
                            <a href="{{ route('volunteer.opportunities') }}" class="w-full flex justify-center items-center px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-500">
                                Browse all opportunities
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection