@extends('layouts.app')

@section('title', 'Organization Registration - Step 2')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900">Organization Registration</h2>
                <span class="text-sm text-gray-600">Step 2 of {{ count($progress['steps']) }}</span>
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
                  enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Registration Document -->
                <div>
                    <label for="registration_document" class="block text-sm font-medium text-gray-700 mb-2">
                        Registration Document <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="registration_document" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Upload registration document</span>
                                    <input id="registration_document" name="registration_document" type="file" class="sr-only" 
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PDF, DOC, DOCX, JPG, PNG up to 10MB</p>
                        </div>
                    </div>
                    <div id="registration_document_preview" class="mt-2 hidden">
                        <div class="flex items-center p-2 bg-gray-50 rounded-md">
                            <svg class="h-5 w-5 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                            </svg>
                            <span id="registration_document_name" class="text-sm text-gray-700"></span>
                            <button type="button" onclick="clearFile('registration_document')" class="ml-auto text-red-500 hover:text-red-700">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Volunteering Policy (Optional) -->
                <div>
                    <label for="volunteering_policy" class="block text-sm font-medium text-gray-700 mb-2">
                        Volunteering Policy (Optional)
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="volunteering_policy" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Upload volunteering policy</span>
                                    <input id="volunteering_policy" name="volunteering_policy" type="file" class="sr-only" 
                                           accept=".pdf,.doc,.docx">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PDF, DOC, DOCX up to 10MB</p>
                        </div>
                    </div>
                    <div id="volunteering_policy_preview" class="mt-2 hidden">
                        <div class="flex items-center p-2 bg-gray-50 rounded-md">
                            <svg class="h-5 w-5 text-gray-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                            </svg>
                            <span id="volunteering_policy_name" class="text-sm text-gray-700"></span>
                            <button type="button" onclick="clearFile('volunteering_policy')" class="ml-auto text-red-500 hover:text-red-700">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Organization Sector -->
                <div>
                    <label for="organization_sector" class="block text-sm font-medium text-gray-700 mb-2">
                        Organization Sector
                    </label>
                    <input type="text" 
                           id="organization_sector" 
                           name="organization_sector" 
                           value="{{ old('organization_sector', $stepData?->getStepDataValue('organization_sector')) }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., Education, Health, Environment">
                </div>

                <!-- Form Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <a href="{{ route('registration.organization.step', 'organization_details') }}" 
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload handling
    function setupFileUpload(inputId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(inputId + '_preview');
        const nameSpan = document.getElementById(inputId + '_name');

        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                nameSpan.textContent = file.name;
                preview.classList.remove('hidden');
            }
        });
    }

    // Clear file function
    window.clearFile = function(inputId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(inputId + '_preview');
        
        input.value = '';
        preview.classList.add('hidden');
    };

    // Setup file uploads
    setupFileUpload('registration_document');
    setupFileUpload('volunteering_policy');

    // Drag and drop functionality
    function setupDragAndDrop(inputId) {
        const input = document.getElementById(inputId);
        const dropZone = input.closest('.border-dashed');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-blue-400', 'bg-blue-50');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;

            if (files.length > 0) {
                input.files = files;
                input.dispatchEvent(new Event('change'));
            }
        }
    }

    setupDragAndDrop('registration_document');
    setupDragAndDrop('volunteering_policy');
});
</script>
@endpush
