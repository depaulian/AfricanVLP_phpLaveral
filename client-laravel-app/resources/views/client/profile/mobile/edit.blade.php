@extends('layouts.mobile')

@section('title', 'Edit Profile')

@section('content')
<div class="mobile-profile-edit">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('profile.mobile.dashboard') }}" class="text-blue-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Edit Profile</h1>
            <button type="submit" form="profileForm" class="text-blue-500 font-medium">Save</button>
        </div>
    </div>

    <form id="profileForm" action="{{ route('profile.mobile.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information Section -->
        <div class="bg-white">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Basic Information</h2>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           required>
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" 
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           required>
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" name="phone_number" value="{{ old('phone_number', $user->profile?->phone_number) }}" 
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('phone_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                    <input type="date" name="date_of_birth" 
                           value="{{ old('date_of_birth', $user->profile?->date_of_birth?->format('Y-m-d')) }}" 
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('date_of_birth')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                    <select name="gender" 
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender', $user->profile?->gender) === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $user->profile?->gender) === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $user->profile?->gender) === 'other' ? 'selected' : '' }}>Other</option>
                        <option value="prefer_not_to_say" {{ old('gender', $user->profile?->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                    </select>
                    @error('gender')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Bio Section -->
        <div class="bg-white">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">About Me</h2>
            </div>
            <div class="p-4">
                <textarea name="bio" rows="4" 
                          placeholder="Tell us about yourself, your interests, and what motivates you to volunteer..."
                          class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none">{{ old('bio', $user->profile?->bio) }}</textarea>
                <div class="flex justify-between items-center mt-2">
                    <span class="text-xs text-gray-500">Share your story and passion for volunteering</span>
                    <span id="bioCount" class="text-xs text-gray-400">0/500</span>
                </div>
                @error('bio')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Location Section -->
        <div class="bg-white">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Location</h2>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <textarea name="address" rows="2" 
                              placeholder="Enter your address"
                              class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none">{{ old('address', $user->profile?->address) }}</textarea>
                    @error('address')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                    <select name="country_id" id="countrySelect"
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" 
                                    {{ old('country_id', $user->profile?->country_id) == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('country_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <select name="city_id" id="citySelect"
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Select City</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" 
                                    data-country="{{ $city->country_id }}"
                                    {{ old('city_id', $user->profile?->city_id) == $city->id ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('city_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Social Links Section -->
        <div class="bg-white">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Social Links</h2>
                <p class="text-sm text-gray-500">Optional - Connect your social profiles</p>
            </div>
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">LinkedIn URL</label>
                    <input type="url" name="linkedin_url" 
                           value="{{ old('linkedin_url', $user->profile?->linkedin_url) }}" 
                           placeholder="https://linkedin.com/in/yourprofile"
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('linkedin_url')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Twitter URL</label>
                    <input type="url" name="twitter_url" 
                           value="{{ old('twitter_url', $user->profile?->twitter_url) }}" 
                           placeholder="https://twitter.com/yourusername"
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('twitter_url')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Website URL</label>
                    <input type="url" name="website_url" 
                           value="{{ old('website_url', $user->profile?->website_url) }}" 
                           placeholder="https://yourwebsite.com"
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    @error('website_url')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Privacy Settings -->
        <div class="bg-white">
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold">Privacy Settings</h2>
            </div>
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium">Public Profile</h3>
                        <p class="text-sm text-gray-500">Make your profile visible to organizations</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_public" value="1" 
                               {{ old('is_public', $user->profile?->is_public ?? true) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="p-4 bg-white border-t border-gray-200">
            <button type="submit" 
                    class="w-full bg-blue-500 text-white py-3 rounded-lg font-medium hover:bg-blue-600 transition-colors">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Bio character counter
const bioTextarea = document.querySelector('textarea[name="bio"]');
const bioCounter = document.getElementById('bioCount');

function updateBioCounter() {
    const length = bioTextarea.value.length;
    bioCounter.textContent = `${length}/500`;
    
    if (length > 500) {
        bioCounter.classList.add('text-red-500');
        bioCounter.classList.remove('text-gray-400');
    } else {
        bioCounter.classList.remove('text-red-500');
        bioCounter.classList.add('text-gray-400');
    }
}

bioTextarea.addEventListener('input', updateBioCounter);
updateBioCounter(); // Initial count

// Country/City dependency
const countrySelect = document.getElementById('countrySelect');
const citySelect = document.getElementById('citySelect');
const allCityOptions = Array.from(citySelect.options);

function filterCities() {
    const selectedCountryId = countrySelect.value;
    
    // Clear current options except the first one
    citySelect.innerHTML = '<option value="">Select City</option>';
    
    if (selectedCountryId) {
        // Add cities for selected country
        allCityOptions.forEach(option => {
            if (option.dataset.country === selectedCountryId) {
                citySelect.appendChild(option.cloneNode(true));
            }
        });
    }
}

countrySelect.addEventListener('change', filterCities);

// Initialize cities based on current country selection
if (countrySelect.value) {
    filterCities();
}

// Form validation
document.getElementById('profileForm').addEventListener('submit', function(e) {
    const bio = bioTextarea.value;
    if (bio.length > 500) {
        e.preventDefault();
        alert('Bio must be 500 characters or less.');
        bioTextarea.focus();
        return false;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Saving...';
    submitBtn.disabled = true;
    
    // Re-enable button after 5 seconds as fallback
    setTimeout(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }, 5000);
});

// Auto-resize textareas
function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}

document.querySelectorAll('textarea').forEach(textarea => {
    textarea.addEventListener('input', () => autoResize(textarea));
    autoResize(textarea); // Initial resize
});
</script>
@endpush