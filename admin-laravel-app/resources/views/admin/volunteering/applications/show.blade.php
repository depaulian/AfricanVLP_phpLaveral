@extends('layouts.admin')

@section('title', 'Application Details - ' . $application->user->name)

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.volunteering.applications.index') }}" 
                       class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Application Details</h1>
                        <p class="text-gray-600 mt-1">{{ $application->user->name }} - {{ $application->opportunity->title }}</p>
                    </div>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    @if($application->status === 'pending')
                        <button onclick="showAcceptModal()" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Accept Application
                        </button>
                        <button onclick="showRejectModal()" 
                                class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                            Reject Application
                        </button>
                    @endif
                    
                    @if($application->status === 'accepted' && !$application->assignment)
                        <button onclick="showAssignmentModal()" 
                                class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-md transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            Create Assignment
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Application Status -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Application Status</h2>
                        @if($application->status === 'pending')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                </svg>
                                Pending Review
                            </span>
                        @elseif($application->status === 'accepted')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Accepted
                            </span>
                        @elseif($application->status === 'rejected')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                Rejected
                            </span>
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Applied Date</p>
                            <p class="font-medium">{{ $application->applied_at->format('M j, Y \a\t g:i A') }}</p>
                        </div>
                        @if($application->reviewed_at)
                            <div>
                                <p class="text-sm text-gray-600">Reviewed Date</p>
                                <p class="font-medium">{{ $application->reviewed_at->format('M j, Y \a\t g:i A') }}</p>
                            </div>
                        @endif
                        @if($application->reviewer)
                            <div>
                                <p class="text-sm text-gray-600">Reviewed By</p>
                                <p class="font-medium">{{ $application->reviewer->name }}</p>
                            </div>
                        @endif
                        @if(isset($application->match_score))
                            <div>
                                <p class="text-sm text-gray-600">Match Score</p>
                                <div class="flex items-center">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $application->match_score }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium">{{ round($application->match_score) }}%</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    @if($application->reviewer_notes)
                        <div class="mt-4 p-4 bg-gray-50 rounded-md">
                            <p class="text-sm text-gray-600 mb-1">Reviewer Notes</p>
                            <p class="text-gray-900">{{ $application->reviewer_notes }}</p>
                        </div>
                    @endif
                </div>

                <!-- Application Details -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Application Details</h2>
                    
                    <div class="space-y-6">
                        @if($application->motivation)
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Motivation</h3>
                                <p class="text-gray-900 leading-relaxed">{{ $application->motivation }}</p>
                            </div>
                        @endif
                        
                        @if($application->relevant_experience)
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Relevant Experience</h3>
                                <p class="text-gray-900 leading-relaxed">{{ $application->relevant_experience }}</p>
                            </div>
                        @endif
                        
                        @if($application->availability)
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Availability</h3>
                                @if(is_array($application->availability))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($application->availability as $day)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $day }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-900">{{ $application->availability }}</p>
                                @endif
                            </div>
                        @endif
                        
                        @if($application->emergency_contact_name)
                            <div>
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Emergency Contact</h3>
                                <p class="text-gray-900">
                                    {{ $application->emergency_contact_name }}
                                    @if($application->emergency_contact_phone)
                                        - {{ $application->emergency_contact_phone }}
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Assignment Details -->
                @if($application->assignment)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Assignment Details</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Start Date</p>
                                <p class="font-medium">{{ $application->assignment->start_date->format('M j, Y') }}</p>
                            </div>
                            @if($application->assignment->end_date)
                                <div>
                                    <p class="text-sm text-gray-600">End Date</p>
                                    <p class="font-medium">{{ $application->assignment->end_date->format('M j, Y') }}</p>
                                </div>
                            @endif
                            @if($application->assignment->hours_committed)
                                <div>
                                    <p class="text-sm text-gray-600">Hours Committed</p>
                                    <p class="font-medium">{{ $application->assignment->hours_committed }} hours</p>
                                </div>
                            @endif
                            @if($application->assignment->supervisor)
                                <div>
                                    <p class="text-sm text-gray-600">Supervisor</p>
                                    <p class="font-medium">{{ $application->assignment->supervisor->name }}</p>
                                </div>
                            @endif
                        </div>
                        
                        @if($application->assignment->timeLogs->count() > 0)
                            <div class="mt-6">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Recent Time Logs</h3>
                                <div class="space-y-2">
                                    @foreach($application->assignment->timeLogs->take(5) as $log)
                                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-md">
                                            <div>
                                                <p class="font-medium">{{ $log->date->format('M j, Y') }}</p>
                                                <p class="text-sm text-gray-600">{{ $log->hours_logged }} hours</p>
                                            </div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $log->status === 'approved' ? 'bg-green-100 text-green-800' : 
                                                   ($log->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ ucfirst($log->status) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                                
                                @if($application->assignment->timeLogs->count() > 5)
                                    <div class="mt-3 text-center">
                                        <a href="{{ route('admin.volunteering.assignments.show', $application->assignment) }}" 
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            View All Time Logs ({{ $application->assignment->timeLogs->count() }})
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Application Timeline -->
                @if(isset($timeline) && count($timeline) > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Application Timeline</h2>
                        
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @foreach($timeline as $index => $event)
                                    <li>
                                        <div class="relative pb-8">
                                            @if($index < count($timeline) - 1)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white
                                                        {{ $event['type'] === 'applied' ? 'bg-blue-500' : 
                                                           ($event['type'] === 'accepted' ? 'bg-green-500' : 
                                                           ($event['type'] === 'rejected' ? 'bg-red-500' : 'bg-gray-500')) }}">
                                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            @if($event['type'] === 'applied')
                                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                            @elseif($event['type'] === 'accepted')
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            @elseif($event['type'] === 'rejected')
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                            @endif
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-900">{{ $event['description'] }}</p>
                                                        @if(isset($event['user']))
                                                            <p class="text-xs text-gray-500">by {{ $event['user'] }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        {{ $event['date']->format('M j, Y') }}
                                                        <div class="text-xs">{{ $event['date']->format('g:i A') }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                <!-- Volunteer Profile -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Volunteer Profile</h2>
                    
                    <div class="flex items-center space-x-4 mb-4">
                        <img class="h-16 w-16 rounded-full" 
                             src="{{ $application->user->profile_image ?? '/images/default-avatar.png' }}" 
                             alt="{{ $application->user->name }}">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $application->user->name }}</h3>
                            <p class="text-gray-600">{{ $application->user->email }}</p>
                            @if($application->user->phone_number)
                                <p class="text-gray-600">{{ $application->user->phone_number }}</p>
                            @endif
                        </div>
                    </div>
                    
                    @if($application->user->city || $application->user->country)
                        <div class="mb-4">
                            <p class="text-sm text-gray-600">Location</p>
                            <p class="font-medium">
                                @if($application->user->city)
                                    {{ $application->user->city->name }}
                                    @if($application->user->country), {{ $application->user->country->name }}@endif
                                @elseif($application->user->country)
                                    {{ $application->user->country->name }}
                                @endif
                            </p>
                        </div>
                    @endif
                    
                    @if($application->user->skills->count() > 0)
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Skills</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($application->user->skills as $skill)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $skill->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    @if($application->user->volunteeringInterests->count() > 0)
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 mb-2">Interests</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($application->user->volunteeringInterests as $interest)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $interest->category->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <div class="pt-4 border-t">
                        <a href="{{ route('admin.users.show', $application->user) }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View Full Profile →
                        </a>
                    </div>
                </div>

                <!-- Opportunity Details -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Opportunity Details</h2>
                    
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">Title</p>
                            <p class="font-medium">{{ $application->opportunity->title }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Organization</p>
                            <p class="font-medium">{{ $application->opportunity->organization->name }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm text-gray-600">Category</p>
                            <p class="font-medium">{{ $application->opportunity->category->name }}</p>
                        </div>
                        
                        @if($application->opportunity->role)
                            <div>
                                <p class="text-sm text-gray-600">Role</p>
                                <p class="font-medium">{{ $application->opportunity->role->name }}</p>
                            </div>
                        @endif
                        
                        <div>
                            <p class="text-sm text-gray-600">Duration</p>
                            <p class="font-medium">
                                {{ $application->opportunity->start_date->format('M j, Y') }} - 
                                {{ $application->opportunity->end_date->format('M j, Y') }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t">
                        <a href="{{ route('admin.volunteering.opportunities.show', $application->opportunity) }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View Opportunity →
                        </a>
                    </div>
                </div>

                <!-- Other Applications -->
                @if($otherApplications->count() > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Other Applications</h2>
                        
                        <div class="space-y-3">
                            @foreach($otherApplications as $otherApp)
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">{{ $otherApp->opportunity->title }}</p>
                                        <p class="text-xs text-gray-500">{{ $otherApp->applied_at->format('M j, Y') }}</p>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $otherApp->status === 'accepted' ? 'bg-green-100 text-green-800' : 
                                           ($otherApp->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        {{ ucfirst($otherApp->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Accept Application Modal -->
<div id="acceptModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Accept Application</h3>
                <button onclick="closeAcceptModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" action="{{ route('admin.volunteering.applications.accept', $application) }}">
                @csrf
                
                <div class="mb-4">
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date *</label>
                    <input type="date" name="start_date" id="start_date" required 
                           min="{{ date('Y-m-d') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" id="end_date" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="hours_committed" class="block text-sm font-medium text-gray-700 mb-2">Hours Committed</label>
                    <input type="number" name="hours_committed" id="hours_committed" min="1" max="1000"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="supervisor_id" class="block text-sm font-medium text-gray-700 mb-2">Supervisor</label>
                    <select name="supervisor_id" id="supervisor_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Supervisor</option>
                        @foreach($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" id="notes" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Optional notes for the volunteer..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAcceptModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition-colors">
                        Accept Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Application Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Reject Application</h3>
                <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form method="POST" action="{{ route('admin.volunteering.applications.reject', $application) }}">
                @csrf
                
                <div class="mb-4">
                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700 mb-2">Reason for Rejection *</label>
                    <textarea name="rejection_reason" id="rejection_reason" rows="4" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Please provide a reason for rejecting this application..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRejectModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                        Reject Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Accept modal functions
function showAcceptModal() {
    document.getElementById('acceptModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeAcceptModal() {
    document.getElementById('acceptModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Reject modal functions
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modals when clicking outside
document.getElementById('acceptModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeAcceptModal();
    }
});

document.getElementById('rejectModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeRejectModal();
    }
});

// Set minimum end date when start date changes
document.getElementById('start_date')?.addEventListener('change', function() {
    const endDateInput = document.getElementById('end_date');
    if (endDateInput) {
        endDateInput.min = this.value;
    }
});
</script>
@endpush
@endsection