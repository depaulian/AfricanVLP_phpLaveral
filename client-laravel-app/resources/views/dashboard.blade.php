@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    @if($user->profile_image)
                        <img class="h-16 w-16 rounded-full" src="{{ asset('storage/profiles/' . $user->profile_image) }}" alt="">
                    @else
                        <div class="h-16 w-16 rounded-full bg-blue-500 flex items-center justify-center text-white text-xl font-medium">
                            {{ substr($user->first_name, 0, 1) }}{{ substr($user->last_name, 0, 1) }}
                        </div>
                    @endif
                </div>
                <div class="ml-5">
                    <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ $user->first_name }}!</h1>
                    <p class="text-sm text-gray-500">
                        @if($userOrganizations->count() > 0)
                            Member of {{ $userOrganizations->count() }} {{ Str::plural('organization', $userOrganizations->count()) }}
                        @else
                            Ready to join organizations and start volunteering
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">My Organizations</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $userOrganizations->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Volunteering</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $userVolunteeringHistory->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Upcoming Events</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $upcomingEvents->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Forum Discussions</dt>
                            <dd class="text-lg font-medium text-gray-900">{{ $recentForumThreads->count() }}</dd>
                        </dl>
                    </div>
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
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Latest News</h3>
                    <div class="space-y-4">
                        @forelse($recentNews as $news)
                            <div class="border-l-4 border-blue-500 pl-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('news.show', $news) }}" class="hover:text-blue-600">
                                            {{ $news->title }}
                                        </a>
                                    </h4>
                                    <span class="text-xs text-gray-500">
                                        {{ $news->created->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ Str::limit($news->description, 100) }}
                                </p>
                                @if($news->organization)
                                    <p class="mt-1 text-xs text-gray-500">
                                        by {{ $news->organization->name }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">No recent news available</p>
                        @endforelse
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('news.index') }}" class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            View all news
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Volunteering Opportunities -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Volunteer Opportunities</h3>
                    <div class="space-y-3">
                        @forelse($volunteeringOpportunities as $opportunity)
                            <div class="border rounded-lg p-3 hover:bg-gray-50">
                                <h4 class="text-sm font-medium text-gray-900">
                                    <a href="{{ route('volunteer.opportunities.show', $opportunity) }}" class="hover:text-blue-600">
                                        {{ $opportunity->event->title ?? 'Volunteer Opportunity' }}
                                    </a>
                                </h4>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $opportunity->getRemainingSpots() }} spots available
                                </p>
                                @if($opportunity->end_date)
                                    <p class="text-xs text-gray-500">
                                        Until {{ $opportunity->end_date->format('M j, Y') }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4 text-sm">No opportunities available</p>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('volunteer.opportunities') }}" class="w-full flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            View all opportunities
                        </a>
                    </div>
                </div>
            </div>

            <!-- My Organizations -->
            @if($userOrganizations->count() > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">My Organizations</h3>
                        <div class="space-y-3">
                            @foreach($userOrganizations as $organization)
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        @if($organization->logo)
                                            <img class="h-8 w-8 rounded-full" src="{{ $organization->logo_url }}" alt="">
                                        @else
                                            <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-xs font-medium text-gray-700">
                                                    {{ substr($organization->name, 0, 2) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <a href="{{ route('organizations.dashboard', $organization) }}" class="hover:text-blue-600">
                                                {{ $organization->name }}
                                            </a>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ ucfirst($organization->pivot->role) }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Load personalized content
    fetch('{{ route("dashboard.personalized") }}')
        .then(response => response.json())
        .then(data => {
            console.log('Personalized content loaded:', data);
        })
        .catch(error => console.error('Error loading personalized content:', error));
</script>
@endpush
@endsection