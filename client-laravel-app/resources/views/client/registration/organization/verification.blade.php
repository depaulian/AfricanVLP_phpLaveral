@extends('layouts.app')

@section('title', 'Organization Registration - Step 4')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900">Organization Registration</h2>
                <span class="text-sm text-gray-600">Step 4 of {{ count($progress['steps']) }}</span>
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

            <form method="POST" action="{{ route('registration.organization.process', $stepName) }}" class="space-y-6">
                @csrf

                <!-- Terms and Conditions -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Terms and Conditions</h4>
                    
                    <div class="max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-md p-4 mb-4">
                        <div class="prose prose-sm text-gray-700">
                            <h5 class="font-semibold">1. Organization Registration Agreement</h5>
                            <p>By registering your organization with the African Union Volunteer Platform (AU-VLP), you agree to the following terms and conditions:</p>
                            
                            <h5 class="font-semibold mt-4">2. Organization Eligibility</h5>
                            <p>Your organization must be a legally registered entity in an African Union member state and operate in alignment with AU-VLP's mission of promoting volunteerism for peace and development across Africa.</p>
                            
                            <h5 class="font-semibold mt-4">3. Information Accuracy</h5>
                            <p>You certify that all information provided during registration is accurate, complete, and up-to-date. You agree to notify AU-VLP of any changes to your organization's status or information.</p>
                            
                            <h5 class="font-semibold mt-4">4. Platform Usage</h5>
                            <p>Your organization agrees to use the platform responsibly and in accordance with AU-VLP guidelines. This includes posting legitimate volunteer opportunities and treating volunteers with respect.</p>
                            
                            <h5 class="font-semibold mt-4">5. Data Protection</h5>
                            <p>AU-VLP is committed to protecting your organization's data and volunteer information in accordance with applicable data protection laws and our Privacy Policy.</p>
                            
                            <h5 class="font-semibold mt-4">6. Account Suspension</h5>
                            <p>AU-VLP reserves the right to suspend or terminate organization accounts that violate these terms or engage in activities contrary to the platform's mission.</p>
                        </div>
                    </div>

                    <label class="flex items-start">
                        <input type="checkbox" 
                               name="terms_accepted" 
                               value="1"
                               {{ old('terms_accepted') ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1"
                               required>
                        <span class="ml-3 text-sm text-gray-700">
                            I have read and agree to the <strong>Terms and Conditions</strong> <span class="text-red-500">*</span>
                        </span>
                    </label>
                </div>

                <!-- Privacy Policy -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Privacy Policy</h4>
                    
                    <div class="max-h-64 overflow-y-auto bg-white border border-gray-200 rounded-md p-4 mb-4">
                        <div class="prose prose-sm text-gray-700">
                            <h5 class="font-semibold">1. Information Collection</h5>
                            <p>We collect information you provide during registration, including organization details, contact information, and documents. We also collect usage data to improve our services.</p>
                            
                            <h5 class="font-semibold mt-4">2. Information Use</h5>
                            <p>Your information is used to verify your organization, facilitate volunteer matching, communicate platform updates, and improve our services.</p>
                            
                            <h5 class="font-semibold mt-4">3. Information Sharing</h5>
                            <p>We do not sell your information. We may share information with volunteers for matching purposes and with AU member states for reporting and coordination.</p>
                            
                            <h5 class="font-semibold mt-4">4. Data Security</h5>
                            <p>We implement appropriate security measures to protect your information against unauthorized access, alteration, disclosure, or destruction.</p>
                            
                            <h5 class="font-semibold mt-4">5. Your Rights</h5>
                            <p>You have the right to access, update, or delete your organization's information. Contact us to exercise these rights.</p>
                            
                            <h5 class="font-semibold mt-4">6. Contact Information</h5>
                            <p>For privacy-related questions, contact us at privacy@au-vlp.org</p>
                        </div>
                    </div>

                    <label class="flex items-start">
                        <input type="checkbox" 
                               name="privacy_accepted" 
                               value="1"
                               {{ old('privacy_accepted') ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1"
                               required>
                        <span class="ml-3 text-sm text-gray-700">
                            I have read and agree to the <strong>Privacy Policy</strong> <span class="text-red-500">*</span>
                        </span>
                    </label>
                </div>

                <!-- Email Verification Notice -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Email Verification Required</h3>
                            <p class="mt-2 text-sm text-blue-700">
                                After completing registration, we will send a verification email to the admin email address provided. 
                                Please check your email and click the verification link to activate your organization's account.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <a href="{{ route('registration.organization.step', 'admin_user') }}" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Previous Step
                    </a>

                    <button type="submit" 
                            class="px-6 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Complete Registration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to top of terms/privacy when opened
    const scrollableAreas = document.querySelectorAll('.max-h-64.overflow-y-auto');
    scrollableAreas.forEach(area => {
        area.scrollTop = 0;
    });

    // Form submission confirmation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const termsAccepted = document.querySelector('input[name="terms_accepted"]').checked;
        const privacyAccepted = document.querySelector('input[name="privacy_accepted"]').checked;

        if (!termsAccepted || !privacyAccepted) {
            e.preventDefault();
            alert('Please accept both the Terms and Conditions and Privacy Policy to continue.');
            return;
        }

        // Show loading state
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
        `;

        // Re-enable button after 10 seconds as fallback
        setTimeout(() => {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }, 10000);
    });
});
</script>
@endpush
