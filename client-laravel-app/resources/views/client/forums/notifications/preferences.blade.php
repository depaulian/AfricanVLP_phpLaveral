@extends('layouts.client')

@section('title', 'Notification Preferences')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Notification Preferences</h1>
                <p class="text-gray-600 mt-2">Customize how you receive forum notifications</p>
            </div>
            <a href="{{ route('forums.notifications.index') }}" 
               class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Notifications
            </a>
        </div>

        <!-- Preferences Form -->
        <form id="preferencesForm" class="space-y-8">
            @csrf
            
            @foreach($types as $type => $config)
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $config['label'] }}</h3>
                        <p class="text-gray-600 text-sm mt-1">{{ $config['description'] }}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Email Notifications -->
                    <div class="space-y-3">
                        <h4 class="font-medium text-gray-900 flex items-center">
                            <i class="fas fa-envelope text-blue-600 mr-2"></i>
                            Email Notifications
                        </h4>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="preferences[{{ $type }}][email_enabled]" 
                                   value="1"
                                   {{ $preferences[$type]['email_enabled'] ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Send email notifications</span>
                        </label>
                    </div>
                    
                    <!-- In-App Notifications -->
                    <div class="space-y-3">
                        <h4 class="font-medium text-gray-900 flex items-center">
                            <i class="fas fa-bell text-green-600 mr-2"></i>
                            In-App Notifications
                        </h4>
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   name="preferences[{{ $type }}][in_app_enabled]" 
                                   value="1"
                                   {{ $preferences[$type]['in_app_enabled'] ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                            <span class="ml-2 text-sm text-gray-700">Show in-app notifications</span>
                        </label>
                    </div>
                    
                    <!-- Digest Notifications -->
                    <div class="space-y-3">
                        <h4 class="font-medium text-gray-900 flex items-center">
                            <i class="fas fa-newspaper text-purple-600 mr-2"></i>
                            Digest Notifications
                        </h4>
                        <label class="flex items-center mb-2">
                            <input type="checkbox" 
                                   name="preferences[{{ $type }}][digest_enabled]" 
                                   value="1"
                                   {{ $preferences[$type]['digest_enabled'] ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                                   onchange="toggleDigestFrequency('{{ $type }}', this.checked)">
                            <span class="ml-2 text-sm text-gray-700">Include in digest</span>
                        </label>
                        
                        <div id="digest-frequency-{{ $type }}" 
                             class="{{ $preferences[$type]['digest_enabled'] ? '' : 'hidden' }}">
                            <select name="preferences[{{ $type }}][digest_frequency]" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                                <option value="daily" {{ $preferences[$type]['digest_frequency'] === 'daily' ? 'selected' : '' }}>Daily</option>
                                <option value="weekly" {{ $preferences[$type]['digest_frequency'] === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ $preferences[$type]['digest_frequency'] === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            
            <!-- Save Button -->
            <div class="flex justify-end space-x-4">
                <button type="button" 
                        onclick="resetToDefaults()" 
                        class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    Reset to Defaults
                </button>
                <button type="submit" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Preferences
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleDigestFrequency(type, enabled) {
    const frequencyDiv = document.getElementById(`digest-frequency-${type}`);
    if (enabled) {
        frequencyDiv.classList.remove('hidden');
    } else {
        frequencyDiv.classList.add('hidden');
    }
}

function resetToDefaults() {
    if (!confirm('Reset all notification preferences to defaults? This cannot be undone.')) {
        return;
    }
    
    // Reset all checkboxes and selects to their default values
    const form = document.getElementById('preferencesForm');
    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    const selects = form.querySelectorAll('select');
    
    // You would need to implement the actual default values here
    // For now, we'll just reload the page
    location.reload();
}

document.getElementById('preferencesForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const preferences = {};
    
    // Convert FormData to nested object
    for (let [key, value] of formData.entries()) {
        if (key.startsWith('preferences[')) {
            const matches = key.match(/preferences\[([^\]]+)\]\[([^\]]+)\]/);
            if (matches) {
                const type = matches[1];
                const setting = matches[2];
                
                if (!preferences[type]) {
                    preferences[type] = {};
                }
                
                if (setting.includes('enabled')) {
                    preferences[type][setting] = value === '1';
                } else {
                    preferences[type][setting] = value;
                }
            }
        }
    }
    
    // Add unchecked checkboxes as false
    const allCheckboxes = this.querySelectorAll('input[type="checkbox"]');
    allCheckboxes.forEach(checkbox => {
        if (!checkbox.checked) {
            const matches = checkbox.name.match(/preferences\[([^\]]+)\]\[([^\]]+)\]/);
            if (matches) {
                const type = matches[1];
                const setting = matches[2];
                
                if (!preferences[type]) {
                    preferences[type] = {};
                }
                preferences[type][setting] = false;
            }
        }
    });
    
    fetch('{{ route("forums.notifications.preferences.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ preferences })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const successDiv = document.createElement('div');
            successDiv.className = 'fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded z-50';
            successDiv.innerHTML = '<i class="fas fa-check mr-2"></i>' + data.message;
            document.body.appendChild(successDiv);
            
            setTimeout(() => {
                successDiv.remove();
            }, 3000);
        } else {
            alert('Failed to update preferences');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update preferences');
    });
});
</script>
@endpush
@endsection