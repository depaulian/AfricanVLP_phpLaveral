@extends('layouts.app')

@section('title', 'Edit Time Log')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center space-x-4">
                <a href="{{ route('client.volunteering.time-logs.index') }}" 
                   class="text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit Time Log</h1>
                    <p class="text-gray-600">Update your volunteer time entry</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <!-- Assignment Info -->
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Assignment Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Opportunity</p>
                        <p class="font-medium text-gray-900">{{ $timeLog->assignment->opportunity->title }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Organization</p>
                        <p class="font-medium text-gray-900">{{ $timeLog->assignment->opportunity->organization->name }}</p>
                    </div>
                    @if($timeLog->assignment->supervisor)
                        <div>
                            <p class="text-sm text-gray-600">Supervisor</p>
                            <p class="font-medium text-gray-900">{{ $timeLog->assignment->supervisor->name }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-sm text-gray-600">Assignment Status</p>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-sm rounded-full">
                            {{ ucfirst($timeLog->assignment->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">Time Log Details</h2>
                
                <form action="{{ route('client.volunteering.time-logs.update', $timeLog) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                                Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="date" id="date" required 
                                   value="{{ old('date', $timeLog->date->format('Y-m-d')) }}"
                                   max="{{ date('Y-m-d') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('date') border-red-500 @enderror">
                            @error('date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                                Start Time <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="start_time" id="start_time" required
                                   value="{{ old('start_time', $timeLog->start_time->format('H:i')) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('start_time') border-red-500 @enderror">
                            @error('start_time')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-2">
                                End Time <span class="text-red-500">*</span>
                            </label>
                            <input type="time" name="end_time" id="end_time" required
                                   value="{{ old('end_time', $timeLog->end_time->format('H:i')) }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('end_time') border-red-500 @enderror">
                            @error('end_time')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Calculated Hours Display -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Calculated Hours:</span>
                            <span id="calculated-hours" class="text-lg font-semibold text-blue-600">
                                {{ number_format($timeLog->hours, 1) }}
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <label for="activity_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Activity Description
                        </label>
                        <textarea name="activity_description" id="activity_description" rows="4"
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('activity_description') border-red-500 @enderror"
                                  placeholder="Describe what you did during this volunteer session...">{{ old('activity_description', $timeLog->activity_description) }}</textarea>
                        @error('activity_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">
                            Providing detailed descriptions helps supervisors understand your contributions.
                        </p>
                    </div>
                    
                    <!-- Current Status Info -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <h4 class="text-sm font-medium text-yellow-800">Current Status: Pending Approval</h4>
                                <p class="text-sm text-yellow-700 mt-1">
                                    This time log entry is waiting for supervisor approval. You can edit it until it's approved.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center pt-6 border-t">
                        <button type="button" onclick="confirmDelete()" 
                                class="px-4 py-2 text-red-600 border border-red-300 rounded-md hover:bg-red-50 transition-colors">
                            Delete Entry
                        </button>
                        
                        <div class="flex space-x-3">
                            <a href="{{ route('client.volunteering.time-logs.index') }}" 
                               class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Update Time Log
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Time Log History for this Assignment -->
            @if($timeLog->assignment->timeLogs->count() > 1)
                <div class="bg-white rounded-lg shadow-sm border p-6 mt-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Other Time Logs for This Assignment</h2>
                    <div class="space-y-3">
                        @foreach($timeLog->assignment->timeLogs->where('id', '!=', $timeLog->id)->take(5) as $otherLog)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <div class="text-sm">
                                        <span class="font-medium">{{ $otherLog->date->format('M d, Y') }}</span>
                                        <span class="text-gray-500">{{ $otherLog->start_time->format('g:i A') }} - {{ $otherLog->end_time->format('g:i A') }}</span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ number_format($otherLog->hours, 1) }} hours
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($otherLog->supervisor_approved)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                            Approved
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                            Pending
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <svg class="w-6 h-6 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900">Delete Time Log Entry</h3>
                </div>
                
                <p class="text-gray-600 mb-6">
                    Are you sure you want to delete this time log entry? This action cannot be undone.
                </p>
                
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button onclick="deleteTimeLog()" 
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Delete Entry
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-calculate hours when times change
function updateCalculatedHours() {
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (startTime && endTime) {
        const start = new Date(`2000-01-01T${startTime}`);
        const end = new Date(`2000-01-01T${endTime}`);
        
        if (end > start) {
            const hours = (end - start) / (1000 * 60 * 60);
            document.getElementById('calculated-hours').textContent = hours.toFixed(1);
        } else {
            document.getElementById('calculated-hours').textContent = '0.0';
        }
    }
}

document.getElementById('start_time').addEventListener('change', updateCalculatedHours);
document.getElementById('end_time').addEventListener('change', updateCalculatedHours);

// Delete confirmation
function confirmDelete() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

function deleteTimeLog() {
    fetch(`{{ route('client.volunteering.time-logs.destroy', $timeLog) }}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    }).then(response => {
        if (response.ok) {
            window.location.href = '{{ route('client.volunteering.time-logs.index') }}';
        } else {
            alert('Failed to delete time log entry.');
            closeDeleteModal();
        }
    }).catch(error => {
        alert('An error occurred while deleting the time log entry.');
        closeDeleteModal();
    });
}

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    
    if (startTime && endTime) {
        const start = new Date(`2000-01-01T${startTime}`);
        const end = new Date(`2000-01-01T${endTime}`);
        
        if (end <= start) {
            e.preventDefault();
            alert('End time must be after start time.');
            return false;
        }
        
        const hours = (end - start) / (1000 * 60 * 60);
        if (hours > 24) {
            e.preventDefault();
            alert('Time log cannot exceed 24 hours.');
            return false;
        }
    }
});

// Initialize calculated hours on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCalculatedHours();
});
</script>
@endpush
@endsection