@extends('layouts.app')

@section('title', 'Application Details')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('client.volunteering.applications.index') }}" 
                       class="text-gray-600 hover:text-gray-900 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Application Details</h1>
                        <p class="text-gray-600">View your volunteer application status and details</p>
                    </div>
                </div>
                
                <!-- Status Badge -->
                <div class="flex items-center space-x-3">
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'withdrawn' => 'bg-gray-100 text-gray-800'
                        ];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$application->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst($application->status) }}
                    </span>
                    
                    @if($application->status === 'pending')
                        <button onclick="withdrawApplication()" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            Withdraw Application
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Opportunity Details -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Opportunity Details</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">{{ $application->opportunity->title }}</h3>
                            <p class="text-gray-600 mt-1">{{ $application->opportunity->organization->name }}</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Category</span>
                                <p class="text-gray-900">{{ $application->opportunity->category->name }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Location</span>
                                <p class="text-gray-900">{{ $application->opportunity->location }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Duration</span>
                                <p class="text-gray-900">{{ $application->opportunity->duration }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Time Commitment</span>
                                <p class="text-gray-900">{{ $application->opportunity->time_commitment }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <span class="text-sm font-medium text-gray-500">Description</span>
                            <p class="text-gray-900 mt-1">{{ $application->opportunity->description }}</p>
                        </div>
                        
                        @if($application->opportunity->requirements)
                        <div>
                            <span class="text-sm font-medium text-gray-500">Requirements</span>
                            <p class="text-gray-900 mt-1">{{ $application->opportunity->requirements }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Application Details -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Your Application</h2>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Applied On</span>
                                <p class="text-gray-900">{{ $application->created_at->format('M d, Y') }}</p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Application ID</span>
                                <p class="text-gray-900">#{{ $application->id }}</p>
                            </div>
                        </div>
                        
                        @if($application->cover_letter)
                        <div>
                            <span class="text-sm font-medium text-gray-500">Cover Letter</span>
                            <div class="mt-2 p-4 bg-gray-50 rounded-lg">
                                <p class="text-gray-900 whitespace-pre-wrap">{{ $application->cover_letter }}</p>
                            </div>
                        </div>
                        @endif
                        
                        @if($application->availability)
                        <div>
                            <span class="text-sm font-medium text-gray-500">Availability</span>
                            <p class="text-gray-900 mt-1">{{ $application->availability }}</p>
                        </div>
                        @endif
                        
                        @if($application->experience)
                        <div>
                            <span class="text-sm font-medium text-gray-500">Relevant Experience</span>
                            <div class="mt-2 p-4 bg-gray-50 rounded-lg">
                                <p class="text-gray-900 whitespace-pre-wrap">{{ $application->experience }}</p>
                            </div>
                        </div>
                        @endif
                        
                        @if($application->skills && count($application->skills) > 0)
                        <div>
                            <span class="text-sm font-medium text-gray-500">Skills</span>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach($application->skills as $skill)
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                        {{ $skill }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Status History -->
                @if($application->statusHistory && count($application->statusHistory) > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Application Timeline</h2>
                    
                    <div class="space-y-4">
                        @foreach($application->statusHistory as $history)
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                @php
                                    $statusIcons = [
                                        'pending' => 'bg-yellow-100 text-yellow-600',
                                        'approved' => 'bg-green-100 text-green-600',
                                        'rejected' => 'bg-red-100 text-red-600',
                                        'withdrawn' => 'bg-gray-100 text-gray-600'
                                    ];
                                @endphp
                                <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $statusIcons[$history->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    Status changed to {{ ucfirst($history->status) }}
                                </p>
                                <p class="text-sm text-gray-500">{{ $history->created_at->format('M d, Y \a\t g:i A') }}</p>
                                @if($history->notes)
                                    <p class="text-sm text-gray-700 mt-1">{{ $history->notes }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Messages/Communication -->
                @if($application->messages && count($application->messages) > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Messages</h2>
                    
                    <div class="space-y-4">
                        @foreach($application->messages as $message)
                        <div class="border-l-4 {{ $message->from_admin ? 'border-blue-400 bg-blue-50' : 'border-gray-400 bg-gray-50' }} p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900">
                                    {{ $message->from_admin ? 'Organization' : 'You' }}
                                </span>
                                <span class="text-sm text-gray-500">{{ $message->created_at->format('M d, Y \a\t g:i A') }}</span>
                            </div>
                            <p class="text-gray-900">{{ $message->content }}</p>
                        </div>
                        @endforeach
                    </div>
                    
                    <!-- Reply Form -->
                    <form action="{{ route('client.volunteering.applications.message', $application) }}" method="POST" class="mt-6">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700">Send a message</label>
                                <textarea name="message" id="message" rows="3" 
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Type your message here..."></textarea>
                            </div>
                            <button type="submit" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    
                    <div class="space-y-3">
                        <a href="{{ route('client.volunteering.opportunities.show', $application->opportunity) }}" 
                           class="block w-full px-4 py-2 text-center bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            View Opportunity
                        </a>
                        
                        @if($application->status === 'approved')
                            <a href="{{ route('client.volunteering.assignments.show', $application->assignment) }}" 
                               class="block w-full px-4 py-2 text-center bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                View Assignment
                            </a>
                        @endif
                        
                        <button onclick="printApplication()" 
                                class="block w-full px-4 py-2 text-center bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            Print Application
                        </button>
                    </div>
                </div>

                <!-- Application Stats -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Application Info</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Application Date</span>
                            <span class="text-sm font-medium text-gray-900">{{ $application->created_at->format('M d, Y') }}</span>
                        </div>
                        
                        @if($application->reviewed_at)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Reviewed Date</span>
                            <span class="text-sm font-medium text-gray-900">{{ $application->reviewed_at->format('M d, Y') }}</span>
                        </div>
                        @endif
                        
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Days Since Applied</span>
                            <span class="text-sm font-medium text-gray-900">{{ $application->created_at->diffInDays(now()) }} days</span>
                        </div>
                        
                        @if($application->opportunity->application_deadline)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Application Deadline</span>
                            <span class="text-sm font-medium text-gray-900">{{ $application->opportunity->application_deadline->format('M d, Y') }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                    
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm font-medium text-gray-500">Organization</span>
                            <p class="text-gray-900">{{ $application->opportunity->organization->name }}</p>
                        </div>
                        
                        @if($application->opportunity->contact_email)
                        <div>
                            <span class="text-sm font-medium text-gray-500">Email</span>
                            <p class="text-gray-900">
                                <a href="mailto:{{ $application->opportunity->contact_email }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    {{ $application->opportunity->contact_email }}
                                </a>
                            </p>
                        </div>
                        @endif
                        
                        @if($application->opportunity->contact_phone)
                        <div>
                            <span class="text-sm font-medium text-gray-500">Phone</span>
                            <p class="text-gray-900">
                                <a href="tel:{{ $application->opportunity->contact_phone }}" 
                                   class="text-blue-600 hover:text-blue-800">
                                    {{ $application->opportunity->contact_phone }}
                                </a>
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Related Applications -->
                @if($relatedApplications && count($relatedApplications) > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Other Applications</h3>
                    
                    <div class="space-y-3">
                        @foreach($relatedApplications as $relatedApp)
                        <div class="border rounded-lg p-3">
                            <h4 class="text-sm font-medium text-gray-900">{{ $relatedApp->opportunity->title }}</h4>
                            <p class="text-xs text-gray-500">{{ $relatedApp->opportunity->organization->name }}</p>
                            <div class="flex items-center justify-between mt-2">
                                <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$relatedApp->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($relatedApp->status) }}
                                </span>
                                <a href="{{ route('client.volunteering.applications.show', $relatedApp) }}" 
                                   class="text-xs text-blue-600 hover:text-blue-800">
                                    View
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Withdraw Application Modal -->
<div id="withdrawModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Withdraw Application</h3>
                <p class="text-gray-600 mb-6">Are you sure you want to withdraw your application? This action cannot be undone.</p>
                
                <form action="{{ route('client.volunteering.applications.withdraw', $application) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="mb-4">
                        <label for="withdrawal_reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Reason for withdrawal (optional)
                        </label>
                        <textarea name="withdrawal_reason" id="withdrawal_reason" rows="3" 
                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                                  placeholder="Please let us know why you're withdrawing..."></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeWithdrawModal()" 
                                class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            Withdraw Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function withdrawApplication() {
    document.getElementById('withdrawModal').classList.remove('hidden');
}

function closeWithdrawModal() {
    document.getElementById('withdrawModal').classList.add('hidden');
}

function printApplication() {
    window.print();
}

// Close modal when clicking outside
document.getElementById('withdrawModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeWithdrawModal();
    }
});
</script>
@endpush

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    .print-break {
        page-break-before: always;
    }
}
</style>
@endpush