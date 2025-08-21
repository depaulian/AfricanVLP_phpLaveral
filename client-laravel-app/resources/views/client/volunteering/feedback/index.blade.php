@extends('layouts.client')

@section('title', 'Volunteer Feedback')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Volunteer Feedback</h1>
        <p class="text-gray-600">Manage and view feedback from your volunteer experiences</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-1l-4 4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Feedback Received</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['received_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Average Rating</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        {{ $stats['average_rating_received'] ? number_format($stats['average_rating_received'], 1) : 'N/A' }}
                        @if($stats['average_rating_received'])
                            <span class="text-sm text-gray-500">/5.0</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Feedback Given</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['given_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Public Feedback</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['public_feedback_count'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6">
                <a href="{{ route('client.volunteering.feedback.index', ['type' => 'received'] + request()->except('type')) }}" 
                   class="py-4 px-1 border-b-2 font-medium text-sm {{ $type === 'received' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Feedback Received
                </a>
                <a href="{{ route('client.volunteering.feedback.index', ['type' => 'given'] + request()->except('type')) }}" 
                   class="py-4 px-1 border-b-2 font-medium text-sm {{ $type === 'given' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Feedback Given
                </a>
            </nav>
        </div>

        <!-- Filters -->
        <div class="p-6 border-b border-gray-200">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <input type="hidden" name="type" value="{{ $type }}">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Feedback Type</label>
                    <select name="feedback_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Types</option>
                        <option value="volunteer_to_organization" {{ $filters['feedback_type'] === 'volunteer_to_organization' ? 'selected' : '' }}>
                            Volunteer to Organization
                        </option>
                        <option value="organization_to_volunteer" {{ $filters['feedback_type'] === 'organization_to_volunteer' ? 'selected' : '' }}>
                            Organization to Volunteer
                        </option>
                        <option value="supervisor_to_volunteer" {{ $filters['feedback_type'] === 'supervisor_to_volunteer' ? 'selected' : '' }}>
                            Supervisor to Volunteer
                        </option>
                        <option value="beneficiary_to_volunteer" {{ $filters['feedback_type'] === 'beneficiary_to_volunteer' ? 'selected' : '' }}>
                            Beneficiary to Volunteer
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Min Rating</label>
                    <select name="rating_min" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Any</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ $filters['rating_min'] == $i ? 'selected' : '' }}>{{ $i }}+ Stars</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Response Status</label>
                    <select name="has_response" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All</option>
                        <option value="1" {{ $filters['has_response'] === '1' ? 'selected' : '' }}>With Response</option>
                        <option value="0" {{ $filters['has_response'] === '0' ? 'selected' : '' }}>No Response</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Feedback List -->
        <div class="divide-y divide-gray-200">
            @forelse($feedback as $item)
                <div class="p-6 hover:bg-gray-50">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $item->feedback_type_display }}
                                </span>
                                @if($item->overall_rating)
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $item->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                            </svg>
                                        @endfor
                                        <span class="ml-1 text-sm text-gray-600">{{ number_format($item->overall_rating, 1) }}</span>
                                    </div>
                                @endif
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item->status_color === 'green' ? 'bg-green-100 text-green-800' : ($item->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $item->status_display }}
                                </span>
                                @if($item->is_public)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Public
                                    </span>
                                @endif
                            </div>

                            <div class="mb-2">
                                <p class="text-sm text-gray-600">
                                    @if($type === 'received')
                                        From: {{ $item->reviewer_display_name }}
                                    @else
                                        To: {{ $item->reviewee->name }}
                                    @endif
                                    • {{ $item->assignment->opportunity->title ?? 'N/A' }}
                                    • {{ $item->submitted_at->format('M j, Y') }}
                                </p>
                            </div>

                            @if($item->positive_feedback || $item->improvement_feedback)
                                <div class="text-sm text-gray-700 mb-2">
                                    @if($item->positive_feedback)
                                        <p class="mb-1"><strong>Positive:</strong> {{ Str::limit($item->positive_feedback, 150) }}</p>
                                    @endif
                                    @if($item->improvement_feedback)
                                        <p><strong>Improvement:</strong> {{ Str::limit($item->improvement_feedback, 150) }}</p>
                                    @endif
                                </div>
                            @endif

                            @if($item->tags && count($item->tags) > 0)
                                <div class="flex flex-wrap gap-1 mb-2">
                                    @foreach(array_slice($item->tags, 0, 5) as $tag)
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $tag }}
                                        </span>
                                    @endforeach
                                    @if(count($item->tags) > 5)
                                        <span class="text-xs text-gray-500">+{{ count($item->tags) - 5 }} more</span>
                                    @endif
                                </div>
                            @endif

                            @if($item->response)
                                <div class="mt-2 p-3 bg-blue-50 rounded-md">
                                    <p class="text-sm font-medium text-blue-900 mb-1">Response:</p>
                                    <p class="text-sm text-blue-800">{{ Str::limit($item->response, 200) }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="ml-4 flex-shrink-0">
                            <a href="{{ route('client.volunteering.feedback.show', $item) }}" 
                               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-1l-4 4z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No feedback found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($type === 'received')
                            You haven't received any feedback yet.
                        @else
                            You haven't given any feedback yet.
                        @endif
                    </p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($feedback->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $feedback->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection