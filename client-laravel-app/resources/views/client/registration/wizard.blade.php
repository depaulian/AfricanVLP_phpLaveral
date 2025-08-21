@extends('layouts.app')

@section('title', 'Complete Your Profile')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Progress Header -->
        <div class="mb-8">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Complete Your Profile</h1>
                <p class="text-gray-600 mt-2">Help us personalize your volunteering experience</p>
            </div>
            
            <!-- Progress Bar -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-medium text-gray-700">Step {{ $currentStep }} of {{ $totalSteps }}</span>
                    <span class="text-sm text-gray-500">{{ $completionPercentage }}% Complete</span>
                </div>
                
                <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ $completionPercentage }}%"></div>
                </div>
                
                <!-- Step Indicators -->
                <div class="flex justify-between">
                    @foreach($steps as $index => $step)
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                                {{ $index + 1 < $currentStep ? 'bg-green-500 text-white' : '' }}
                                {{ $index + 1 == $currentStep ? 'bg-blue-600 text-white' : '' }}
                                {{ $index + 1 > $currentStep ? 'bg-gray-300 text-gray-600' : '' }}">
                                @if($index + 1 < $currentStep)
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </div>
                            <span class="text-xs text-gray-600 mt-2 text-center max-w-20">{{ $step['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Step Content -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">{{ $currentStepData['title'] }}</h2>
                <p class="text-gray-600 mt-1">{{ $currentStepData['description'] }}</p>
            </div>
            
            <div class="p-6">
                <form action="{{ route('registration.wizard.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="step" value="{{ $currentStep }}">
                    
                    @switch($currentStep)
                        @case(1)
                            @include('client.registration.steps.basic-info')
                            @break
                        @case(2)
                            @include('client.registration.steps.location')
                            @break
                        @case(3)
                            @include('client.registration.steps.interests')
                            @break
                        @case(4)
                            @include('client.registration.steps.skills')
                            @break
                        @case(5)
                            @include('client.registration.steps.availability')
                            @break
                        @case(6)
                            @include('client.registration.steps.verification')
                            @break
                        @default
                            @include('client.registration.steps.complete')
                    @endswitch
                    
                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <div>
                            @if($currentStep > 1)
                                <a href="{{ route('registration.wizard', ['step' => $currentStep - 1]) }}" 
                                   class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                                    Previous
                                </a>
                            @endif
                        </div>
                        
                        <div class="flex items-center space-x-3">
                            @if($currentStep < $totalSteps)
                                <button type="button" onclick="skipStep()" 
                                        class="text-gray-500 hover:text-gray-700 px-4 py-2">
                                    Skip for now
                                </button>
                                <button type="submit" 
                                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    Continue
                                </button>
                            @else
                                <button type="submit" 
                                        class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                                    Complete Registration
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Need Help?</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>{{ $currentStepData['help_text'] ?? 'Complete each step to help us match you with the best volunteering opportunities.' }}</p>
                        <div class="mt-3">
                            <a href="{{ route('support.contact') }}" class="text-blue-600 hover:text-blue-500 font-medium">
                                Contact Support →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function skipStep() {
    if (confirm('Are you sure you want to skip this step? You can always complete it later from your profile.')) {
        window.location.href = '{{ route("registration.wizard", ["step" => $currentStep + 1]) }}';
    }
}

// Auto-save functionality
let autoSaveTimeout;
const form = document.querySelector('form');
const formInputs = form.querySelectorAll('input, select, textarea');

formInputs.forEach(input => {
    input.addEventListener('input', () => {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(autoSave, 2000); // Auto-save after 2 seconds of inactivity
    });
});

function autoSave() {
    const formData = new FormData(form);
    formData.append('auto_save', '1');
    
    fetch('{{ route("registration.wizard.auto-save") }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAutoSaveIndicator();
        }
    })
    .catch(error => {
        console.error('Auto-save failed:', error);
    });
}

function showAutoSaveIndicator() {
    // Create or update auto-save indicator
    let indicator = document.getElementById('autoSaveIndicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'autoSaveIndicator';
        indicator.className = 'fixed top-4 right-4 bg-green-100 text-green-800 px-3 py-2 rounded-lg shadow-lg text-sm';
        document.body.appendChild(indicator);
    }
    
    indicator.textContent = 'Changes saved automatically';
    indicator.style.display = 'block';
    
    setTimeout(() => {
        indicator.style.display = 'none';
    }, 3000);
}

// Form validation before submission
form.addEventListener('submit', function(e) {
    const requiredFields = form.querySelectorAll('[required]');
    let hasErrors = false;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            hasErrors = true;
        } else {
            field.classList.remove('border-red-500');
        }
    });
    
    if (hasErrors) {
        e.preventDefault();
        alert('Please fill in all required fields.');
        return false;
    }
});
</script>
@endpush
@endsection