@extends('layouts.client')

@section('title', 'Volunteer Connections')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Volunteer Connections</h1>
        <p class="text-gray-600">Build meaningful relationships with fellow volunteers</p>
    </div>

    <!-- Connection Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $connectionStats['total_connections'] }}</div>
            <div class="text-sm text-gray-600">Total Connections</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $connectionStats['active_connections'] }}</div>
            <div class="text-sm text-gray-600">Active This Month</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ $connectionStats['pending_received'] }}</div>
            <div class="text-sm text-gray-600">Pending Requests</div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $connectionStats['total_interactions'] }}</div>
            <div class="text-sm text-gray-600">Total Interactions</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Pending Requests -->
            @if($pendingRequests->count() > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Pending Connection Requests</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($pendingRequests as $request)
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <img class="w-12 h-12 rounded-full" src="{{ $request->requester->avatar ?? '/images/default-avatar.png' }}" alt="{{ $request->requester->name }}">
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $request->requester->name }}</h3>
                                        <p class="text-sm text-gray-600">{{ $request->requester->bio ?? 'No bio available' }}</p>
                                        @if($request->message)
                                            <p class="text-sm text-gray-500 mt-1">"{{ $request->message }}"</p>
                                        @endif
                                        <p class="text-xs text-gray-400 mt-1">{{ $request->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="respondToConnection({{ $request->id }}, 'accept')" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">
                                        Accept
                                    </button>
                                    <button onclick="respondToConnection({{ $request->id }}, 'decline')" class="px-4 py-2 bg-gray-600 text-white text-sm rounded-md hover:bg-gray-700">
                                        Decline
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- My Connections -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">My Connections</h2>
                </div>
                <div class="p-6">
                    @if($connections->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($connections as $connection)
                                @php
                                    $otherUser = $connection->getOtherUser(auth()->user());
                                @endphp
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center space-x-4">
                                        <img class="w-12 h-12 rounded-full" src="{{ $otherUser->avatar ?? '/images/default-avatar.png' }}" alt="{{ $otherUser->name }}">
                                        <div class="flex-1">
                                            <h3 class="font-medium text-gray-900">{{ $otherUser->name }}</h3>
                                            <p class="text-sm text-gray-600">{{ $otherUser->bio ?? 'No bio available' }}</p>
                                            <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                <span>Connected {{ $connection->connection_duration }}</span>
                                                <span>{{ $connection->interaction_count }} interactions</span>
                                                <span class="px-2 py-1 bg-{{ $connection->getConnectionStrengthAttribute() === 'Strong' ? 'green' : ($connection->getConnectionStrengthAttribute() === 'Moderate' ? 'yellow' : 'gray') }}-100 text-{{ $connection->getConnectionStrengthAttribute() === 'Strong' ? 'green' : ($connection->getConnectionStrengthAttribute() === 'Moderate' ? 'yellow' : 'gray') }}-800 rounded-full">
                                                    {{ $connection->connection_strength }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex space-x-2">
                                        <button class="flex-1 px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                            Message
                                        </button>
                                        <button class="px-3 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300">
                                            View Profile
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6">
                            {{ $connections->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No connections yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Start connecting with other volunteers to build your network.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Suggested Connections -->
            @if(count($suggestedConnections) > 0)
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Suggested Connections</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($suggestedConnections as $suggestion)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center space-x-3">
                                    <img class="w-10 h-10 rounded-full" src="{{ $suggestion['user']->avatar ?? '/images/default-avatar.png' }}" alt="{{ $suggestion['user']->name }}">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-medium text-gray-900 truncate">{{ $suggestion['user']->name }}</h4>
                                        <p class="text-sm text-gray-600">{{ $suggestion['common_interests_count'] }} shared interests</p>
                                        <p class="text-xs text-gray-500">Score: {{ $suggestion['suggestion_score'] }}</p>
                                    </div>
                                </div>
                                <button onclick="sendConnectionRequest({{ $suggestion['user']->id }})" class="mt-3 w-full px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                                    Connect
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Search Volunteers -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Find Volunteers</h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('client.volunteering.community.members') }}" method="GET" class="space-y-4">
                        <div>
                            <input type="text" name="keyword" placeholder="Search by name or skills..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <select name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Any Location</option>
                                <option value="New York">New York</option>
                                <option value="Los Angeles">Los Angeles</option>
                                <option value="Chicago">Chicago</option>
                                <!-- Add more locations -->
                            </select>
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Search
                        </button>
                    </form>
                </div>
            </div>

            <!-- Connection Tips -->
            <div class="bg-blue-50 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-blue-900 mb-4">Connection Tips</h3>
                <ul class="space-y-2 text-sm text-blue-800">
                    <li class="flex items-start">
                        <svg class="w-4 h-4 mt-0.5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Personalize your connection requests
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 mt-0.5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Look for shared interests and experiences
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 mt-0.5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Stay active and engage regularly
                    </li>
                    <li class="flex items-start">
                        <svg class="w-4 h-4 mt-0.5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Attend community events to meet people
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Connection Request Modal -->
<div id="connectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Send Connection Request</h3>
            <form id="connectionForm">
                <input type="hidden" id="recipientId" name="recipient_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Message (Optional)</label>
                    <textarea name="message" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Why would you like to connect?"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Connection Reasons</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="connection_reasons[]" value="shared_interests" class="mr-2">
                            <span class="text-sm">Shared volunteering interests</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="connection_reasons[]" value="mentorship" class="mr-2">
                            <span class="text-sm">Mentorship opportunities</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="connection_reasons[]" value="collaboration" class="mr-2">
                            <span class="text-sm">Collaboration on projects</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="connection_reasons[]" value="networking" class="mr-2">
                            <span class="text-sm">Professional networking</span>
                        </label>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Send Request
                    </button>
                    <button type="button" onclick="closeConnectionModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function sendConnectionRequest(userId) {
    document.getElementById('recipientId').value = userId;
    document.getElementById('connectionModal').classList.remove('hidden');
}

function closeConnectionModal() {
    document.getElementById('connectionModal').classList.add('hidden');
    document.getElementById('connectionForm').reset();
}

function respondToConnection(connectionId, action) {
    fetch(`/volunteering/community/connections/${connectionId}/respond`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ action: action })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

document.getElementById('connectionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    // Convert FormData to regular object
    for (let [key, value] of formData.entries()) {
        if (key.endsWith('[]')) {
            const arrayKey = key.slice(0, -2);
            if (!data[arrayKey]) data[arrayKey] = [];
            data[arrayKey].push(value);
        } else {
            data[key] = value;
        }
    }
    
    fetch('/volunteering/community/connections/request', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeConnectionModal();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});
</script>
@endpush