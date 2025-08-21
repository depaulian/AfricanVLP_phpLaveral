@extends('layouts.admin')

@section('title', 'Forum Management')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Forum Management</h1>
            <p class="text-gray-600 mt-2">Manage community forums and discussions</p>
        </div>
        
        <div class="flex space-x-4">
            <a href="{{ route('admin.forums.moderation') }}" 
               class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-shield-alt mr-2"></i>Moderation
            </a>
            
            <a href="{{ route('admin.forums.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-plus mr-2"></i>Create Forum
            </a>
        </div>
    </div>

    <!-- Forums List -->
    @if($forums->isEmpty())
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-comments text-6xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Forums Created</h3>
            <p class="text-gray-500 mb-4">Create your first forum to get started with community discussions.</p>
            <a href="{{ route('admin.forums.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                <i class="fas fa-plus mr-2"></i>Create First Forum
            </a>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">All Forums</h2>
                    <div class="text-sm text-gray-500">
                        {{ $forums->count() }} forums total
                    </div>
                </div>
            </div>
            
            <div class="divide-y divide-gray-200">
                @foreach($forums as $forum)
                    <div class="p-6 hover:bg-gray-50 transition duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <a href="{{ route('admin.forums.show', $forum) }}" 
                                           class="hover:text-blue-600 transition duration-200">
                                            {{ $forum->name }}
                                        </a>
                                    </h3>
                                    
                                    @if($forum->is_private)
                                        <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                                            <i class="fas fa-lock mr-1"></i>Private
                                        </span>
                                    @endif
                                    
                                    <span class="bg-{{ $forum->status === 'active' ? 'green' : 'red' }}-100 text-{{ $forum->status === 'active' ? 'green' : 'red' }}-800 text-xs px-2 py-1 rounded-full">
                                        {{ ucfirst($forum->status) }}
                                    </span>
                                    
                                    @if($forum->category)
                                        <span class="bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full">
                                            {{ ucfirst($forum->category) }}
                                        </span>
                                    @endif
                                </div>
                                
                                @if($forum->description)
                                    <p class="text-gray-600 mb-3">{{ Str::limit($forum->description, 150) }}</p>
                                @endif
                                
                                <div class="flex items-center space-x-6 text-sm text-gray-500">
                                    <span>
                                        <i class="fas fa-comments mr-1"></i>
                                        {{ $forum->thread_count }} threads
                                    </span>
                                    <span>
                                        <i class="fas fa-reply mr-1"></i>
                                        {{ $forum->post_count }} posts
                                    </span>
                                    @if($forum->organization)
                                        <span>
                                            <i class="fas fa-building mr-1"></i>
                                            {{ $forum->organization->name }}
                                        </span>
                                    @endif
                                    @if($forum->moderator_ids && count($forum->moderator_ids) > 0)
                                        <span>
                                            <i class="fas fa-user-shield mr-1"></i>
                                            {{ count($forum->moderator_ids) }} moderators
                                        </span>
                                    @endif
                                    <span>
                                        <i class="fas fa-clock mr-1"></i>
                                        Updated {{ $forum->updated_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex items-center space-x-2 ml-4">
                                <a href="{{ route('admin.forums.analytics', $forum) }}" 
                                   class="text-gray-600 hover:text-blue-600 p-2" title="Analytics">
                                    <i class="fas fa-chart-bar"></i>
                                </a>
                                
                                <a href="{{ route('admin.forums.edit', $forum) }}" 
                                   class="text-gray-600 hover:text-blue-600 p-2" title="Edit Forum">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <button onclick="deleteForum({{ $forum->id }}, '{{ $forum->name }}')" 
                                        class="text-gray-600 hover:text-red-600 p-2" title="Delete Forum">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        @if($forum->threads->isNotEmpty())
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Threads</h4>
                                <div class="space-y-1">
                                    @foreach($forum->threads->take(3) as $thread)
                                        <div class="flex items-center justify-between text-sm">
                                            <a href="{{ route('admin.forums.show', $forum) }}" 
                                               class="text-blue-600 hover:text-blue-800 truncate flex-1">
                                                {{ Str::limit($thread->title, 50) }}
                                            </a>
                                            <span class="text-gray-400 ml-2">
                                                {{ $thread->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
function deleteForum(forumId, forumName) {
    if (confirm(`Are you sure you want to delete the forum "${forumName}"? This will also delete all threads and posts in this forum. This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/forums/${forumId}`;
        
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        
        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        form.appendChild(methodInput);
        form.appendChild(tokenInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection