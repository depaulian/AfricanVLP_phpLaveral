@extends('layouts.app')

@section('title', 'Complete Your Profile')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Progress Header -->
        <div class="mb-8">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Complete Your Profile</h1>
                <p class="text-gray-600 mt-2">Help us create the best experience for you by completing these steps</p>
            </div>
            
            <!-- Progress Bar -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-medium text-gray-700">Progress</span>
                    <span class="text-sm font-medium text-blue-600">{{ $progress['completion_percentage'] }}% Complete</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                         style="width: {{ $progress['completion_percentage'] }}%"></div>
                </div>
                
                <!-- Step Indicators -->
                <div class="flex justify-between mt-6">
                    @foreach($steps as $index => $step)
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium
                                {{ $step['completed'] ? 'bg-green-500 text-white' : ($step['current'] ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-600') }}">
                                @if($step['completed'])
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
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

        <!-- Current Step Content -->
        <div class="bg-white rounded-lg shadow">
            @if($currentStep === 'basic_info')
                @include('client.profile.wizard.basic-info')
            @elseif($currentStep === 'location')
                @include('client.profile.wizard.location')
            @elseif($currentStep === 'skills')
                @include('client.profile.wizard.skills')
            @elseif($currentStep === 'interests')
                @include('client.profile.wizard.interests')
            @elseif($currentStep === 'documents')
                @include('client.profile.wizard.documents')
            @elseif($currentStep === 'verification')
                @include('client.profile.wizard.verification')
            @else
                @include('client.profile.wizard.complete')
            @endif
        </div>

        <!-- Navigation -->
        <div class="flex justify-between mt-8">
            @if($currentStep !== 'basic_info')
                <a href="{{ route('registration.wizard', ['step' => $previousStep]) }}" 
                   class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400 transition-colors">
                    Previous Step
                </a>
            @else
                <div></div>
            @endif

            @if($currentStep !== 'complete')
                <button type="submit" form="wizardForm"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    {{ $isLastStep ? 'Complete Profile' : 'Continue' }}
                </button>
            @else
                <a href="{{ route('profile.index') }}" 
                   class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                    Go to Profile
                </a>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-save functionality
let autoSaveTimeout;

function autoSave() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        const form = document.getElementById('wizardForm');
        if (form) {
            const formData = new FormData(form);
            formData.append('auto_save', '1');
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
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
    }, 2000);
}

function showAutoSaveIndicator() {
    const indicator = document.createElement('div');
    indicator.className = 'fixed top-4 right-4 bg-green-100 text-green-800 px-3 py-2 rounded-lg text-sm z-50';
    indicator.textContent = 'Changes saved automatically';
    document.body.appendChild(indicator);
    
    setTimeout(() => {
        indicator.remove();
    }, 3000);
}

// Add auto-save listeners to form inputs
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('wizardForm');
    if (form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', autoSave);
            input.addEventListener('change', autoSave);
        });
    }
});
</script>
@endpush
@endsection