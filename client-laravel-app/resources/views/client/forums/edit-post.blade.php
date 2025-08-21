@extends('layouts.client')

@section('title', 'Edit Post')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <nav class="text-sm text-gray-500 mb-4">
                <a href="{{ route('forums.index') }}" class="hover:text-blue-600">Forums</a>
                <span class="mx-2">/</span>
                <a href="{{ route('forums.show', $post->thread->forum) }}" class="hover:text-blue-600">{{ $post->thread->forum->name }}</a>
                <span class="mx-2">/</span>
                <a href="{{ route('forums.threads.show', [$post->thread->forum, $post->thread]) }}" class="hover:text-blue-600">{{ Str::limit($post->thread->title, 30) }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">Edit Post</span>
            </nav>
            
            <h1 class="text-3xl font-bold text-gray-900">Edit Post</h1>
            <p class="text-gray-600 mt-2">Make changes to your post</p>
        </div>

        <!-- Original Post Context -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-gray-900 mb-2">In thread: {{ $post->thread->title }}</h3>
            <div class="text-sm text-gray-600">
                <span>Posted {{ $post->created_at->diffForHumans() }}</span>
                @if($post->is_solution)
                    <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                        <i class="fas fa-check-circle mr-1"></i>Marked as Solution
                    </span>
                @endif
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6">
                <form action="{{ route('forums.posts.update', $post) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <!-- Post Content -->
                    <div class="mb-6">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                            Post Content <span class="text-red-500">*</span>
                        </label>
                        <textarea name="content" id="content" rows="10" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 @error('content') border-red-500 @enderror" 
                                  placeholder="Write your post content here..."
                                  required>{{ old('content', $post->content) }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Minimum 5 characters. You can use line breaks for formatting.</p>
                    </div>
                    
                    <!-- Current Attachments -->
                    @if($post->attachments->isNotEmpty())
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Current Attachments</h4>
                            <div class="space-y-2">
                                @foreach($post->attachments as $attachment)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center space-x-2">
                                            <i class="{{ $attachment->file_icon }} text-gray-500"></i>
                                            <span class="text-sm text-gray-700">{{ $attachment->file_name }}</span>
                                            <span class="text-xs text-gray-500">({{ $attachment->human_file_size }})</span>
                                        </div>
                                        <button type="button" onclick="removeAttachment({{ $attachment->id }})" 
                                                class="text-red-600 hover:text-red-800 text-sm">
                                            <i class="fas fa-trash mr-1"></i>Remove
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <!-- Edit Notice -->
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <h3 class="text-sm font-medium text-yellow-900 mb-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Edit Notice
                        </h3>
                        <ul class="text-sm text-yellow-800 space-y-1">
                            <li>• Your post will show as "edited" with a timestamp</li>
                            <li>• Editing is limited to {{ auth()->user()->hasRole('admin') || $post->thread->forum->canModerate(auth()->user()) ? 'unlimited time' : '24 hours after creation' }}</li>
                            <li>• Consider the impact on ongoing discussions</li>
                            @if($post->replies()->count() > 0)
                                <li>• This post has {{ $post->replies()->count() }} replies that may reference the original content</li>
                            @endif
                        </ul>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <a href="{{ route('forums.threads.show', [$post->thread->forum, $post->thread]) }}#post-{{ $post->id }}" 
                           class="text-gray-600 hover:text-gray-800 px-4 py-2 transition duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>Cancel
                        </a>
                        
                        <div class="flex space-x-3">
                            <button type="button" onclick="previewChanges()" 
                                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-eye mr-2"></i>Preview
                            </button>
                            
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Post Info -->
        <div class="mt-8 bg-gray-50 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Post Information</h3>
            
            <div class="grid md:grid-cols-2 gap-6 text-sm text-gray-700">
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Post Details</h4>
                    <ul class="space-y-1">
                        <li><strong>Created:</strong> {{ $post->created_at->format('M j, Y \a\t g:i A') }}</li>
                        <li><strong>Vote Score:</strong> {{ $post->vote_score }} ({{ $post->upvotes }} up, {{ $post->downvotes }} down)</li>
                        <li><strong>Replies:</strong> {{ $post->replies()->count() }}</li>
                        @if($post->attachments->isNotEmpty())
                            <li><strong>Attachments:</strong> {{ $post->attachments->count() }}</li>
                        @endif
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-medium text-gray-900 mb-2">Status</h4>
                    <div class="flex flex-wrap gap-2">
                        @if($post->is_solution)
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                <i class="fas fa-check-circle mr-1"></i>Solution
                            </span>
                        @endif
                        
                        @if($post->vote_score > 0)
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                <i class="fas fa-thumbs-up mr-1"></i>Upvoted
                            </span>
                        @endif
                        
                        @if($post->replies()->count() > 0)
                            <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full">
                                <i class="fas fa-reply mr-1"></i>Has Replies
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Preview Changes</h3>
                    <button onclick="closePreview()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6">
                <div class="mb-4">
                    <div class="text-sm text-gray-500 mb-2">
                        Preview - changes not yet saved
                    </div>
                </div>
                
                <div id="previewContent" class="prose max-w-none"></div>
            </div>
            
            <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                <button onclick="closePreview()" 
                        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition duration-200">
                    Close Preview
                </button>
                <button onclick="closePreview(); document.querySelector('form').submit();" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function previewChanges() {
    const content = document.getElementById('content').value;
    
    document.getElementById('previewContent').innerHTML = content.replace(/\n/g, '<br>') || '<em>No content</em>';
    
    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
}

function removeAttachment(attachmentId) {
    if (confirm('Are you sure you want to remove this attachment? This action cannot be undone.')) {
        fetch(`/forums/attachments/${attachmentId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to remove attachment. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreview();
    }
});

// Close modal on backdrop click
document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePreview();
    }
});

// Auto-save functionality
let saveTimer;
function autoSave() {
    const content = document.getElementById('content').value;
    
    if (content !== `{{ $post->content }}`) {
        localStorage.setItem('forum_post_edit_{{ $post->id }}', JSON.stringify({
            content: content,
            timestamp: Date.now()
        }));
    }
}

// Set up auto-save every 30 seconds
document.addEventListener('DOMContentLoaded', function() {
    saveTimer = setInterval(autoSave, 30000);
    
    // Load any existing draft
    const draft = localStorage.getItem('forum_post_edit_{{ $post->id }}');
    if (draft) {
        const draftData = JSON.parse(draft);
        const age = Date.now() - draftData.timestamp;
        
        // Only load draft if it's less than 1 hour old
        if (age < 60 * 60 * 1000) {
            if (confirm('An unsaved draft was found. Would you like to restore it?')) {
                document.getElementById('content').value = draftData.content || '';
            }
        }
    }
});

// Clear draft on successful submission
document.querySelector('form').addEventListener('submit', function() {
    localStorage.removeItem('forum_post_edit_{{ $post->id }}');
    clearInterval(saveTimer);
});
</script>
@endpush
@endsection