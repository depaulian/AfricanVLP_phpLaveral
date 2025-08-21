<div class="px-6 py-8">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Location Information</h2>
        <p class="text-gray-600 mt-2">Help us connect you with local volunteering opportunities</p>
    </div>

    <form id="wizardForm" action="{{ route('registration.wizard.store', ['step' => 'location']) }}" method="POST" class="space-y-6">
        @csrf
        
        <!-- Current Location -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-blue-800">Use Current Location</h3>
                    <p class="text-sm text-blue-600">Click to automatically detect your location</p>
                </div>
                <button type="button" onclick="getCurrentLocation()" 
                        class="ml-auto bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">
                    Detect Location
                </button>
            </div>
        </div>

        <!-- Country Selection -->
        <div>
            <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">
                Country *
            </label>
            <select id="country_id" name="country_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('country_id') border-red-500 @enderror"
                    onchange="loadCities(this.value)">
                <option value="">Select Country</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}" 
                            {{ old('country_id', $user->profile?->country_id) == $country->id ? 'selected' : '' }}>
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>
            @error('country_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- City Selection -->
        <div>
            <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">
                City *
            </label>
            <select id="city_id" name="city_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('city_id') border-red-500 @enderror">
                <option value="">Select City</option>
                @if($user->profile?->city_id)
                    @foreach($cities->where('country_id', $user->profile->country_id) as $city)
                        <option value="{{ $city->id }}" 
                                {{ old('city_id', $user->profile?->city_id) == $city->id ? 'selected' : '' }}>
                            {{ $city->name }}
                        </option>
                    @endforeach
                @endif
            </select>
            @error('city_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Address -->
        <div>
            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                Street Address
            </label>
            <input type="text" id="address" name="address"
                   value="{{ old('address', $user->profile?->address) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-500 @enderror"
                   placeholder="123 Main Street">
            @error('address')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
            <p class="text-sm text-gray-500 mt-1">Optional - This helps organizations find volunteers in their area</p>
        </div>

        <!-- Travel Preferences -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Travel Preferences</h3>
            
            <div class="space-y-4">
                <!-- Maximum Travel Distance -->
                <div>
                    <label for="max_travel_distance" class="block text-sm font-medium text-gray-700 mb-2">
                        Maximum Travel Distance (km)
                    </label>
                    <select id="max_travel_distance" name="max_travel_distance"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">No preference</option>
                        <option value="5" {{ old('max_travel_distance', $user->profile?->max_travel_distance) == '5' ? 'selected' : '' }}>Within 5 km</option>
                        <option value="10" {{ old('max_travel_distance', $user->profile?->max_travel_distance) == '10' ? 'selected' : '' }}>Within 10 km</option>
                        <option value="25" {{ old('max_travel_distance', $user->profile?->max_travel_distance) == '25' ? 'selected' : '' }}>Within 25 km</option>
                        <option value="50" {{ old('max_travel_distance', $user->profile?->max_travel_distance) == '50' ? 'selected' : '' }}>Within 50 km</option>
                        <option value="100" {{ old('max_travel_distance', $user->profile?->max_travel_distance) == '100' ? 'selected' : '' }}>Within 100 km</option>
                        <option value="unlimited" {{ old('max_travel_distance', $user->profile?->max_travel_distance) == 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                    </select>
                </div>

                <!-- Transportation -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">
                        Available Transportation
                    </label>
                    <div class="space-y-2">
                        @php
                            $transportationMethods = ['car', 'public_transport', 'bicycle', 'walking'];
                            $currentTransportation = old('transportation', $user->profile?->transportation ?? []);
                            if (is_string($currentTransportation)) {
                                $currentTransportation = json_decode($currentTransportation, true) ?? [];
                            }
                        @endphp
                        
                        @foreach($transportationMethods as $method)
                            <label class="flex items-center">
                                <input type="checkbox" name="transportation[]" value="{{ $method }}"
                                       {{ in_array($method, $currentTransportation) ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $method)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Remote Work Preference -->
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="remote_volunteering" value="1"
                               {{ old('remote_volunteering', $user->profile?->remote_volunteering) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">I'm interested in remote/virtual volunteering opportunities</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Availability Zones -->
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Preferred Areas</h3>
            <p class="text-sm text-gray-600 mb-4">Select areas where you'd prefer to volunteer (optional)</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($nearbyAreas as $area)
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="preferred_areas[]" value="{{ $area->id }}"
                               {{ in_array($area->id, old('preferred_areas', $user->profile?->preferred_areas ?? [])) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900">{{ $area->name }}</span>
                            <p class="text-xs text-gray-500">{{ $area->distance_km }} km away</p>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Load cities based on country selection
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

// Get current location
function getCurrentLocation() {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by this browser.');
        return;
    }

    const button = event.target;
    const originalText = button.textContent;
    button.textContent = 'Detecting...';
    button.disabled = true;

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Reverse geocoding to get location details
            fetch(`/api/geocode/reverse?lat=${lat}&lng=${lng}`)
                .then(response => response.json())
                .then(data => {
                    if (data.country_id && data.city_id) {
                        document.getElementById('country_id').value = data.country_id;
                        loadCities(data.country_id);
                        
                        // Set city after cities are loaded
                        setTimeout(() => {
                            document.getElementById('city_id').value = data.city_id;
                        }, 1000);
                        
                        if (data.address) {
                            document.getElementById('address').value = data.address;
                        }
                        
                        showLocationSuccess();
                    } else {
                        showLocationError('Could not determine your location details.');
                    }
                })
                .catch(error => {
                    console.error('Geocoding error:', error);
                    showLocationError('Could not determine your location details.');
                })
                .finally(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                });
        },
        function(error) {
            let message = 'Could not get your location.';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Location access denied by user.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Location information is unavailable.';
                    break;
                case error.TIMEOUT:
                    message = 'Location request timed out.';
                    break;
            }
            showLocationError(message);
            button.textContent = originalText;
            button.disabled = false;
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000
        }
    );
}

function showLocationSuccess() {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-100 text-green-800 px-4 py-2 rounded-lg shadow-lg z-50';
    notification.textContent = 'Location detected successfully!';
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function showLocationError(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-red-100 text-red-800 px-4 py-2 rounded-lg shadow-lg z-50';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Load cities on page load if country is selected
document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('country_id');
    if (countrySelect.value) {
        loadCities(countrySelect.value);
    }
});
</script>
@endpush