@extends('layouts.app')

@section('title', 'My Volunteer Hours')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">My Volunteer Hours</h1>
                    <p class="text-gray-600">Track and manage your volunteer time</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Total Approved Hours</p>
                        <p class="text-2xl font-bold text-green-600">{{ number_format($totalApprovedHours, 1) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Pending Approval</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ number_format($pendingHours, 1) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Active Assignments -->
        @if($activeAssignments->isNotEmpty())
            <div class="bg-white rounded-lg shadow-sm border mb-8">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Active Assignments</h2>
                    <p class="text-gray-600">Log hours for your current volunteer assignments</p>
                </div>
                <div class="divide-y">
                    @foreach($activeAssignments as $assignment)
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="font-medium text-gray-900">{{ $assignment->opportunity->title }}</h3>
                                    <p class="text-sm text-gray-600">{{ $assignment->opportunity->organization->name }}</p>
                                    <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                        <span>Started: {{ $assignment->start_date->format('M d, Y') }}</span>
                                        @if($assignment->hours_committed)
                                            <span>Committed: {{ $assignment->hours_committed }} hours</span>
                                        @endif
                                        <span>Logged: {{ number_format($assignment->total_hours_logged, 1) }} hours</span>
                                        <span>Approved: {{ number_format($assignment->total_approved_hours, 1) }} hours</span>
                                    </div>
                                </div>
                                <button onclick="openLogHoursModal({{ $assignment->id }})" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Log Hours
                                </button>
                            </div>
                            
                            <!-- Recent Time Logs -->
                            @if($assignment->timeLogs->isNotEmpty())
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Entries</h4>
                                    <div class="space-y-2">
                                        @foreach($assignment->timeLogs->take(3) as $log)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                                <div class="flex items-center space-x-4">
                                                    <div class="text-sm">
                                                        <span class="font-medium">{{ $log->date->format('M d, Y') }}</span>
                                                        <span class="text-gray-500">{{ $log->start_time->format('g:i A') }} - {{ $log->end_time->format('g:i A') }}</span>
                                                    </div>
                                                    <div class="text-sm text-gray-600">
                                                        {{ number_format($log->hours, 1) }} hours
                                                    </div>
                                                    @if($log->activity_description)
                                                        <div class="text-sm text-gray-500 truncate max-w-xs">
                                                            {{ $log->activity_description }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    @if($log->supervisor_approved)
                                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                                            Approved
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                                            Pending
                                                        </span>
                                                    @endif
                                                    @if(!$log->supervisor_approved)
                                                        <button onclick="editTimeLog({{ $log->id }})" 
                                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                                            Edit
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if($assignment->timeLogs->count() > 3)
                                        <button onclick="viewAllLogs({{ $assignment->id }})" 
                                                class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                            View all {{ $assignment->timeLogs->count() }} entries →
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Time Logs History -->
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Time Logs History</h2>
                        <p class="text-gray-600">All your volunteer time entries</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Filter Controls -->
                        <select id="assignment-filter" class="border-gray-300 rounded-md text-sm">
                            <option value="">All Assignments</option>
                            @foreach($allAssignments as $assignment)
                                <option value="{{ $assignment->id }}">{{ $assignment->opportunity->title }}</option>
                            @endforeach
                        </select>
                        <select id="status-filter" class="border-gray-300 rounded-md text-sm">
                            <option value="">All Status</option>
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                        </select>
                        <button onclick="exportTimeLogs()" class="px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                            Export CSV
                        </button>
                    </div>
                </div>
            </div>
            
            @if($timeLogs->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($timeLogs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $log->date->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div>
                                            <div class="font-medium">{{ $log->assignment->opportunity->title }}</div>
                                            <div class="text-gray-500">{{ $log->assignment->opportunity->organization->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $log->start_time->format('g:i A') }} - {{ $log->end_time->format('g:i A') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ number_format($log->hours, 1) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                        {{ $log->activity_description ?: 'No description' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($log->supervisor_approved)
                                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                                Approved
                                            </span>
                                            @if($log->approved_at)
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ $log->approved_at->format('M d, Y') }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                                Pending Approval
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if(!$log->supervisor_approved)
                                            <div class="flex items-center space-x-2">
                                                <button onclick="editTimeLog({{ $log->id }})" 
                                                        class="text-blue-600 hover:text-blue-800">
                                                    Edit
                                                </button>
                                                <button onclick="deleteTimeLog({{ $log->id }})" 
                                                        class="text-red-600 hover:text-red-800">
                                                    Delete
                                                </button>
                                            </div>
                                        @else
                                            <button onclick="viewTimeLogDetails({{ $log->id }})" 
                                                    class="text-gray-600 hover:text-gray-800">
                                                View
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 border-t">
                    {{ $timeLogs->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Time Logs Yet</h3>
                    <p class="text-gray-600 mb-4">Start logging your volunteer hours to track your contributions.</p>
                    @if($activeAssignments->isNotEmpty())
                        <button onclick="openLogHoursModal({{ $activeAssignments->first()->id }})" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Log Your First Hours
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Log Hours Modal -->
<div id="logHoursModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Log Volunteer Hours</h3>
                    <button onclick="closeLogHoursModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <form id="logHoursForm" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" name="date" id="date" required max="{{ date('Y-m-d') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                                <input type="time" name="start_time" id="start_time" required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                                <input type="time" name="end_time" id="end_time" required
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label for="activity_description" class="block text-sm font-medium text-gray-700 mb-1">Activity Description</label>
                            <textarea name="activity_description" id="activity_description" rows="3"
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Describe what you did during this volunteer session..."></textarea>
                        </div>
                        
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <strong>Note:</strong> Your logged hours will need supervisor approval before being officially recorded.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeLogHoursModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Log Hours
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentAssignmentId = null;

function openLogHoursModal(assignmentId) {
    currentAssignmentId = assignmentId;
    document.getElementById('logHoursForm').action = `/volunteering/assignments/${assignmentId}/log-hours`;
    document.getElementById('date').value = new Date().toISOString().split('T')[0];
    document.getElementById('logHoursModal').classList.remove('hidden');
}

function closeLogHoursModal() {
    document.getElementById('logHoursModal').classList.add('hidden');
    document.getElementById('logHoursForm').reset();
    currentAssignmentId = null;
}

function editTimeLog(logId) {
    // Implementation for editing time logs
    window.location.href = `/volunteering/time-logs/${logId}/edit`;
}

function deleteTimeLog(logId) {
    if (confirm('Are you sure you want to delete this time log entry?')) {
        fetch(`/volunteering/time-logs/${logId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        }).then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Failed to delete time log entry.');
            }
        });
    }
}

function exportTimeLogs() {
    const assignmentFilter = document.getElementById('assignment-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    
    let url = '/volunteering/time-logs/export?';
    if (assignmentFilter) url += `assignment=${assignmentFilter}&`;
    if (statusFilter) url += `status=${statusFilter}&`;
    
    window.location.href = url;
}

// Auto-calculate hours when times change
document.addEventListener('DOMContentLoaded', function() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    function updateHours() {
        if (startTimeInput.value && endTimeInput.value) {
            const start = new Date(`2000-01-01T${startTimeInput.value}`);
            const end = new Date(`2000-01-01T${endTimeInput.value}`);
            
            if (end > start) {
                const hours = (end - start) / (1000 * 60 * 60);
                // You could display calculated hours here if needed
            }
        }
    }
    
    startTimeInput.addEventListener('change', updateHours);
    endTimeInput.addEventListener('change', updateHours);
});

// Filter functionality
document.getElementById('assignment-filter').addEventListener('change', function() {
    applyFilters();
});

document.getElementById('status-filter').addEventListener('change', function() {
    applyFilters();
});

function applyFilters() {
    const assignmentFilter = document.getElementById('assignment-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    
    let url = new URL(window.location);
    
    if (assignmentFilter) {
        url.searchParams.set('assignment', assignmentFilter);
    } else {
        url.searchParams.delete('assignment');
    }
    
    if (statusFilter) {
        url.searchParams.set('status', statusFilter);
    } else {
        url.searchParams.delete('status');
    }
    
    window.location.href = url.toString();
}
</script>
@endpush
@endsection