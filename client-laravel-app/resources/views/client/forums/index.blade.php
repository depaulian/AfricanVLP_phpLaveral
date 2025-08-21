@extends('layouts.client')

@section('title', 'Forums')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Community Forums</h1>
            <p class="text-gray-600 mt-2">Join discussions and connect with the community</p>
        </div>
        
        <div class="flex space-x-4">
            <a href="{{ route('forums.search') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="fas fa-search mr-2"></i>Search Forums
            </a>
        </div>
    </div>

    @if($forums->isEmpty())
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-comments text-6xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No Forums Available</h3>
            <p class="text-gray-500">There are no forums available for you to access at this time.</p>
        </div>
    @else
        @if($forumsByCategory->isNotEmpty())
            @foreach($forumsByCategory as $category => $categoryForums)
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-4 capitalize">
                        {{ $category ?: 'General' }} Forums
                    </h2>
                    
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($categoryForums as $forum)
                            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition duration-200">
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                                <a href="{{ route('forums.show', $forum) }}" 
                                                   class="hover:text-blue-600 transition duration-200">
                                                    {{ $forum->name }}
                                                </a>
                                            </h3>
                                            
                                            @if($forum->description)
                                                <p class="text-gray-600 text-sm mb-3">
                                                    {{ Str::limit($forum->description, 100) }}
                                                </p>
                                            @endif
                                        </div>
                                        
                                        @if($forum->is_private)
                                            <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full ml-2">
                                                <i class="fas fa-lock mr-1"></i>Private
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <div class="flex space-x-4">
                                            <span>
                                                <i class="fas fa-comments mr-1"></i>
                                                {{ $forum->thread_count }} threads
                                            </span>
                                            <span>
                                                <i class="fas fa-reply mr-1"></i>
                                                {{ $forum->post_count }} posts
                                            </span>
                                        </div>
                                        
                                        @if($forum->organization)
                                            <span class="text-blue-600">
                                                {{ $forum->organization->name }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($forum->threads->isNotEmpty())
                                        <div class="mt-4 pt-4 border-t border-gray-200">
                                            <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Threads</h4>
                                            @foreach($forum->threads->take(2) as $thread)
                                                <div class="flex items-center justify-between py-1">
                                                    <a href="{{ route('forums.threads.show', [$forum, $thread]) }}" 
                                                       class="text-sm text-blue-600 hover:text-blue-800 truncate flex-1">
                                                        {{ Str::limit($thread->title, 40) }}
                                                    </a>
                                                    <span class="text-xs text-gray-400 ml-2">
                                                        {{ $thread->created_at->diffForHumans() }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($forums as $forum)
                    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition duration-200">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                        <a href="{{ route('forums.show', $forum) }}" 
                                           class="hover:text-blue-600 transition duration-200">
                                            {{ $forum->name }}
                                        </a>
                                    </h3>
                                    
                                    @if($forum->description)
                                        <p class="text-gray-600 text-sm mb-3">
                                            {{ Str::limit($forum->description, 100) }}
                                        </p>
                                    @endif
                                </div>
                                
                                @if($forum->is_private)
                                    <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full ml-2">
                                        <i class="fas fa-lock mr-1"></i>Private
                                    </span>
                                @endif
                            </div>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <div class="flex space-x-4">
                                    <span>
                                        <i class="fas fa-comments mr-1"></i>
                                        {{ $forum->thread_count }} threads
                                    </span>
                                    <span>
                                        <i class="fas fa-reply mr-1"></i>
                                        {{ $forum->post_count }} posts
                                    </span>
                                </div>
                                
                                @if($forum->organization)
                                    <span class="text-blue-600">
                                        {{ $forum->organization->name }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection