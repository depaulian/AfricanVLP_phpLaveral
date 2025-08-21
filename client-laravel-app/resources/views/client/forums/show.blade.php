@extends('layouts.client')

@section('title', $forum->name . ' - Forums')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Forum Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-start mb-4">
            <div class="flex-1">
                <nav class="text-sm text-gray-500 mb-2">
                    <a href="{{ route('forums.index') }}" class="hover:text-blue-600">Forums</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">{{ $forum->name }}</span>
                </nav>
                
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $forum->name }}</h1>
                
                @if($forum->description)
                    <p class="text-gray-600">{{ $forum->description }}</p>
                @endif
                
                <div class="flex items-center space-x-6 mt-4 text-sm text-gray-500">
                    <span>
                        <i class="fas fa-comments mr-1"></i>
                        {{ $forumStats['total_threads'] }} threads
                    </span>
                    <span>
                        <i class="fas fa-reply mr-1"></i>
                        {{ $forumStats['total_posts'] }} posts
                    </span>
                    @if($forum->organization)
                        <span>
                            <i class="fas fa-building mr-1"></i>
                            {{ $forum->organization->name }}
                        </span>
                    @endif
                    @if($forum->is_private)
                        <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">
                            <i class="fas fa-lock mr-1"></i>Private
                        </span>
                    @endif
                </div>
            </div>
            
            @can('createThread', $forum)
                <a href="{{ route('forums.threads.create', $forum) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200">
                    <i class="fas fa-plus mr-2"></i>New Thread
                </a>
            @endcan
        </div>
    </div>

    <!-- Threads List -->
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Discussions</h2>
        </div>
        
        @if($threads->isEmpty())
            <div class="p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-comments text-4xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-700 mb-2">No threads yet</h3>
                <p class="text-gray-500 mb-4">Be the first to start a discussion in this forum.</p>
                
                @can('createThread', $forum)
                    <a href="{{ route('forums.threads.create', $forum) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                        Start a Discussion
                    </a>
                @endcan
            </div>
        @else
            <div class="divide-y divide-gray-200">
                @foreach($threads as $thread)
                    <div class="p-6 hover:bg-gray-50 transition duration-200">
                        <div class="flex items-start space-x-4">
                            <!-- Thread Status Icons -->
                            <div class="flex flex-col items-center space-y-1 mt-1">
                                @if($thread->is_pinned)
                                    <i class="fas fa-thumbtack text-green-600" title="Pinned"></i>
                                @endif
                                @if($thread->is_locked)
                                    <i class="fas fa-lock text-red-600" title="Locked"></i>
                                @endif
                                @if($thread->hasSolution())
                                    <i class="fas fa-check-circle text-green-600" title="Has Solution"></i>
                                @endif
                            </div>
                            
                            <!-- Thread Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                            <a href="{{ route('forums.threads.show', [$forum, $thread]) }}" 
                                               class="hover:text-blue-600 transition duration-200">
                                                {{ $thread->title }}
                                            </a>
                                        </h3>
                                        
                                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                                            <span>
                                                by <a href="#" class="text-blue-600 hover:text-blue-800">{{ $thread->author->name }}</a>
                                            </span>
                                            <span>{{ $thread->created_at->diffForHumans() }}</span>
                                            <span>
                                                <i class="fas fa-eye mr-1"></i>{{ $thread->view_count }} views
                                            </span>
                                            <span>
                                                <i class="fas fa-reply mr-1"></i>{{ $thread->reply_count }} replies
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Last Reply Info -->
                                    @if($thread->lastReplyBy)
                                        <div class="text-right text-sm text-gray-500 ml-4">
                                            <div>Last reply by</div>
                                            <a href="#" class="text-blue-600 hover:text-blue-800">{{ $thread->lastReplyBy->name }}</a>
                                            <div>{{ $thread->last_reply_at->diffForHumans() }}</div>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Thread Preview -->
                                <div class="mt-2 text-gray-600 text-sm">
                                    {{ Str::limit(strip_tags($thread->content), 150) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            <div class="p-6 border-t border-gray-200">
                {{ $threads->links() }}
            </div>
        @endif
    </div>
</div>
@endsection