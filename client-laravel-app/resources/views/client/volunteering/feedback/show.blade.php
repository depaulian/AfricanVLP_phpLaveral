@extends('layouts.client')

@section('title', 'Feedback Details')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Feedback Details</h1>
                <p class="text-gray-600">View detailed feedback information</p>
            </div>
            <div class="flex space-x-3">
                @can('update', $feedback)
                    @if($feedback->canBeEdited())
                        <a href="{{ route('client.volunteering.feedback.edit', $feedback) }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit
                        </a>
                    @endif
                @endcan
                <a href="{{ route('client.volunteering.feedback.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Feedback
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Feedback Overview -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $feedback->feedback_type_display }}</h2>
                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <span>{{ $feedback->submitted_at->format('M j, Y \a\t g:i A') }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $feedback->status_color === 'green' ? 'bg-green-100 text-green-800' : ($feedback->status_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $feedback->status_display }}
                            </span>
                            @if($feedback->is_anonymous)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    Anonymous
                                </span>
                            @endif
                            @if($feedback->is_public)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    Public
                                </span>
                            @endif
                        </div>
                    </div>
                    @if($feedback->overall_rating)
                        <div class="text-right">
                            <div class="flex items-center justify-end mb-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= $feedback->overall_rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                @endfor
                            </div>
                            <p class="text-sm text-gray-600">{{ number_format($feedback->overall_rating, 1) }}/5.0</p>
                        </div>
                    @endif
                </div>

                <!-- Participants -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">From</p>
                        <p class="text-gray-900">{{ $feedback->reviewer_display_name }}</p>
                        <p class="text-sm text-gray-500">{{ $feedback->reviewer_type_display }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">To</p>
                        <p class="text-gray-900">{{ $feedback->reviewee->name }}</p>
                    </div>
                </div>

                <!-- Assignment Info -->
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-sm font-medium text-gray-600 mb-2">Assignment Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Opportunity</p>
                            <p class="font-medium text-gray-900">{{ $feedback->assignment->opportunity->title ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Organization</p>
                            <p class="font-medium text-gray-900">{{ $feedback->assignment->organization->name ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ratings -->
            @if($feedback->hasRatings())
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ratings</h3>
                    <div class="space-y-4">
                        @foreach($feedback->rating_categories as $category => $data)
                            @if($data['value'])
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $data['label'] }}</p>
                                        @if($data['description'])
                                            <p class="text-sm text-gray-500">{{ $data['description'] }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="flex">
                                            @for($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $i <= $data['value'] ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                                </svg>
                                            @endfor
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ number_format($data['value'], 1) }}</span>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Written Feedback -->
            @if($feedback->hasWrittenFeedback())
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Written Feedback</h3>
                    <div class="space-y-4">
                        @if($feedback->positive_feedback)
                            <div>
                                <h4 class="font-medium text-green-800 mb-2">What went well</h4>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <p class="text-green-800">{{ $feedback->positive_feedback }}</p>
                                </div>
                            </div>
                        @endif

                        @if($feedback->improvement_feedback)
                            <div>
                                <h4 class="font-medium text-orange-800 mb-2">Areas for improvement</h4>
                                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                    <p class="text-orange-800">{{ $feedback->improvement_feedback }}</p>
                                </div>
                            </div>
                        @endif

                        @if($feedback->additional_comments)
                            <div>
                                <h4 class="font-medium text-blue-800 mb-2">Additional comments</h4>
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <p class="text-blue-800">{{ $feedback->additional_comments }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Tags -->
            @if($feedback->tags && count($feedback->tags) > 0)
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tags</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($feedback->tags as $tag)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Response Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Response</h3>
                
                @if($feedback->response)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-blue-800">Response from {{ $feedback->reviewee->name }}</p>
                                <p class="text-sm text-blue-700 mt-1">{{ $feedback->response_at->format('M j, Y \a\t g:i A') }}</p>
                                <p class="text-blue-800 mt-2">{{ $feedback->response }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    @can('respond', $feedback)
                        <form action="{{ route('client.volunteering.feedback.respond', $feedback) }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label for="response" class="block text-sm font-medium text-gray-700 mb-2">
                                    Add your response
                                </label>
                                <textarea id="response" name="response" rows="4" required
                                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                          placeholder="Share your thoughts on this feedback..."></textarea>
                            </div>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Submit Response
                            </button>
                        </form>
                    @else
                        <p class="text-gray-500 italic">No response yet.</p>
                    @endcan
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                <div class="space-y-3">
                    @can('requestFollowUp', $feedback)
                        @if(!$feedback->follow_up_requested)
                            <form action="{{ route('client.volunteering.feedback.request-follow-up', $feedback) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-1">
                                        Schedule follow-up (optional)
                                    </label>
                                    <input type="datetime-local" id="scheduled_at" name="scheduled_at" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <button type="submit" 
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Request Follow-up
                                </button>
                            </form>
                        @else
                            <div class="text-center p-3 bg-green-50 border border-green-200 rounded-md">
                                <svg class="mx-auto h-6 w-6 text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-sm font-medium text-green-800">Follow-up requested</p>
                                @if($feedback->follow_up_scheduled_at)
                                    <p class="text-xs text-green-600 mt-1">
                                        Scheduled for {{ $feedback->follow_up_scheduled_at->format('M j, Y') }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    @endcan

                    <button onclick="window.print()" 
                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print Feedback
                    </button>
                </div>
            </div>

            <!-- Feedback Stats -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Feedback Statistics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Status</span>
                        <span class="text-sm font-medium text-gray-900">{{ $feedback->status_display }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Submitted</span>
                        <span class="text-sm font-medium text-gray-900">{{ $feedback->submitted_at->format('M j, Y') }}</span>
                    </div>
                    @if($feedback->reviewed_at)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Reviewed</span>
                            <span class="text-sm font-medium text-gray-900">{{ $feedback->reviewed_at->format('M j, Y') }}</span>
                        </div>
                    @endif
                    @if($feedback->response_at)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Response</span>
                            <span class="text-sm font-medium text-gray-900">{{ $feedback->response_at->format('M j, Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Related Feedback -->
            @if($feedback->assignment)
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Feedback</h3>
                    @php
                        $relatedFeedback = $feedback->assignment->feedback()
                            ->where('id', '!=', $feedback->id)
                            ->submitted()
                            ->limit(3)
                            ->get();
                    @endphp
                    
                    @if($relatedFeedback->count() > 0)
                        <div class="space-y-3">
                            @foreach($relatedFeedback as $related)
                                <div class="border border-gray-200 rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-medium text-blue-600">{{ $related->feedback_type_display }}</span>
                                        @if($related->overall_rating)
                                            <div class="flex items-center">
                                                <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                                </svg>
                                                <span class="ml-1 text-xs text-gray-600">{{ number_format($related->overall_rating, 1) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">{{ Str::limit($related->summary, 80) }}</p>
                                    <a href="{{ route('client.volunteering.feedback.show', $related) }}" 
                                       class="text-xs text-blue-600 hover:text-blue-800">View details →</a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No other feedback for this assignment.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection