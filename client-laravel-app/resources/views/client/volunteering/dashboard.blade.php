@extends('layouts.client')

@section('title', 'Volunteer Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Volunteer Dashboard</h1>
                    <p class="text-gray-600 mt-1">Track your volunteer activities and applications</p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <a href="{{ route('client.volunteering.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        Find Opportunities
                    </a>
                    @if($activeAssignments->count() > 0)
                        <a href="#log-hours-modal" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-md transition-colors"
                           onclick="openLogHoursModal()">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                            </svg>
                            Log Hours
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Applications</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['total_assignments'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Assignments</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['active_assignments'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100">
                        <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Hours Logged</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['total_approved_hours'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100">
                        <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Completed Projects</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['completed_assignments'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Recent Applications -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Recent Applications</h2>
                            <a href="{{ route('client.volunteering.applications') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        @if($recentApplications->count() > 0)
                            <div class="space-y-4">
                                @foreach($recentApplications as $application)
                                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900">
                                                <a href="{{ route('client.volunteering.show', $application->opportunity) }}" class="hover:text-blue-600">
                                                    {{ $application->opportunity->title }}
                                                </a>
                                            </h3>
                                            <p class="text-sm text-gray-600">{{ $application->opportunity->organization->name }}</p>
                                            <p class="text-xs text-gray-500 mt-1">Applied {{ $application->applied_at->diffForHumans() }}</p>
                                            @if($application->status === 'accepted' && $application->assignment)
                                                <div class="mt-2">
                                                    <a href="{{ route('client.volunteering.assignment', $application->assignment) }}" 
                                                       class="text-xs text-green-600 hover:text-green-800 font-medium">
                                                        View Assignment →
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-4 flex flex-col items-end">
                                            @if($application->status === 'pending')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    Pending
                                                </span>
                                            @elseif($application->status === 'accepted')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Accepted
                                                </span>
                                            @elseif($application->status === 'rejected')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Rejected
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.5-.816-6.207-2.175.168-.106.34-.215.517-.327C7.229 11.708 8.549 11 10 11h4c1.451 0 2.771.708 3.69 1.498.177.112.349.221.517.327A7.962 7.962 0 0112 15z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No applications yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Start by applying to volunteer opportunities.</p>
                                <div class="mt-6">
                                    <a href="{{ route('client.volunteering.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        Browse Opportunities
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Active Assignments -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Active Assignments</h2>
                            <a href="{{ route('client.volunteering.assignments') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="p-6">
                        @if($activeAssignments->count() > 0)
                            <div class="space-y-4">
                                @foreach($activeAssignments as $assignment)
                                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h3 class="font-medium text-gray-900">
                                                    <a href="{{ route('client.volunteering.assignment', $assignment) }}" class="hover:text-blue-600">
                                                        {{ $assignment->application->opportunity->title }}
                                                    </a>
                                                </h3>
                                                <p class="text-sm text-gray-600">{{ $assignment->application->opportunity->organization->name }}</p>
                                                <div class="flex items-center mt-2 space-x-4">
                                                    <span class="text-xs text-gray-500">
                                                        Started {{ $assignment->start_date->format('M j, Y') }}
                                                    </span>
                                                    @if($assignment->hours_committed)
                                                        <span class="text-xs text-gray-500">
                                                            {{ $assignment->hours_completed }}/{{ $assignment->hours_committed }} hours
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($assignment->supervisor)
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        Supervisor: {{ $assignment->supervisor->name }}
                                                    </p>
                                                @endif
                                            </div>
                                            <div class="ml-4 flex flex-col items-end space-y-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                                <button onclick="openLogHoursModal({{ $assignment->id }})" 
                                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                    Log Hours
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Progress Bar -->
                                        @if($assignment->hours_committed)
                                            <div class="mt-3">
                                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                    <span>Progress</span>
                                                    <span>{{ round(($assignment->hours_completed / $assignment->hours_committed) * 100) }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" 
                                                         style="width: {{ min(($assignment->hours_completed / $assignment->hours_committed) * 100, 100) }}%"></div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No active assignments</h3>
                                <p class="mt-1 text-sm text-gray-500">Apply to opportunities to get started with volunteering.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Time Logs -->
                @if($recentTimeLogs->count() > 0)
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900">Recent Time Logs</h2>
                                <a href="{{ route('client.volunteering.time-logs') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    View All
                                </a>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                @foreach($recentTimeLogs as $timeLog)
                                    <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $timeLog->assignment->application->opportunity->title }}
                                            </p>
                                            <p class="text-xs text-gray-600">
                                                {{ $timeLog->date->format('M j, Y') }} • {{ $timeLog->hours }} hours
                                            </p>
                                            @if($timeLog->activity_description)
                                                <p class="text-xs text-gray-500 mt-1">{{ Str::limit($timeLog->activity_description, 60) }}</p>
                                            @endif
                                        </div>
                                        <div class="ml-4">
                                            @if($timeLog->supervisor_approved)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Approved
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Pending
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('client.volunteering.index') }}" 
                           class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            Find Opportunities
                        </a>
                        <a href="{{ route('client.profile.edit') }}" 
                           class="block w-full text-center bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            Update Profile
                        </a>
                        @if($activeAssignments->count() > 0)
                            <button onclick="openLogHoursModal()" 
                                    class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                                Log Hours
                            </button>
                        @endif
                        <a href="{{ route('client.volunteering.certificates') }}" 
                           class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            My Certificates
                        </a>
                    </div>
                </div>

                <!-- Volunteer Impact -->
                @if($statistics['total_approved_hours'] > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Your Impact</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Hours</span>
                                <span class="font-medium">{{ number_format($statistics['total_approved_hours']) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Organizations Served</span>
                                <span class="font-medium">{{ $statistics['organizations_served'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Categories</span>
                                <span class="font-medium">{{ $statistics['categories_participated'] ?? 0 }}</span>
                            </div>
                        </div>

                        <!-- Impact Visualization -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ $statistics['total_approved_hours'] ?? 0 }}</div>
                                <div class="text-sm text-gray-600">Hours of Impact</div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Recommended Opportunities -->
                @if($matchingOpportunities->count() > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recommended for You</h3>
                        <div class="space-y-3">
                            @foreach($matchingOpportunities->take(3) as $opportunity)
                                <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition-colors">
                                    <h4 class="font-medium text-gray-900 text-sm mb-1">
                                        <a href="{{ route('client.volunteering.show', $opportunity) }}" class="hover:text-blue-600">
                                            {{ Str::limit($opportunity->title, 40) }}
                                        </a>
                                    </h4>
                                    <p class="text-xs text-gray-600 mb-2">{{ $opportunity->organization->name }}</p>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-500">
                                            {{ $opportunity->location_type === 'remote' ? 'Remote' : ($opportunity->city ? $opportunity->city->name : 'Location TBD') }}
                                        </span>
                                        <a href="{{ route('client.volunteering.show', $opportunity) }}" 
                                           class="text-xs text-blue-600 hover:text-blue-800 font-medium">View →</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('client.volunteering.index') }}" 
                               class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                View All Opportunities →
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Pending Actions -->
                @php
                    $pendingActions = [];
                    if($recentApplications->where('status', 'pending')->count() > 0) {
                        $pendingActions[] = [
                            'type' => 'applications',
                            'count' => $recentApplications->where('status', 'pending')->count(),
                            'message' => 'applications pending review'
                        ];
                    }
                    
                    $needsHoursLogged = $activeAssignments->filter(function($assignment) {
                        return $assignment->timeLogs()->where('created_at', '>=', now()->subWeek())->count() === 0;
                    });
                    
                    if($needsHoursLogged->count() > 0) {
                        $pendingActions[] = [
                            'type' => 'hours',
                            'count' => $needsHoursLogged->count(),
                            'message' => 'assignments need hour logging'
                        ];
                    }
                @endphp

                @if(count($pendingActions) > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Pending Actions</h3>
                        <div class="space-y-3">
                            @foreach($pendingActions as $action)
                                <div class="flex items-center text-sm">
                                    <div class="w-2 h-2 {{ $action['type'] === 'applications' ? 'bg-yellow-400' : 'bg-orange-400' }} rounded-full mr-3"></div>
                                    <span class="text-gray-700">{{ $action['count'] }} {{ $action['message'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        
                        @if($activeAssignments->count() > 0)
                            <div class="mt-4">
                                <button onclick="openLogHoursModal()" 
                                        class="w-full text-center bg-orange-600 hover:bg-orange-700 text-white font-medium py-2 px-4 rounded-md transition-colors text-sm">
                                    Log Hours Now
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Profile Completion -->
                @php
                    $profileCompletion = 0;
                    $user = auth()->user();
                    
                    if($user->name) $profileCompletion += 20;
                    if($user->email) $profileCompletion += 20;
                    if($user->volunteeringInterests()->count() > 0) $profileCompletion += 30;
                    if($user->skills()->count() > 0) $profileCompletion += 30;
                @endphp

                @if($profileCompletion < 100)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Complete Your Profile</h3>
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Profile Completion</span>
                                <span>{{ $profileCompletion }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full" style="width: {{ $profileCompletion }}%"></div>
                            </div>
                        </div>
                        
                        <div class="space-y-2 text-sm">
                            @if($user->volunteeringInterests()->count() === 0)
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add volunteering interests
                                </div>
                            @endif
                            
                            @if($user->skills()->count() === 0)
                                <div class="flex items-center text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add your skills
                                </div>
                            @endif
                        </div>
                        
                        <div class="mt-4">
                            <a href="{{ route('client.profile.edit') }}" 
                               class="w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors text-sm block">
                                Complete Profile
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Log Hours Modal -->
@if($activeAssignments->count() > 0)
    <div id="logHoursModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Log Volunteer Hours</h3>
                    <button onclick="closeLogHoursModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="logHoursForm" method="POST" action="{{ route('client.volunteering.log-hours') }}">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="assignment_id" class="block text-sm font-medium text-gray-700 mb-2">Assignment</label>
                        <select name="assignment_id" id="assignment_id" required 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($activeAssignments as $assignment)
                                <option value="{{ $assignment->id }}">
                                    {{ $assignment->application->opportunity->title }} - {{ $assignment->application->opportunity->organization->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                        <input type="date" name="date" id="date" required max="{{ date('Y-m-d') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">Start Time</label>
                            <input type="time" name="start_time" id="start_time" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">End Time</label>
                            <input type="time" name="end_time" id="end_time" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="activity_description" class="block text-sm font-medium text-gray-700 mb-2">Activity Description</label>
                        <textarea name="activity_description" id="activity_description" rows="3" 
                                  placeholder="Describe what you worked on during this time..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeLogHoursModal()" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                            Log Hours
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@push('scripts')
<script>
function openLogHoursModal(assignmentId = null) {
    const modal = document.getElementById('logHoursModal');
    const assignmentSelect = document.getElementById('assignment_id');
    const dateInput = document.getElementById('date');
    
    // Set today's date as default
    dateInput.value = new Date().toISOString().split('T')[0];
    
    // If specific assignment ID provided, select it
    if (assignmentId) {
        assignmentSelect.value = assignmentId;
    }
    
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLogHoursModal() {
    const modal = document.getElementById('logHoursModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('logHoursForm').reset();
}

// Close modal when clicking outside
document.getElementById('logHoursModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeLogHoursModal();
    }
});

// Calculate hours automatically
document.getElementById('start_time')?.addEventListener('change', calculateHours);
document.getElementById('end_time')?.addEventListener('change', calculateHours);

function calculateHours() {
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (startTime && endTime) {
        const start = new Date(`2000-01-01T${startTime}`);
        const end = new Date(`2000-01-01T${endTime}`);
        
        if (end > start) {
            const hours = (end - start) / (1000 * 60 * 60);
            console.log(`Hours calculated: ${hours.toFixed(2)}`);
        }
    }
}

// Auto-refresh dashboard data every 5 minutes
setInterval(function() {
    // Only refresh if user is active (not idle)
    if (document.hasFocus()) {
        window.location.reload();
    }
}, 300000); // 5 minutes

// Show success message if redirected with success
@if(session('success'))
    // You can integrate with your notification system here
    console.log('Success: {{ session('success') }}');
@endif

@if(session('error'))
    // You can integrate with your notification system here
    console.log('Error: {{ session('error') }}');
@endif
</script>
@endpush
@endsection