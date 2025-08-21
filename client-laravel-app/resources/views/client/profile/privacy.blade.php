@extends('layouts.app')

@section('title', 'Privacy Settings')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Privacy Settings</h1>
                    <p class="text-gray-600 mt-1">Control who can see your profile information and how you're contacted</p>
                </div>
                <a href="{{ route('profile.index') }}" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    Back to Profile
                </a>
            </div>
        </div>

        <!-- Verification Status -->
        @if($verificationStatus['overall_score'] < 80)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Verify Your Profile for Better Privacy Control</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Verified profiles get enhanced privacy features and better visibility. Your verification score: {{ $verificationStatus['overall_score'] }}%</p>
                        </div>
                        <div class="mt-4">
                            <div class="flex -mx-2">
                                <a href="{{ route('profile.edit') }}" class="bg-yellow-100 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-200 mx-2">
                                    Complete Profile
                                </a>
                                <a href="{{ route('profile.documents') }}" class="bg-yellow-100 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-200 mx-2">
                                    Upload Documents
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('profile.privacy.update') }}" method="POST" class="space-y-8">
            @csrf
            @method('PUT')

            <!-- Profile Visibility -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Profile Visibility</h3>
                    <p class="text-sm text-gray-600 mt-1">Control who can see different sections of your profile</p>
                </div>
                <div class="p-6 space-y-6">
                    @foreach($profileSections as $sectionKey => $section)
                        @if(!$section['required'])
                            <div class="flex items-center justify-between py-4 border-b border-gray-100 last:border-b-0">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $section['label'] }}</h4>
                                    <p class="text-sm text-gray-500 mt-1">{{ $section['description'] }}</p>
                                </div>
                                <div class="ml-6">
                                    <select name="{{ $sectionKey }}" 
                                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        @foreach($privacyLevels as $levelKey => $level)
                                            <option value="{{ $levelKey }}" 
                                                    {{ old($sectionKey, $currentSettings[$sectionKey] ?? 'private') === $levelKey ? 'selected' : '' }}>
                                                {{ $level['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Overall Profile Visibility -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Overall Profile Visibility</h3>
                    <p class="text-sm text-gray-600 mt-1">Set the default visibility for your entire profile</p>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @foreach($privacyLevels as $levelKey => $level)
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="radio" name="profile_visibility" value="{{ $levelKey }}"
                                       {{ old('profile_visibility', $currentSettings['profile_visibility'] ?? 'public') === $levelKey ? 'checked' : '' }}
                                       class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($levelKey === 'public')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            @elseif($levelKey === 'organization')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            @endif
                                        </svg>
                                        <span class="font-medium text-gray-900">{{ $level['label'] }}</span>
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1">{{ $level['description'] }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Communication Settings -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Communication Settings</h3>
                    <p class="text-sm text-gray-600 mt-1">Control how others can contact you</p>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Allow Messages -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Allow Messages</h4>
                            <p class="text-sm text-gray-500">Let other users send you direct messages</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="allow_messages" value="1" 
                                   {{ old('allow_messages', $currentSettings['allow_messages'] ?? true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <!-- Message Restrictions -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Who can message you?</h4>
                        <div class="space-y-3">
                            @foreach($messageRestrictions as $restrictionKey => $restriction)
                                <label class="flex items-start space-x-3 cursor-pointer">
                                    <input type="radio" name="messages_from" value="{{ $restrictionKey }}"
                                           {{ old('messages_from', $currentSettings['messages_from'] ?? 'verified') === $restrictionKey ? 'checked' : '' }}
                                           class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <div>
                                        <span class="font-medium text-gray-900">{{ $restriction['label'] }}</span>
                                        <p class="text-sm text-gray-500">{{ $restriction['description'] }}</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity & Discovery Settings -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Activity & Discovery</h3>
                    <p class="text-sm text-gray-600 mt-1">Control your online presence and discoverability</p>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Show Online Status -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Show Online Status</h4>
                            <p class="text-sm text-gray-500">Let others see when you're online</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="show_online_status" value="1" 
                                   {{ old('show_online_status', $currentSettings['show_online_status'] ?? true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <!-- Show Last Active -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Show Last Active</h4>
                            <p class="text-sm text-gray-500">Display when you were last active on the platform</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="show_last_active" value="1" 
                                   {{ old('show_last_active', $currentSettings['show_last_active'] ?? false) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <!-- Searchable Profile -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Searchable Profile</h4>
                            <p class="text-sm text-gray-500">Allow your profile to appear in search results</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="searchable_profile" value="1" 
                                   {{ old('searchable_profile', $currentSettings['searchable_profile'] ?? true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Data & Privacy -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Data & Privacy</h3>
                    <p class="text-sm text-gray-600 mt-1">Manage your data and privacy preferences</p>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Data Export -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Export My Data</h4>
                            <p class="text-sm text-gray-500">Download a copy of your profile data</p>
                        </div>
                        <a href="{{ route('profile.export') }}" 
                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Export Data
                        </a>
                    </div>

                    <!-- Account Deletion -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <div>
                            <h4 class="text-sm font-medium text-red-900">Delete Account</h4>
                            <p class="text-sm text-red-600">Permanently delete your account and all data</p>
                        </div>
                        <button type="button" 
                                onclick="confirmAccountDeletion()"
                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                            Delete Account
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6">
                <a href="{{ route('profile.index') }}" 
                   class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancel
                </a>
                <button type="submit" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Save Privacy Settings
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Account Deletion Confirmation Modal -->
<div id="deleteAccountModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <div class="flex items-center mb-4">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-medium text-gray-900">Delete Account</h3>
            </div>
        </div>
        <div class="mb-4">
            <p class="text-sm text-gray-500">
                Are you sure you want to delete your account? This action cannot be undone and will permanently remove all your data, including:
            </p>
            <ul class="list-disc list-inside text-sm text-gray-500 mt-2 space-y-1">
                <li>Profile information and history</li>
                <li>Volunteering applications and records</li>
                <li>Documents and certifications</li>
                <li>Messages and connections</li>
            </ul>
        </div>
        <div class="mb-4">
            <label for="deleteConfirmation" class="block text-sm font-medium text-gray-700 mb-2">
                Type "DELETE" to confirm:
            </label>
            <input type="text" id="deleteConfirmation" 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                   placeholder="DELETE">
        </div>
        <div class="flex justify-end space-x-3">
            <button type="button" 
                    onclick="closeDeleteModal()"
                    class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                Cancel
            </button>
            <button type="button" 
                    id="confirmDeleteBtn"
                    onclick="deleteAccount()"
                    disabled
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                Delete Account
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmAccountDeletion() {
    document.getElementById('deleteAccountModal').classList.remove('hidden');
    document.getElementById('deleteAccountModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteAccountModal').classList.add('hidden');
    document.getElementById('deleteAccountModal').classList.remove('flex');
    document.getElementById('deleteConfirmation').value = '';
    document.getElementById('confirmDeleteBtn').disabled = true;
}

function deleteAccount() {
    const confirmation = document.getElementById('deleteConfirmation').value;
    if (confirmation === 'DELETE') {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("profile.delete") }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}

// Enable delete button when correct confirmation is typed
document.getElementById('deleteConfirmation').addEventListener('input', function(e) {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (e.target.value === 'DELETE') {
        confirmBtn.disabled = false;
    } else {
        confirmBtn.disabled = true;
    }
});
</script>
@endpush
@endsection