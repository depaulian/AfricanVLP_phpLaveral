<!-- Location Step -->
<div class="space-y-6">
    <!-- Current Location -->
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">Where are you located?</h4>
        <p class="text-sm text-gray-600 mb-6">This helps us show you volunteering opportunities in your area.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Country *
                </label>
                <select id="country_id" name="country_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('country_id') border-red-500 @enderror"
                        onchange="loadCities(this.value)">
                    <option value="">Select Country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}" {{ old('country_id', $user->profile?->country_id) == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                    @endforeach
                </select>
                @error('country_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">
                    City *
                </label>
                <select id="city_id" name="city_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('city_id') border-red-500 @enderror">
                    <option value="">Select City</option>
                    @if($user->profile?->city_id)
                        @foreach($cities->where('country_id', $user->profile->country_id) as $city)
                            <option value="{{ $city->id }}" {{ old('city_id', $user->profile?->city_id) == $city->id ? 'selected' : '' }}>
                                {{ $city->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
                @error('city_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Address Details -->
    <div>
        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
            Street Address (Optional)
        </label>
        <input type="text" id="address" name="address"
               value="{{ old('address', $user->profile?->address) }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-500 @enderror"
               placeholder="123 Main Street, Apartment 4B">
        @error('address')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-sm text-gray-500 mt-1">Your exact address is kept private and only used for location-based matching.</p>
    </div>

    <!-- Travel Preferences -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Travel Preferences</h4>
        <p class="text-sm text-gray-600 mb-4">How far are you willing to travel for volunteering opportunities?</p>
        
        <div class="space-y-4">
            <div>
                <label for="max_travel_distance" class="block text-sm font-medium text-gray-700 mb-2">
                    Maximum Travel Distance
                </label>
                <div class="flex items-center space-x-4">
                    <input type="range" id="max_travel_distance" name="max_travel_distance" 
                           min="5" max="100" step="5"
                           value="{{ old('max_travel_distance', $user->profile?->max_travel_distance ?? 25) }}"
                           class="flex-1"
                           oninput="updateDistanceDisplay(this.value)">
                    <span id="distanceDisplay" class="text-sm font-medium text-gray-900 min-w-16">
                        {{ old('max_travel_distance', $user->profile?->max_travel_distance ?? 25) }} km
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="flex items-center">
                    <input type="checkbox" name="has_transportation" value="1" 
                           {{ old('has_transportation', $user->profile?->has_transportation) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-700">I have my own transportation</span>
                </label>
                
                <label class="flex items-center">
                    <input type="checkbox" name="willing_to_travel" value="1" 
                           {{ old('willing_to_travel', $user->profile?->willing_to_travel) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-700">Willing to use public transport</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Remote Work Preferences -->
    <div class="bg-blue-50 rounded-lg p-6">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Remote Volunteering</h4>
        <p class="text-sm text-gray-600 mb-4">Are you interested in remote or virtual volunteering opportunities?</p>
        
        <div class="space-y-3">
            <label class="flex items-center">
                <input type="radio" name="remote_preference" value="in_person_only" 
                       {{ old('remote_preference', $user->profile?->remote_preference ?? 'both') === 'in_person_only' ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                <span class="ml-2 text-sm text-gray-700">In-person opportunities only</span>
            </label>
            
            <label class="flex items-center">
                <input type="radio" name="remote_preference" value="remote_only" 
                       {{ old('remote_preference', $user->profile?->remote_preference) === 'remote_only' ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                <span class="ml-2 text-sm text-gray-700">Remote opportunities only</span>
            </label>
            
            <label class="flex items-center">
                <input type="radio" name="remote_preference" value="both" 
                       {{ old('remote_preference', $user->profile?->remote_preference ?? 'both') === 'both' ? 'checked' : '' }}
                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                <span class="ml-2 text-sm text-gray-700">Both in-person and remote opportunities</span>
            </label>
        </div>
    </div>

    <!-- Time Zone -->
    <div>
        <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
            Time Zone
        </label>
        <select id="timezone" name="timezone"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('timezone') border-red-500 @enderror">
            <option value="">Select Time Zone</option>
            @foreach($timezones as $timezone)
                <option value="{{ $timezone }}" {{ old('timezone', $user->profile?->timezone) === $timezone ? 'selected' : '' }}>
                    {{ $timezone }}
                </option>
            @endforeach
        </select>
        @error('timezone')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
        <p class="text-sm text-gray-500 mt-1">This helps with scheduling remote volunteering activities.</p>
    </div>
</div>

<script>
function loadCities(countryId) {
    const citySelect = document.getElementById('city_id');
    
    // Clear existing options
    citySelect.innerHTML = '<option value="">Loading cities...</option>';
    
    if (!countryId) {
        citySelect.innerHTML = '<option value="">Select City</option>';
        return;
    }
    
    fetch(`/api/countries/${countryId}/cities`)
        .then(response => response.json())
        .then(data => {
            citySelect.innerHTML = '<option value="">Select City</option>';
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.id;
                option.textContent = city.name;
                citySelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading cities:', error);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
        });
}

function updateDistanceDisplay(value) {
    document.getElementById('distanceDisplay').textContent = value + ' km';
}

// Load cities on page load if country is selected
document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('country_id');
    if (countrySelect.value) {
        loadCities(countrySelect.value);
    }
});
</script>