@extends('layouts.client')

@section('title', 'Apply for ' . $opportunity->title)

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-8" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('client.home') }}" class="text-gray-700 hover:text-blue-600">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('client.volunteering.index') }}" class="ml-1 text-gray-700 hover:text-blue-600 md:ml-2">Volunteering</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('client.volunteering.show', $opportunity) }}" class="ml-1 text-gray-700 hover:text-blue-600 md:ml-2">{{ Str::limit($opportunity->title, 30) }}</a>
                    </div>
                </li>
                <li aria-current="page">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-gray-500 md:ml-2">Apply</span>
                    </div>
                </li>
            </ol>
        </nav>

        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Application Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-gray-900 mb-2">Apply for Volunteer Position</h1>
                            <p class="text-gray-600">Complete the form below to submit your application. All fields marked with * are required.</p>
                        </div>

                        @if ($errors->any())
                            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                                        <div class="mt-2 text-sm text-red-700">
                                            <ul class="list-disc pl-5 space-y-1">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('client.volunteering.submit-application', $opportunity) }}" method="POST" class="space-y-6">
                            @csrf

                            <!-- Motivation -->
                            <div>
                                <label for="motivation" class="block text-sm font-medium text-gray-700 mb-2">
                                    Why do you want to volunteer for this opportunity? *
                                </label>
                                <textarea id="motivation" 
                                          name="motivation" 
                                          rows="5" 
                                          required
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('motivation') border-red-300 @enderror"
                                          placeholder="Tell us about your motivation, what draws you to this opportunity, and how it aligns with your values or goals...">{{ old('motivation') }}</textarea>
                                <p class="mt-1 text-sm text-gray-500">Minimum 100 characters. Be specific about why this opportunity interests you.</p>
                                @error('motivation')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Relevant Experience -->
                            <div>
                                <label for="relevant_experience" class="block text-sm font-medium text-gray-700 mb-2">
                                    Relevant Experience
                                </label>
                                <textarea id="relevant_experience" 
                                          name="relevant_experience" 
                                          rows="4" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('relevant_experience') border-red-300 @enderror"
                                          placeholder="Describe any relevant experience, skills, or qualifications you have that relate to this volunteer position...">{{ old('relevant_experience') }}</textarea>
                                <p class="mt-1 text-sm text-gray-500">Include any volunteer work, professional experience, education, or personal projects that are relevant.</p>
                                @error('relevant_experience')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Availability -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    When are you available to volunteer? *
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    @php
                                        $availabilityOptions = [
                                            'monday' => 'Monday',
                                            'tuesday' => 'Tuesday', 
                                            'wednesday' => 'Wednesday',
                                            'thursday' => 'Thursday',
                                            'friday' => 'Friday',
                                            'saturday' => 'Saturday',
                                            'sunday' => 'Sunday',
                                            'morning' => 'Mornings',
                                            'afternoon' => 'Afternoons',
                                            'evening' => 'Evenings',
                                            'weekends' => 'Weekends',
                                            'flexible' => 'Flexible'
                                        ];
                                        $oldAvailability = old('availability', []);
                                    @endphp
                                    
                                    @foreach($availabilityOptions as $value => $label)
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   name="availability[]" 
                                                   value="{{ $value }}"
                                                   {{ in_array($value, $oldAvailability) ? 'checked' : '' }}
                                                   class="mr-2 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <span class="text-sm text-gray-700">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('availability')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Additional Information -->
                            <div>
                                <label for="additional_info" class="block text-sm font-medium text-gray-700 mb-2">
                                    Additional Information
                                </label>
                                <textarea id="additional_info" 
                                          name="additional_info" 
                                          rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('additional_info') border-red-300 @enderror"
                                          placeholder="Is there anything else you'd like us to know about your application?">{{ old('additional_info') }}</textarea>
                                <p class="mt-1 text-sm text-gray-500">Optional: Share any additional information that might be relevant to your application.</p>
                                @error('additional_info')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Emergency Contact (Optional) -->
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Emergency Contact (Optional)</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700 mb-2">
                                            Contact Name
                                        </label>
                                        <input type="text" 
                                               id="emergency_contact_name" 
                                               name="emergency_contact_name" 
                                               value="{{ old('emergency_contact_name') }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('emergency_contact_name') border-red-300 @enderror">
                                        @error('emergency_contact_name')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700 mb-2">
                                            Contact Phone
                                        </label>
                                        <input type="tel" 
                                               id="emergency_contact_phone" 
                                               name="emergency_contact_phone" 
                                               value="{{ old('emergency_contact_phone') }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('emergency_contact_phone') border-red-300 @enderror">
                                        @error('emergency_contact_phone')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Details -->
                            <div class="border-t border-gray-200 pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Details (Optional)</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label for="dietary_restrictions" class="block text-sm font-medium text-gray-700 mb-2">
                                            Dietary Restrictions or Allergies
                                        </label>
                                        <textarea id="dietary_restrictions" 
                                                  name="dietary_restrictions" 
                                                  rows="2" 
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('dietary_restrictions') border-red-300 @enderror"
                                                  placeholder="Please list any dietary restrictions, food allergies, or special dietary needs...">{{ old('dietary_restrictions') }}</textarea>
                                        @error('dietary_restrictions')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="medical_conditions" class="block text-sm font-medium text-gray-700 mb-2">
                                            Medical Conditions or Accessibility Needs
                                        </label>
                                        <textarea id="medical_conditions" 
                                                  name="medical_conditions" 
                                                  rows="2" 
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('medical_conditions') border-red-300 @enderror"
                                                  placeholder="Please share any medical conditions or accessibility accommodations we should be aware of...">{{ old('medical_conditions') }}</textarea>
                                        <p class="mt-1 text-sm text-gray-500">This information helps us ensure your safety and provide appropriate accommodations.</p>
                                        @error('medical_conditions')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="border-t border-gray-200 pt-6">
                                <div class="flex items-start">
                                    <input type="checkbox" 
                                           id="terms_accepted" 
                                           name="terms_accepted" 
                                           value="1"
                                           {{ old('terms_accepted') ? 'checked' : '' }}
                                           required
                                           class="mt-1 mr-3 text-blue-600 focus:ring-blue-500 border-gray-300 rounded @error('terms_accepted') border-red-300 @enderror">
                                    <label for="terms_accepted" class="text-sm text-gray-700">
                                        I agree to the <a href="#" class="text-blue-600 hover:text-blue-800 underline">terms and conditions</a> 
                                        and understand that this application does not guarantee acceptance. I confirm that all information provided is accurate and complete. *
                                    </label>
                                </div>
                                @error('terms_accepted')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex flex-col sm:flex-row gap-4 pt-6">
                                <button type="submit" 
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Submit Application
                                </button>
                                <a href="{{ route('client.volunteering.show', $opportunity) }}" 
                                   class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-3 px-6 rounded-lg transition duration-200 text-center focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Opportunity Summary Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Opportunity Summary</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $opportunity->title }}</h4>
                                <p class="text-sm text-gray-600">{{ $opportunity->organization->name }}</p>
                            </div>

                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                @if($opportunity->location_type === 'remote')
                                    Remote
                                @elseif($opportunity->location_type === 'hybrid')
                                    Hybrid
                                @else
                                    {{ $opportunity->city->name ?? 'On-site' }}
                                @endif
                            </div>

                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ $opportunity->time_commitment }}
                            </div>

                            @if($opportunity->application_deadline)
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Apply by {{ $opportunity->application_deadline->format('M j, Y') }}
                                </div>
                            @endif

                            @if($opportunity->volunteers_needed)
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    {{ $opportunity->spots_remaining }} spots remaining
                                </div>
                            @endif
                        </div>

                        @if($opportunity->required_skills && count($opportunity->required_skills) > 0)
                            <div class="mt-6 pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Skills Needed</h4>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($opportunity->required_skills as $skill)
                                        <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded">
                                            {{ $skill }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <p class="text-xs text-gray-500">
                                By submitting this application, you agree to be contacted by {{ $opportunity->organization->name }} 
                                regarding this volunteer opportunity.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Character counter for motivation field
document.getElementById('motivation').addEventListener('input', function() {
    const current = this.value.length;
    const min = 100;
    const max = 1000;
    
    // You could add a character counter here if desired
    if (current < min) {
        this.style.borderColor = '#f87171'; // red
    } else if (current > max) {
        this.style.borderColor = '#f87171'; // red
    } else {
        this.style.borderColor = '#10b981'; // green
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const motivation = document.getElementById('motivation').value;
    const availability = document.querySelectorAll('input[name="availability[]"]:checked');
    const terms = document.getElementById('terms_accepted').checked;
    
    if (motivation.length < 100) {
        e.preventDefault();
        alert('Please provide at least 100 characters for your motivation.');
        return;
    }
    
    if (availability.length === 0) {
        e.preventDefault();
        alert('Please select at least one availability option.');
        return;
    }
    
    if (!terms) {
        e.preventDefault();
        alert('Please accept the terms and conditions to continue.');
        return;
    }
});
</script>
@endpush
@endsection