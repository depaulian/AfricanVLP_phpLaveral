@extends('layouts.client')

@section('title', 'Create New Thread - ' . $forum->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <nav class="text-sm text-gray-500 mb-4">
                <a href="{{ route('forums.index') }}" class="hover:text-blue-600">Forums</a>
                <span class="mx-2">/</span>
                <a href="{{ route('forums.show', $forum) }}" class="hover:text-blue-600">{{ $forum->name }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">New Thread</span>
            </nav>
            
            <h1 class="text-3xl font-bold text-gray-900">Create New Thread</h1>
            <p class="text-gray-600 mt-2">Start a new discussion in {{ $forum->name }}</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6">
                <form action="{{ route('forums.threads.store', $forum) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- Thread Title -->
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Thread Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" id="title" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('title') border-red-500 @enderror" 
                               placeholder="Enter a descriptive title for your thread"
                               value="{{ old('title') }}" required>
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Choose a clear, descriptive title that summarizes your topic</p>
                    </div>
                    
                    <!-- Thread Content -->
                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                            Content <span class="text-red-500">*</span>
                        </label>
                        <textarea name="content" id="content" rows="12" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('content') border-red-500 @enderror" 
                                  placeholder="Write your thread content here. Be clear and provide as much detail as possible to help others understand your topic."
                                  required>{{ old('content') }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Minimum 10 characters. You can use line breaks for formatting.</p>
                    </div>
                    
                    <!-- File Attachments -->
                    <div class="mb-6">
                        <label for="attachments" class="block text-sm font-medium text-gray-700 mb-2">
                            Attachments (Optional)
                        </label>
                        <input type="file" name="attachments[]" id="attachments" multiple 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('attachments') border-red-500 @enderror"
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip">
                        @error('attachments')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('attachments.*')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">
                            You can upload up to 5 files (max 10MB each). 
                            Supported formats: images (JPG, PNG, GIF), documents (PDF, DOC, DOCX, TXT), and ZIP files.
                        </p>
                    </div>
                    
                    <!-- Forum Guidelines -->
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <h3 class="text-sm font-medium text-blue-900 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>Forum Guidelines
                        </h3>
                        <ul class="text-sm text-blue-800 space-y-1">
                            <li>• Be respectful and constructive in your discussions</li>
                            <li>• Search existing threads before creating a new one</li>
                            <li>• Use clear, descriptive titles</li>
                            <li>• Stay on topic and provide relevant information</li>
                            <li>• Follow community guidelines and terms of service</li>
                        </ul>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <a href="{{ route('forums.show', $forum) }}" 
                           class="text-gray-600 hover:text-gray-800 px-4 py-2 transition duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Forum
                        </a>
                        
                        <div class="flex space-x-3">
                            <button type="button" onclick="saveDraft()" 
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-save mr-2"></i>Save Draft
                            </button>
                            
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-plus mr-2"></i>Create Thread
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Tips Sidebar -->
        <div class="mt-8 bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Tips for a Great Thread
            </h3>
            
            <div class="space-y-4 text-sm text-gray-700">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <div>
                        <strong>Be Specific:</strong> Include relevant details, error messages, or examples to help others understand your situation.
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <div>
                        <strong>Use Formatting:</strong> Break up long text with paragraphs and use clear language.
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <div>
                        <strong>Add Context:</strong> Explain what you've already tried or what you're looking to achieve.
                    </div>
                </div>
                
                <div class="flex items-start space-x-3">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <div>
                        <strong>Attach Files:</strong> Include screenshots, documents, or other relevant files to illustrate your point.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Auto-save draft functionality
let draftTimer;

function saveDraft() {
    const title = document.getElementById('title').value;
    const content = document.getElementById('content').value;
    
    if (title || content) {
        localStorage.setItem('forum_thread_draft_{{ $forum->id }}', JSON.stringify({
            title: title,
            content: content,
            timestamp: Date.now()
        }));
        
        // Show feedback
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check mr-2"></i>Saved!';
        button.classList.remove('bg-gray-200', 'hover:bg-gray-300');
        button.classList.add('bg-green-200', 'text-green-800');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('bg-green-200', 'text-green-800');
            button.classList.add('bg-gray-200', 'hover:bg-gray-300');
        }, 2000);
    }
}

// Auto-save every 30 seconds
function autoSave() {
    const title = document.getElementById('title').value;
    const content = document.getElementById('content').value;
    
    if (title || content) {
        saveDraft();
    }
}

// Load draft on page load
document.addEventListener('DOMContentLoaded', function() {
    const draft = localStorage.getItem('forum_thread_draft_{{ $forum->id }}');
    if (draft) {
        const draftData = JSON.parse(draft);
        const age = Date.now() - draftData.timestamp;
        
        // Only load draft if it's less than 24 hours old
        if (age < 24 * 60 * 60 * 1000) {
            if (confirm('A draft was found. Would you like to restore it?')) {
                document.getElementById('title').value = draftData.title || '';
                document.getElementById('content').value = draftData.content || '';
            }
        }
    }
    
    // Set up auto-save
    draftTimer = setInterval(autoSave, 30000);
});

// Clear draft on successful submission
document.querySelector('form').addEventListener('submit', function() {
    localStorage.removeItem('forum_thread_draft_{{ $forum->id }}');
    clearInterval(draftTimer);
});

// File upload preview
document.getElementById('attachments').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const maxFiles = 5;
    const maxSize = 10 * 1024 * 1024; // 10MB
    
    if (files.length > maxFiles) {
        alert(`You can only upload up to ${maxFiles} files at once.`);
        e.target.value = '';
        return;
    }
    
    for (let file of files) {
        if (file.size > maxSize) {
            alert(`File "${file.name}" is too large. Maximum size is 10MB.`);
            e.target.value = '';
            return;
        }
    }
});
</script>
@endpush
@endsection