@extends('layouts.client')

@section('title', $opportunity->title)

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('client.home') }}" class="text-gray-700 hover:text-blue-600">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('client.volunteering.index') }}" class="ml-1 text-gray-700 hover:text-blue-600 md:ml-2">Volunteering</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-gray-500 md:ml-2">{{ Str::limit($opportunity->title, 30) }}</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    @if($opportunity->featured)
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 mb-4">
                            ⭐ Featured Opportunity
                        </div>
                    @endif

                    <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $opportunity->title }}</h1>
                    
                    <div class="flex flex-wrap items-center gap-4 mb-4">
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m4 0V9a2 2 0 012-2h2a2 2 0 012 2v12M13 7a1 1 0 11-2 0 1 1 0 012 0z"></path>
                            </svg>
                            <a href="#" class="hover:text-blue-600">{{ $opportunity->organization->name }}</a>
                        </div>
                        
                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            @if($opportunity->location_type === 'remote')
                                Remote
                            @elseif($opportunity->location_type === 'hybrid')
                                Hybrid
                            @else
                                {{ $opportunity->city->name ?? 'On-site' }}@if($opportunity->country), {{ $opportunity->country->name }}@endif
                            @endif
                        </div>

                        <div class="flex items-center text-gray-600">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ $opportunity->time_commitment }}
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            {{ $opportunity->category->name }}
                        </span>
                        
                        @if($opportunity->experience_level !== 'any')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                                {{ ucfirst($opportunity->experience_level) }} Level
                            </span>
                        @endif

                        @if($opportunity->role)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                {{ $opportunity->role->name }}
                            </span>
                        @endif
                    </div>

                    <!-- Match Score for authenticated users -->
                    @auth
                        @if($matchScore)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-green-800">
                                            {{ round($matchScore) }}% Match
                                        </h3>
                                        <div class="mt-1 text-sm text-green-700">
                                            This opportunity matches your skills and interests well!
                                            <button onclick="showMatchDetails()" class="underline hover:no-underline ml-1">
                                                See details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endauth

                    <!-- Image -->
                    @if($opportunity->image_url)
                        <div class="mb-6">
                            <img src="{{ $opportunity->image_url }}" alt="{{ $opportunity->title }}" class="w-full h-64 object-cover rounded-lg">
                        </div>
                    @endif
                </div>

                <!-- Description -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">About This Opportunity</h2>
                    <div class="prose max-w-none text-gray-700">
                        {!! nl2br(e($opportunity->description)) !!}
                    </div>
                </div>

                <!-- Requirements -->
                @if($opportunity->requirements)
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Requirements</h2>
                        <div class="prose max-w-none text-gray-700">
                            {!! nl2br(e($opportunity->requirements)) !!}
                        </div>
                    </div>
                @endif

                <!-- Skills Required -->
                @if($opportunity->required_skills && count($opportunity->required_skills) > 0)
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Skills Needed</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach($opportunity->required_skills as $skill)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                    {{ $skill }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Benefits -->
                @if($opportunity->benefits)
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">What You'll Gain</h2>
                        <div class="prose max-w-none text-gray-700">
                            {!! nl2br(e($opportunity->benefits)) !!}
                        </div>
                    </div>
                @endif

                <!-- Similar Opportunities -->
                @if($similarOpportunities->count() > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Similar Opportunities</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($similarOpportunities as $similar)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <h3 class="font-medium text-gray-900 mb-2">
                                        <a href="{{ route('client.volunteering.show', $similar) }}" class="hover:text-blue-600">
                                            {{ $similar->title }}
                                        </a>
                                    </h3>
                                    <p class="text-sm text-gray-600 mb-2">{{ $similar->organization->name }}</p>
                                    <p class="text-sm text-gray-500">{{ Str::limit($similar->description, 100) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Application Card -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-6 sticky top-4">
                    <div class="text-center mb-6">
                        @auth
                            @if($hasApplied)
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                    <div class="flex items-center justify-center mb-2">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-blue-900 mb-1">Application Submitted</h3>
                                    <p class="text-sm text-blue-700">
                                        Status: <span class="font-medium">{{ ucfirst($userApplication->status) }}</span>
                                    </p>
                                    @if($userApplication->status === 'pending')
                                        <p class="text-xs text-blue-600 mt-2">We'll notify you when there's an update.</p>
                                    @endif
                                </div>
                            @else
                                @can('apply', $opportunity)
                                    <a href="{{ route('client.volunteering.apply', $opportunity) }}" 
                                       class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 inline-block">
                                        Apply Now
                                    </a>
                                @else
                                    <div class="bg-gray-100 text-gray-600 font-semibold py-3 px-6 rounded-lg">
                                        @if(!$opportunity->is_accepting_applications)
                                            Applications Closed
                                        @elseif($opportunity->spots_remaining <= 0)
                                            Position Full
                                        @else
                                            Cannot Apply
                                        @endif
                                    </div>
                                @endcan
                            @endif
                        @else
                            <a href="{{ route('login') }}" 
                               class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 inline-block">
                                Login to Apply
                            </a>
                        @endauth
                    </div>

                    <!-- Key Information -->
                    <div class="space-y-4">
                        @if($opportunity->application_deadline)
                            <div class="flex items-center text-sm">
                                <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <div class="text-gray-900 font-medium">Application Deadline</div>
                                    <div class="text-gray-600">{{ $opportunity->application_deadline->format('M j, Y') }}</div>
                                </div>
                            </div>
                        @endif

                        @if($opportunity->start_date)
                            <div class="flex items-center text-sm">
                                <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <div>
                                    <div class="text-gray-900 font-medium">Start Date</div>
                                    <div class="text-gray-600">{{ $opportunity->start_date->format('M j, Y') }}</div>
                                </div>
                            </div>
                        @endif

                        @if($opportunity->volunteers_needed)
                            <div class="flex items-center text-sm">
                                <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <div>
                                    <div class="text-gray-900 font-medium">Volunteers Needed</div>
                                    <div class="text-gray-600">{{ $opportunity->spots_remaining }} spots remaining</div>
                                </div>
                            </div>
                        @endif

                        @if($opportunity->contact_email || $opportunity->contact_phone)
                            <div class="pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Contact Information</h4>
                                @if($opportunity->contact_email)
                                    <div class="text-sm text-gray-600 mb-1">
                                        <a href="mailto:{{ $opportunity->contact_email }}" class="hover:text-blue-600">
                                            {{ $opportunity->contact_email }}
                                        </a>
                                    </div>
                                @endif
                                @if($opportunity->contact_phone)
                                    <div class="text-sm text-gray-600">
                                        <a href="tel:{{ $opportunity->contact_phone }}" class="hover:text-blue-600">
                                            {{ $opportunity->contact_phone }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Opportunity Stats</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Applications</span>
                            <span class="font-medium">{{ $statistics['total_applications'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Active Volunteers</span>
                            <span class="font-medium">{{ $statistics['active_assignments'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Hours Contributed</span>
                            <span class="font-medium">{{ number_format($statistics['total_hours_logged']) }}</span>
                        </div>
                        @if($statistics['days_until_deadline'])
                            <div class="flex justify-between">
                                <span class="text-gray-600">Days to Apply</span>
                                <span class="font-medium text-orange-600">{{ $statistics['days_until_deadline'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Match Details Modal -->
@auth
    @if($matchScore)
        <div id="matchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Match Details</h3>
                        <button onclick="hideMatchDetails()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="matchDetails" class="text-sm text-gray-600">
                        Loading match details...
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth

@push('scripts')
<script>
@auth
    @if($matchScore)
        function showMatchDetails() {
            document.getElementById('matchModal').classList.remove('hidden');
            
            fetch(`{{ route('client.volunteering.match-explanation', $opportunity) }}`)
                .then(response => response.json())
                .then(data => {
                    const detailsHtml = `
                        <div class="space-y-3">
                            <div class="text-center mb-4">
                                <div class="text-2xl font-bold text-green-600">${data.total_score}%</div>
                                <div class="text-gray-500">Overall Match</div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>Interest Match (40%)</span>
                                    <span class="font-medium">${data.breakdown.interest.score}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Skills Match (35%)</span>
                                    <span class="font-medium">${data.breakdown.skills.score}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Location Match (15%)</span>
                                    <span class="font-medium">${data.breakdown.location.score}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Experience Match (10%)</span>
                                    <span class="font-medium">${data.breakdown.experience.score}%</span>
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('matchDetails').innerHTML = detailsHtml;
                })
                .catch(error => {
                    document.getElementById('matchDetails').innerHTML = 'Unable to load match details.';
                });
        }

        function hideMatchDetails() {
            document.getElementById('matchModal').classList.add('hidden');
        }
    @endif
@endauth
</script>
@endpush
@endsection