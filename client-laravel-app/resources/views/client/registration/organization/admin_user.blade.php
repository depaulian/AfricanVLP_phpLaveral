@extends('layouts.app')

@section('title', 'Organization Registration - Step 3')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900">Organization Registration</h2>
                <span class="text-sm text-gray-600">Step 3 of {{ count($progress['steps']) }}</span>
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
                  id="adminUserForm" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">
                            First Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="first_name" 
                               name="first_name" 
                               value="{{ old('first_name', $stepData?->getStepDataValue('first_name')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter first name"
                               required>
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Last Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="last_name" 
                               name="last_name" 
                               value="{{ old('last_name', $stepData?->getStepDataValue('last_name')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter last name"
                               required>
                    </div>

                    <!-- Email -->
                    <div class="md:col-span-2">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="{{ old('email', $stepData?->getStepDataValue('email')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter email address"
                               required>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter password"
                                   required>
                            <button type="button" 
                                    onclick="togglePassword('password')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg id="password-eye" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Confirm password"
                                   required>
                            <button type="button" 
                                    onclick="togglePassword('password_confirmation')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <svg id="password_confirmation-eye" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">
                            Gender <span class="text-red-500">*</span>
                        </label>
                        <select id="gender" 
                                name="gender" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $stepData?->getStepDataValue('gender')) === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $stepData?->getStepDataValue('gender')) === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $stepData?->getStepDataValue('gender')) === 'other' ? 'selected' : '' }}>Other</option>
                            <option value="prefer_not_to_say" {{ old('gender', $stepData?->getStepDataValue('gender')) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                        </select>
                    </div>

                    <!-- Date of Birth -->
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-2">
                            Date of Birth <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               id="date_of_birth" 
                               name="date_of_birth" 
                               value="{{ old('date_of_birth', $stepData?->getStepDataValue('date_of_birth')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               max="{{ date('Y-m-d', strtotime('-18 years')) }}"
                               required>
                    </div>

                    <!-- Preferred Language -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Preferred Language <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($languages as $code => $name)
                                <label class="flex items-center">
                                    <input type="radio" 
                                           name="preferred_language" 
                                           value="{{ $code }}"
                                           {{ old('preferred_language', $stepData?->getStepDataValue('preferred_language')) === $code ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                           required>
                                    <span class="ml-2 text-sm text-gray-700">{{ $name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Platform Interests -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Platform Interests
                        </label>
                        <p class="text-sm text-gray-600 mb-4">What is your organization's interest on the Continental Platform?</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($platformInterests as $interest)
                                <label class="flex items-center">
                                    <input type="checkbox" 
                                           name="platform_interests[]" 
                                           value="{{ $interest->id }}"
                                           {{ in_array($interest->id, old('platform_interests', $stepData?->getStepDataValue('platform_interests', []))) ? 'checked' : '' }}
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">{{ $interest->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <a href="{{ route('registration.organization.step', 'document_upload') }}" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Previous Step
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
    const form = document.getElementById('adminUserForm');
    let autoSaveTimeout;

    // Password visibility toggle
    window.togglePassword = function(fieldId) {
        const field = document.getElementById(fieldId);
        const eye = document.getElementById(fieldId + '-eye');
        
        if (field.type === 'password') {
            field.type = 'text';
            eye.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
            `;
        } else {
            field.type = 'password';
            eye.innerHTML = `
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            `;
        }
    };

    // Password strength indicator
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('password_confirmation');

    function validatePasswords() {
        const password = passwordField.value;
        const confirmPassword = confirmPasswordField.value;

        // Remove existing validation messages
        const existingMessages = document.querySelectorAll('.password-validation-message');
        existingMessages.forEach(msg => msg.remove());

        // Password strength validation
        if (password.length > 0 && password.length < 8) {
            showValidationMessage(passwordField, 'Password must be at least 8 characters long', 'error');
        }

        // Password confirmation validation
        if (confirmPassword.length > 0 && password !== confirmPassword) {
            showValidationMessage(confirmPasswordField, 'Passwords do not match', 'error');
        } else if (confirmPassword.length > 0 && password === confirmPassword) {
            showValidationMessage(confirmPasswordField, 'Passwords match', 'success');
        }
    }

    function showValidationMessage(field, message, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `password-validation-message mt-1 text-sm ${type === 'error' ? 'text-red-600' : 'text-green-600'}`;
        messageDiv.textContent = message;
        field.parentNode.appendChild(messageDiv);
    }

    passwordField.addEventListener('input', validatePasswords);
    confirmPasswordField.addEventListener('input', validatePasswords);

    // Auto-save functionality
    function autoSave() {
        const formData = new FormData(form);
        const stepData = {};
        
        for (let [key, value] of formData.entries()) {
            if (key !== '_token') {
                if (key === 'platform_interests[]') {
                    if (!stepData.platform_interests) {
                        stepData.platform_interests = [];
                    }
                    stepData.platform_interests.push(value);
                } else {
                    stepData[key] = value;
                }
            }
        }

        // Don't auto-save passwords for security
        delete stepData.password;
        delete stepData.password_confirmation;

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

    // Auto-save on input changes (excluding password fields)
    form.addEventListener('input', function(e) {
        if (!e.target.name.includes('password')) {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSave, 2000);
        }
    });

    // Auto-save on select/checkbox changes
    form.addEventListener('change', function(e) {
        if (!e.target.name.includes('password')) {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSave, 1000);
        }
    });
});
</script>
@endpush
