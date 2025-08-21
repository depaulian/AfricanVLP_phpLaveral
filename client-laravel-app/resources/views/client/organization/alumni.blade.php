@extends('layouts.app')

@section('title', $organization->name . ' - Alumni')

@section('content')
<div class="space-y-6">
    <!-- Organization Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    @if($organization->logo)
                        <img class="h-12 w-12 rounded-full" src="{{ $organization->logo_url }}" alt="{{ $organization->name }}">
                    @else
                        <div class="h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center">
                            <span class="text-lg font-medium text-gray-700">
                                {{ substr($organization->name, 0, 2) }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <h1 class="text-xl font-bold text-gray-900">{{ $organization->name }} - Alumni</h1>
                    <p class="text-sm text-gray-600">Connect with graduates and former members</p>
                </div>
                <div class="flex-shrink-0">
                    <a href="{{ route('organizations.dashboard', $organization) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search Alumni</label>
                    <input type="text" 
                           name="search" 
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Name or email..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Graduation Year Filter -->
                <div>
                    <label for="graduation_year" class="block text-sm font-medium text-gray-700">Graduation Year</label>
                    <select name="graduation_year" id="graduation_year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Years</option>
                        @foreach($graduationYears as $year)
                            <option value="{{ $year }}" {{ request('graduation_year') == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Filter
                    </button>
                    <a href="{{ route('organizations.alumni', $organization) }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Alumni Grid -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            @if($alumni->count() > 0)
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($alumni as $alumnus)
                        <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    @if($alumnus->profile_image)
                                        <img class="h-12 w-12 rounded-full" src="{{ $alumnus->profile_image_url }}" alt="{{ $alumnus->full_name }}">
                                    @else
                                        <div class="h-12 w-12 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ substr($alumnus->first_name, 0, 1) }}{{ substr($alumnus->last_name, 0, 1) }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-medium text-gray-900 truncate">
                                        {{ $alumnus->full_name }}
                                    </h3>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Class of {{ $alumnus->pivot->graduation_year }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                @if($alumnus->email)
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        {{ $alumnus->email }}
                                    </div>
                                @endif
                                @if($alumnus->city && $alumnus->country)
                                    <div class="flex items-center text-sm text-gray-500">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        {{ $alumnus->city->name }}, {{ $alumnus->country->name }}
                                    </div>
                                @endif
                            </div>

                            @if($alumnus->pivot->notes)
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600">
                                        {{ Str::limit($alumnus->pivot->notes, 100) }}
                                    </p>
                                </div>
                            @endif

                            <div class="mt-4 flex space-x-2">
                                <a href="{{ route('messages.create', ['type' => 'user', 'id' => $alumnus->id]) }}" 
                                   class="flex-1 text-center bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700">
                                    Message
                                </a>
                                <button class="px-3 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 text-sm font-medium">
                                    Connect
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($alumni->hasPages())
                    <div class="mt-6">
                        {{ $alumni->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No alumni found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request()->hasAny(['search', 'graduation_year']))
                            Try adjusting your search or filter criteria.
                        @else
                            This organization doesn't have any alumni yet.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Alumni Statistics -->
    @if($alumni->count() > 0)
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Alumni Statistics</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ $alumni->total() }}</div>
                        <div class="text-sm text-gray-500">Total Alumni</div>
                    </div>
                    @if($graduationYears->count() > 0)
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $graduationYears->count() }}</div>
                            <div class="text-sm text-gray-500">Graduation Years</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ $graduationYears->first() }} - {{ $graduationYears->last() }}</div>
                            <div class="text-sm text-gray-500">Year Range</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection