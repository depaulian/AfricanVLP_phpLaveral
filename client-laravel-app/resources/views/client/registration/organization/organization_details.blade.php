@extends('layouts.app')

@section('title', 'Organization Registration - Step 1')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900">Organization Registration</h2>
                <span class="text-sm text-gray-600">Step 1 of {{ count($progress['steps']) }}</span>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                     style="width: {{ $progress['overall_percentage'] }}%"></div>
            </div>
            
            <div class="flex justify-between mt-2">
                @foreach($progress['steps'] as $step)
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                                    {{ $step['is_completed'] ? 'bg-green-500 text-white' : 
                                       ($step['name'] === $stepName ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-600') }}">
                            {{ $loop->iteration }}
                        </div>
                        <span class="text-xs mt-1 text-center">{{ $step['title'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="mb-6">
                <h3 class="text-xl font-semibold text-gray-900">{{ $stepConfig['title'] }}</h3>
                <p class="text-gray-600 mt-2">{{ $stepConfig['description'] }}</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('registration.organization.process', $stepName) }}" 
                  id="organizationForm" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Organization Name -->
                    <div class="md:col-span-2">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Organization Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $stepData?->getStepDataValue('name')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter your organization name"
                               required>
                    </div>

                    <!-- About Organization -->
                    <div class="md:col-span-2">
                        <label for="about" class="block text-sm font-medium text-gray-700 mb-2">
                            About Organization <span class="text-red-500">*</span>
                        </label>
                        <textarea id="about" 
                                  name="about" 
                                  rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Provide a brief description of your organization"
                                  required>{{ old('about', $stepData?->getStepDataValue('about')) }}</textarea>
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                            Address <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               value="{{ old('address', $stepData?->getStepDataValue('address')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter your organization's address"
                               required>
                    </div>

                    <!-- Country -->
                    <div>
                        <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Country <span class="text-red-500">*</span>
                        </label>
                        <select id="country_id" 
                                name="country_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="">Select Country</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" 
                                        {{ old('country_id', $stepData?->getStepDataValue('country_id')) == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- City -->
                    <div>
                        <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">
                            City <span class="text-red-500">*</span>
                        </label>
                        <select id="city_id" 
                                name="city_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="">Select City</option>
                        </select>
                    </div>

                    <!-- Phone Number -->
                    <div>
                        <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone Number <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" 
                               id="phone_number" 
                               name="phone_number" 
                               value="{{ old('phone_number', $stepData?->getStepDataValue('phone_number')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter phone number"
                               required>
                    </div>

                    <!-- Website -->
                    <div>
                        <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                            Website
                        </label>
                        <input type="url" 
                               id="website" 
                               name="website" 
                               value="{{ old('website', $stepData?->getStepDataValue('website')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="https://example.com">
                    </div>

                    <!-- Organization Type -->
                    <div>
                        <label for="organization_type_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Organization Type <span class="text-red-500">*</span>
                        </label>
                        <select id="organization_type_id" 
                                name="organization_type_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="">Select Type</option>
                            @foreach($organizationTypes as $type)
                                <option value="{{ $type->id }}" 
                                        {{ old('organization_type_id', $stepData?->getStepDataValue('organization_type_id')) == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date of Establishment -->
                    <div>
                        <label for="date_of_establishment" class="block text-sm font-medium text-gray-700 mb-2">
                            Date of Establishment
                        </label>
                        <input type="date" 
                               id="date_of_establishment" 
                               name="date_of_establishment" 
                               value="{{ old('date_of_establishment', $stepData?->getStepDataValue('date_of_establishment')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               max="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <!-- Hidden fields for coordinates (if using map integration) -->
                <input type="hidden" name="lat" id="lat" value="{{ old('lat', $stepData?->getStepDataValue('lat')) }}">
                <input type="hidden" name="lng" id="lng" value="{{ old('lng', $stepData?->getStepDataValue('lng')) }}">

                <!-- Form Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <a href="{{ route('registration.index') }}" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Back to Selection
                    </a>

                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Next Step
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Auto-save indicator -->
<div id="autosave-indicator" class="fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-md shadow-lg hidden">
    <div class="flex items-center">
        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
        </svg>
        Progress saved
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const countrySelect = document.getElementById('country_id');
    const citySelect = document.getElementById('city_id');
    const form = document.getElementById('organizationForm');
    let autoSaveTimeout;

    // Load cities when country changes
    countrySelect.addEventListener('change', function() {
        const countryId = this.value;
        citySelect.innerHTML = '<option value="">Loading...</option>';

        if (countryId) {
            fetch(`{{ route('registration.organization.cities') }}?country_id=${countryId}`)
                .then(response => response.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    if (data.status === 'success') {
                        data.data.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.name;
                            citySelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading cities:', error);
                    citySelect.innerHTML = '<option value="">Error loading cities</option>';
                });
        } else {
            citySelect.innerHTML = '<option value="">Select City</option>';
        }
    });

    // Load cities on page load if country is already selected
    if (countrySelect.value) {
        countrySelect.dispatchEvent(new Event('change'));
    }

    // Auto-save functionality
    function autoSave() {
        const formData = new FormData(form);
        const stepData = {};
        
        for (let [key, value] of formData.entries()) {
            if (key !== '_token') {
                stepData[key] = value;
            }
        }

        fetch('{{ route('registration.organization.autosave') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                step_name: '{{ $stepName }}',
                step_data: stepData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showAutoSaveIndicator();
            }
        })
        .catch(error => {
            console.error('Auto-save error:', error);
        });
    }

    function showAutoSaveIndicator() {
        const indicator = document.getElementById('autosave-indicator');
        indicator.classList.remove('hidden');
        setTimeout(() => {
            indicator.classList.add('hidden');
        }, 2000);
    }

    // Auto-save on input changes
    form.addEventListener('input', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(autoSave, 2000); // Save after 2 seconds of inactivity
    });

    // Auto-save on select changes
    form.addEventListener('change', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(autoSave, 1000); // Save after 1 second for select changes
    });
});
</script>
@endpush
