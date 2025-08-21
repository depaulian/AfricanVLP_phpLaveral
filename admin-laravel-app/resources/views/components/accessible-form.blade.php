{{-- Accessible form component with WCAG 2.1 AA compliance --}}
@props([
    'action' => '#',
    'method' => 'POST',
    'title' => '',
    'description' => '',
    'submitText' => 'Submit',
    'cancelUrl' => null,
    'helpUrl' => null
])

<div class="max-w-2xl mx-auto">
    {{-- Skip link --}}
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    {{-- Form header --}}
    @if($title)
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900" id="form-title">{{ $title }}</h1>
        @if($description)
        <p class="mt-2 text-gray-600" id="form-description">{{ $description }}</p>
        @endif
        @if($helpUrl)
        <p class="mt-2">
            <a href="{{ $helpUrl }}" class="text-blue-600 hover:text-blue-800 underline" data-help="form-help">
                <span class="sr-only">Get help with this form</span>
                Need help? <span aria-hidden="true">?</span>
            </a>
        </p>
        @endif
    </div>
    @endif
    
    {{-- Main form --}}
    <form 
        action="{{ $action }}" 
        method="{{ $method }}"
        role="form"
        aria-labelledby="{{ $title ? 'form-title' : '' }}"
        aria-describedby="{{ $description ? 'form-description' : '' }}"
        x-data="formValidation()"
        @submit="handleSubmit"
        id="main-content"
        tabindex="-1"
        class="space-y-6 bg-white p-6 rounded-lg shadow-sm border border-gray-200"
    >
        @csrf
        @if($method !== 'GET' && $method !== 'POST')
            @method($method)
        @endif
        
        {{-- Form fields slot --}}
        {{ $slot }}
        
        {{-- Form actions --}}
        <div class="flex items-center justify-between pt-6 border-t border-gray-200">
            <div class="flex items-center space-x-4">
                @if($cancelUrl)
                <a 
                    href="{{ $cancelUrl }}" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 touch-target"
                >
                    Cancel
                </a>
                @endif
                
                <button 
                    type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed touch-target"
                    :disabled="submitting"
                    :aria-describedby="submitting ? 'submit-status' : ''"
                >
                    <span x-show="!submitting">{{ $submitText }}</span>
                    <span x-show="submitting" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Submitting...
                    </span>
                </button>
            </div>
            
            {{-- Form status for screen readers --}}
            <div id="submit-status" class="sr-only" aria-live="polite">
                <span x-show="submitting">Form is being submitted, please wait...</span>
            </div>
        </div>
        
        {{-- Form-level error summary --}}
        <div x-show="Object.keys(errors).length > 0" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md" role="alert" aria-live="assertive">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        There <span x-text="Object.keys(errors).length === 1 ? 'is' : 'are'"></span> 
                        <span x-text="Object.keys(errors).length"></span> 
                        error<span x-show="Object.keys(errors).length !== 1">s</span> with your submission
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <template x-for="(fieldErrors, fieldName) in errors" :key="fieldName">
                                <li x-show="fieldErrors.length > 0">
                                    <strong x-text="getFieldLabel(document.querySelector(`[name='${fieldName}']`)) || fieldName"></strong>: 
                                    <span x-text="fieldErrors[0]"></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    {{-- Help system integration --}}
    <div x-data="helpSystem()" x-show="helpVisible" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" role="dialog" aria-modal="true" aria-labelledby="help-title">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="help-title">Help</h3>
                        <button type="button" class="text-gray-400 hover:text-gray-600" @click="hideHelp()">
                            <span class="sr-only">Close help</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-3">
                        <div id="help-content" class="text-sm text-gray-500" tabindex="-1">
                            <!-- Help content will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Accessibility enhancements --}}
<div x-data="accessibility()" x-init="init()">
    {{-- High contrast mode indicator --}}
    <div x-data="highContrastMode()" x-init="init()"></div>
    
    {{-- Reduced motion indicator --}}
    <div x-data="reducedMotion()" x-init="init()"></div>
    
    {{-- Keyboard navigation indicator --}}
    <div x-data="keyboardNavigation()" x-init="init()"></div>
    
    {{-- Live region for announcements --}}
    <div x-data="liveRegion()" class="sr-only" aria-live="polite" aria-atomic="true">
        <template x-for="message in messages" :key="message.id">
            <div x-text="message.text"></div>
        </template>
    </div>
</div>