@extends('layouts.client')

@section('title', $thread->title . ' - ' . $forum->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Thread Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <nav class="text-sm text-gray-500 mb-4">
            <a href="{{ route('forums.index') }}" class="hover:text-blue-600">Forums</a>
            <span class="mx-2">/</span>
            <a href="{{ route('forums.show', $forum) }}" class="hover:text-blue-600">{{ $forum->name }}</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900">{{ $thread->title }}</span>
        </nav>
        
        <div class="flex justify-between items-start">
            <div class="flex-1">
                <div class="flex items-center space-x-2 mb-2">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $thread->title }}</h1>
                    
                    @if($thread->is_pinned)
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                            <i class="fas fa-thumbtack mr-1"></i>Pinned
                        </span>
                    @endif
                    
                    @if($thread->is_locked)
                        <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">
                            <i class="fas fa-lock mr-1"></i>Locked
                        </span>
                    @endif
                    
                    @if($thread->hasSolution())
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                            <i class="fas fa-check-circle mr-1"></i>Solved
                        </span>
                    @endif
                </div>
                
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                    <span>
                        by <a href="#" class="text-blue-600 hover:text-blue-800">{{ $thread->author->name }}</a>
                    </span>
                    <span>{{ $thread->created_at->format('M j, Y \a\t g:i A') }}</span>
                    <span>
                        <i class="fas fa-eye mr-1"></i>{{ $thread->view_count }} views
                    </span>
                    <span>
                        <i class="fas fa-reply mr-1"></i>{{ $thread->reply_count }} replies
                    </span>
                </div>
            </div>
            
            <!-- Thread Actions -->
            <div class="flex space-x-2">
                @can('update', $thread)
                    <a href="{{ route('forums.threads.edit', [$forum, $thread]) }}" 
                       class="text-gray-600 hover:text-blue-600 p-2" title="Edit Thread">
                        <i class="fas fa-edit"></i>
                    </a>
                @endcan
                
                @can('pin', $thread)
                    <button onclick="togglePin({{ $thread->id }})" 
                            class="text-gray-600 hover:text-green-600 p-2" 
                            title="{{ $thread->is_pinned ? 'Unpin' : 'Pin' }} Thread">
                        <i class="fas fa-thumbtack"></i>
                    </button>
                @endcan
                
                @can('lock', $thread)
                    <button onclick="toggleLock({{ $thread->id }})" 
                            class="text-gray-600 hover:text-red-600 p-2" 
                            title="{{ $thread->is_locked ? 'Unlock' : 'Lock' }} Thread">
                        <i class="fas fa-lock"></i>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Original Post -->
    <div class="bg-white rounded-lg shadow-md mb-6">
        <div class="p-6">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                        {{ substr($thread->author->name, 0, 1) }}
                    </div>
                </div>
                
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ $thread->author->name }}</h3>
                            <p class="text-sm text-gray-500">{{ $thread->created_at->format('M j, Y \a\t g:i A') }}</p>
                        </div>
                        
                        <div class="text-sm text-gray-500">
                            Original Post
                        </div>
                    </div>
                    
                    <div class="prose max-w-none">
                        {!! nl2br(e($thread->content)) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Posts -->
    @if($posts->isNotEmpty())
        <div class="space-y-6">
            @foreach($posts as $post)
                <div class="bg-white rounded-lg shadow-md" id="post-{{ $post->id }}">
                    <div class="p-6">
                        <div class="flex items-start space-x-4">
                            <!-- Vote Section -->
                            <div class="flex flex-col items-center space-y-2">
                                @can('vote', $post)
                                    <button onclick="vote({{ $post->id }}, 'up')" 
                                            class="vote-btn text-gray-400 hover:text-green-600 transition duration-200 {{ $post->getUserVoteType(auth()->user()) === 'up' ? 'text-green-600' : '' }}">
                                        <i class="fas fa-chevron-up text-lg"></i>
                                    </button>
                                @endcan
                                
                                <span class="vote-score font-semibold text-gray-700" data-post-id="{{ $post->id }}">
                                    {{ $post->vote_score }}
                                </span>
                                
                                @can('vote', $post)
                                    <button onclick="vote({{ $post->id }}, 'down')" 
                                            class="vote-btn text-gray-400 hover:text-red-600 transition duration-200 {{ $post->getUserVoteType(auth()->user()) === 'down' ? 'text-red-600' : '' }}">
                                        <i class="fas fa-chevron-down text-lg"></i>
                                    </button>
                                @endcan
                                
                                @if($post->is_solution)
                                    <div class="text-green-600 mt-2" title="Marked as Solution">
                                        <i class="fas fa-check-circle text-lg"></i>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Author Avatar -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-gray-600 rounded-full flex items-center justify-center text-white font-semibold">
                                    {{ substr($post->author->name, 0, 1) }}
                                </div>
                            </div>
                            
                            <!-- Post Content -->
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-4">
                                    <div>
                                        <h3 class="font-semibold text-gray-900">{{ $post->author->name }}</h3>
                                        <p class="text-sm text-gray-500">{{ $post->created_at->format('M j, Y \a\t g:i A') }}</p>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        @can('markSolution', $thread)
                                            @if(!$post->is_solution)
                                                <button onclick="markSolution({{ $post->id }})" 
                                                        class="text-sm text-green-600 hover:text-green-800 px-2 py-1 rounded border border-green-600 hover:bg-green-50 transition duration-200">
                                                    <i class="fas fa-check mr-1"></i>Mark as Solution
                                                </button>
                                            @endif
                                        @endcan
                                        
                                        @can('update', $post)
                                            <a href="{{ route('forums.posts.edit', $post) }}" 
                                               class="text-gray-600 hover:text-blue-600 p-1" title="Edit Post">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endcan
                                    </div>
                                </div>
                                
                                <div class="prose max-w-none">
                                    {!! nl2br(e($post->content)) !!}
                                </div>
                                
                                <!-- Attachments -->
                                @if($post->attachments->isNotEmpty())
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Attachments</h4>
                                        <div class="space-y-2">
                                            @foreach($post->attachments as $attachment)
                                                <div class="flex items-center space-x-2 text-sm">
                                                    <i class="{{ $attachment->file_icon }} text-gray-500"></i>
                                                    <a href="{{ route('forums.attachments.download', $attachment) }}" 
                                                       class="text-blue-600 hover:text-blue-800">
                                                        {{ $attachment->file_name }}
                                                    </a>
                                                    <span class="text-gray-500">({{ $attachment->human_file_size }})</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        <div class="mt-8">
            {{ $posts->links() }}
        </div>
    @endif

    <!-- Reply Form -->
    @can('reply', $thread)
        <div class="bg-white rounded-lg shadow-md mt-8">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Post a Reply</h3>
                
                <form action="{{ route('forums.posts.store', [$forum, $thread]) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Your Reply</label>
                        <textarea name="content" id="content" rows="6" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                                  placeholder="Write your reply here..." required>{{ old('content') }}</textarea>
                        @error('content')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="attachments" class="block text-sm font-medium text-gray-700 mb-2">Attachments (Optional)</label>
                        <input type="file" name="attachments[]" id="attachments" multiple 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt">
                        <p class="mt-1 text-sm text-gray-500">Max 3 files, 5MB each. Supported: images, documents</p>
                        @error('attachments')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-reply mr-2"></i>Post Reply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        @if($thread->is_locked)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-8">
                <div class="flex items-center">
                    <i class="fas fa-lock text-yellow-600 mr-2"></i>
                    <span class="text-yellow-800">This thread is locked and no longer accepting replies.</span>
                </div>
            </div>
        @endif
    @endcan
</div>

@push('scripts')
<script>
function vote(postId, voteType) {
    fetch(`/forums/posts/${postId}/vote`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ vote_type: voteType })
    })
    .then(response => response.json())
    .then(data => {
        const scoreElement = document.querySelector(`.vote-score[data-post-id="${postId}"]`);
        if (scoreElement) {
            scoreElement.textContent = data.score;
        }
        
        // Update button states
        const upBtn = document.querySelector(`button[onclick="vote(${postId}, 'up')"]`);
        const downBtn = document.querySelector(`button[onclick="vote(${postId}, 'down')"]`);
        
        if (upBtn && downBtn) {
            upBtn.classList.remove('text-green-600');
            downBtn.classList.remove('text-red-600');
            
            if (data.user_vote === 'up') {
                upBtn.classList.add('text-green-600');
            } else if (data.user_vote === 'down') {
                downBtn.classList.add('text-red-600');
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function markSolution(postId) {
    fetch(`/forums/posts/${postId}/solution`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function togglePin(threadId) {
    fetch(`/forums/{{ $forum->id }}/threads/${threadId}/pin`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function toggleLock(threadId) {
    fetch(`/forums/{{ $forum->id }}/threads/${threadId}/lock`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
@endpush
@endsection