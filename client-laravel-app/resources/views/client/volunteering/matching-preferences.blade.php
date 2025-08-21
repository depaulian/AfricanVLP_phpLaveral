@extends('layouts.client')

@section('title', 'Volunteer Matching Preferences')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Matching Preferences</h1>
                    <p class="text-gray-600 mt-1">Customize how we match you with volunteer opportunities</p>
                </div>
                <a href="{{ route('client.volunteering.dashboard') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-md transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Profile Completion -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Completion</h2>
                    
                    <div class="mb-6">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Profile Completion</span>
                            <span>{{ $profileCompletion }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-300" 
                                 style="width: {{ $profileCompletion }}%"></div>
                        </div>
                    </div>

                    @if($profileCompletion < 100)
                        <div class="space-y-3">
                            <h3 class="font-medium text-gray-900">Complete your profile to get better matches:</h3>
                            @foreach($suggestions as $suggestion)
                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="flex-1">
                                        <p class="text-sm text-gray-700">{{ $suggestion['message'] }}</p>
                                        <p class="text-xs text-blue-600 mt-1">+{{ $suggestion['points'] }} points</p>
                                    </div>
                                    <a href="{{ $suggestion['action'] }}" 
                                       class="ml-4 px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md transition-colors">
                                        Complete
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-full">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Profile Complete!
                            </div>
                            <p class="text-sm text-gray-600 mt-2">Your profile is complete and optimized for matching</p>
                        </div>
                    @endif
                </div>

                <!-- Notification Preferences -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Notification Preferences</h2>
                    
                    <form id="preferencesForm" class="space-y-6">
                        @csrf
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900">Volunteer Opportunity Notifications</h3>
                                    <p class="text-sm text-gray-500">Get notified when new opportunities match your profile</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="volunteer_notifications_enabled" 
                                           class="sr-only peer" {{ $preferences['volunteer_notifications_enabled'] ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900">Trending Opportunities</h3>
                                    <p class="text-sm text-gray-500">Get notified about popular volunteer opportunities</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="trending_notifications_enabled" 
                                           class="sr-only peer" {{ $preferences['trending_notifications_enabled'] ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900">Weekly Digest</h3>
                                    <p class="text-sm text-gray-500">Receive a weekly summary of recommended opportunities</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="digest_notifications_enabled" 
                                           class="sr-only peer" {{ $preferences['digest_notifications_enabled'] ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h3 class="text-sm font-medium text-gray-900">Immediate Notifications</h3>
                                    <p class="text-sm text-gray-500">Get instant notifications for high-match opportunities</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="immediate_notifications_enabled" 
                                           class="sr-only peer" {{ $preferences['immediate_notifications_enabled'] ? 'checked' : '' }}>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="clearCache()" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors">
                                Clear Cache
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                                Save Preferences
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Matching Algorithm Info -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">How We Match You</h2>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-blue-600">40%</span>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-gray-900">Interest Matching</h3>
                                <p class="text-sm text-gray-500">Based on your volunteering interests and categories</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-green-600">35%</span>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-gray-900">Skill Matching</h3>
                                <p class="text-sm text-gray-500">Matching your skills with opportunity requirements</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-purple-600">15%</span>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-gray-900">Location Preference</h3>
                                <p class="text-sm text-gray-500">Considering your location and remote work preferences</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex-shrink-0 w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-yellow-600">10%</span>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-gray-900">Experience Level</h3>
                                <p class="text-sm text-gray-500">Matching your experience with opportunity requirements</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <button onclick="triggerRecommendations()" 
                                class="w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            Get New Recommendations
                        </button>
                        <a href="{{ route('client.profile.interests') }}" 
                           class="block w-full text-center bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            Update Interests
                        </a>
                        <a href="{{ route('client.profile.skills') }}" 
                           class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md transition-colors">
                            Update Skills
                        </a>
                    </div>
                </div>

                <!-- Matching Tips -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Matching Tips</h3>
                    <div class="space-y-3 text-sm text-gray-600">
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Add multiple interests to get diverse recommendations</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Keep your skills updated to match with relevant opportunities</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-purple-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Set your location to find local opportunities</span>
                        </div>
                        <div class="flex items-start">
                            <svg class="w-4 h-4 text-yellow-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Enable notifications to never miss great opportunities</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Handle preferences form submission
document.getElementById('preferencesForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const preferences = {};
    
    // Convert form data to preferences object
    preferences.volunteer_notifications_enabled = formData.has('volunteer_notifications_enabled');
    preferences.trending_notifications_enabled = formData.has('trending_notifications_enabled');
    preferences.digest_notifications_enabled = formData.has('digest_notifications_enabled');
    preferences.immediate_notifications_enabled = formData.has('immediate_notifications_enabled');
    
    try {
        const response = await fetch('{{ route("client.volunteering.matching.preferences.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(preferences)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Preferences saved successfully!', 'success');
        } else {
            showNotification('Failed to save preferences', 'error');
        }
    } catch (error) {
        showNotification('An error occurred while saving preferences', 'error');
    }
});

// Clear recommendation cache
async function clearCache() {
    try {
        const response = await fetch('{{ route("client.volunteering.matching.clear-cache") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Cache cleared successfully!', 'success');
        } else {
            showNotification('Failed to clear cache', 'error');
        }
    } catch (error) {
        showNotification('An error occurred while clearing cache', 'error');
    }
}

// Trigger new recommendations
async function triggerRecommendations() {
    try {
        const response = await fetch('{{ route("client.volunteering.matching.trigger") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('New recommendations sent!', 'success');
        } else {
            showNotification('Failed to send recommendations', 'error');
        }
    } catch (error) {
        showNotification('An error occurred while sending recommendations', 'error');
    }
}

// Show notification
function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-md shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
@endpush
@endsection