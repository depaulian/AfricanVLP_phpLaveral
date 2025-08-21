@extends('layouts.app')

@section('title', 'New Message')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                New Message
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Start a conversation with another member or organization
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('messages.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Messages
            </a>
        </div>
    </div>

    <!-- Message Form -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="POST" action="{{ route('messages.store') }}" class="space-y-6">
                @csrf

                <!-- Recipient Selection -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Recipient Type -->
                    <div>
                        <label for="recipient_type" class="block text-sm font-medium text-gray-700">
                            Recipient Type
                        </label>
                        <select name="recipient_type" 
                                id="recipient_type" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                onchange="toggleRecipientOptions()">
                            <option value="">Select recipient type</option>
                            <option value="user" {{ old('recipient_type', $recipientType) === 'user' ? 'selected' : '' }}>
                                Individual Member
                            </option>
                            <option value="organization" {{ old('recipient_type', $recipientType) === 'organization' ? 'selected' : '' }}>
                                Organization
                            </option>
                        </select>
                        @error('recipient_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Recipient Selection -->
                    <div>
                        <label for="recipient_id" class="block text-sm font-medium text-gray-700">
                            Recipient
                        </label>
                        
                        <!-- User Selection -->
                        <select name="recipient_id" 
                                id="user_recipient" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                style="display: {{ old('recipient_type', $recipientType) === 'user' ? 'block' : 'none' }}">
                            <option value="">Select a member</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" 
                                        {{ old('recipient_id') == $user->id || (isset($recipient) && $recipient->id == $user->id && $recipientType === 'user') ? 'selected' : '' }}>
                                    {{ $user->full_name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>

                        <!-- Organization Selection -->
                        <select name="recipient_id" 
                                id="organization_recipient" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                style="display: {{ old('recipient_type', $recipientType) === 'organization' ? 'block' : 'none' }}">
                            <option value="">Select an organization</option>
                            @foreach($organizations as $organization)
                                <option value="{{ $organization->id }}" 
                                        {{ old('recipient_id') == $organization->id || (isset($recipient) && $recipient->id == $organization->id && $recipientType === 'organization') ? 'selected' : '' }}>
                                    {{ $organization->name }}
                                </option>
                            @endforeach
                        </select>

                        @error('recipient_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Subject -->
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700">
                        Subject
                    </label>
                    <input type="text" 
                           name="subject" 
                           id="subject" 
                           value="{{ old('subject') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Enter message subject">
                    @error('subject')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Message -->
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">
                        Message
                    </label>
                    <textarea name="message" 
                              id="message" 
                              rows="8" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Type your message here...">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('messages.index') }}" 
                       class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleRecipientOptions() {
    const recipientType = document.getElementById('recipient_type').value;
    const userSelect = document.getElementById('user_recipient');
    const orgSelect = document.getElementById('organization_recipient');
    
    if (recipientType === 'user') {
        userSelect.style.display = 'block';
        orgSelect.style.display = 'none';
        userSelect.name = 'recipient_id';
        orgSelect.name = '';
    } else if (recipientType === 'organization') {
        userSelect.style.display = 'none';
        orgSelect.style.display = 'block';
        userSelect.name = '';
        orgSelect.name = 'recipient_id';
    } else {
        userSelect.style.display = 'none';
        orgSelect.style.display = 'none';
        userSelect.name = '';
        orgSelect.name = '';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleRecipientOptions();
});
</script>
@endsection