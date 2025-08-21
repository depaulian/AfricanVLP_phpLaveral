@extends('layouts.client')

@section('title', 'Search Forums')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Search Header -->
        <div class="mb-8">
            <nav class="text-sm text-gray-500 mb-4">
                <a href="{{ route('forums.index') }}" class="hover:text-blue-600">Forums</a>
                <span class="mx-2">/</span>
                <span class="text-gray-900">Search</span>
            </nav>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Search Forums</h1>
            <p class="text-gray-600">Find discussions and topics across all accessible forums</p>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" action="{{ route('forums.search') }}" class="space-y-4">
                <div class="flex flex-col md:flex-row md:space-x-4 space-y-4 md:space-y-0">
                    <!-- Search Query -->
                    <div class="flex-1">
                        <label for="q" class="block text-sm font-medium text-gray-700 mb-2">Search Terms</label>
                        <input type="text" name="q" id="q" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" 
                               placeholder="Enter keywords to search for..."
                               value="{{ $query }}">
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="md:w-48">
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" id="category" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Categories</option>
                            <option value="general" {{ ($filters['category'] ?? '') === 'general' ? 'selected' : '' }}>General</option>
                            <option value="announcements" {{ ($filters['category'] ?? '') === 'announcements' ? 'selected' : '' }}>Announcements</option>
                            <option value="support" {{ ($filters['category'] ?? '') === 'support' ? 'selected' : '' }}>Support</option>
                            <option value="feedback" {{ ($filters['category'] ?? '') === 'feedback' ? 'selected' : '' }}>Feedback</option>
                            <option value="events" {{ ($filters['category'] ?? '') === 'events' ? 'selected' : '' }}>Events</option>
                            <option value="volunteering" {{ ($filters['category'] ?? '') === 'volunteering' ? 'selected' : '' }}>Volunteering</option>
                            <option value="alumni" {{ ($filters['category'] ?? '') === 'alumni' ? 'selected' : '' }}>Alumni</option>
                        </select>
                    </div>
                    
                    <!-- Search Button -->
                    <div class="md:w-32 flex items-end">
                        <button type="submit" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-200">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if($query || !empty(array_filter($filters)))
            <!-- Search Results -->
            <div class="space-y-8">
                <!-- Forums Results -->
                @if($forums->isNotEmpty())
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">
                                <i class="fas fa-comments mr-2 text-blue-600"></i>
                                Forums ({{ $forums->count() }})
                            </h2>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            @foreach($forums as $forum)
                                <div class="p-6 hover:bg-gray-50 transition duration-200">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                <a href="{{ route('forums.show', $forum) }}" 
                                                   class="hover:text-blue-600 transition duration-200">
                                                    {{ $forum->name }}
                                                </a>
                                                @if($forum->is_private)
                                                    <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                                                        <i class="fas fa-lock mr-1"></i>Private
                                                    </span>
                                                @endif
                                            </h3>
                                            
                                            @if($forum->description)
                                                <p class="text-gray-600 mb-3">{{ $forum->description }}</p>
                                            @endif
                                            
                                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                                <span>
                                                    <i class="fas fa-comments mr-1"></i>
                                                    {{ $forum->thread_count }} threads
                                                </span>
                                                <span>
                                                    <i class="fas fa-reply mr-1"></i>
                                                    {{ $forum->post_count }} posts
                                                </span>
                                                @if($forum->category)
                                                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs">
                                                        {{ ucfirst($forum->category) }}
                                                    </span>
                                                @endif
                                                @if($forum->organization)
                                                    <span class="text-blue-600">
                                                        {{ $forum->organization->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Threads Results -->
                @if($threads->isNotEmpty())
                    <div class="bg-white rounded-lg shadow-md">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-semibold text-gray-900">
                                <i class="fas fa-list mr-2 text-green-600"></i>
                                Threads ({{ $threads->count() }})
                            </h2>
                        </div>
                        
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
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between mb-2">
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                                        <a href="{{ route('forums.threads.show', [$thread->forum, $thread]) }}" 
                                                           class="hover:text-blue-600 transition duration-200">
                                                            {{ $thread->title }}
                                                        </a>
                                                    </h3>
                                                    
                                                    <div class="flex items-center space-x-4 text-sm text-gray-500 mb-2">
                                                        <span>
                                                            in <a href="{{ route('forums.show', $thread->forum) }}" 
                                                                  class="text-blue-600 hover:text-blue-800">{{ $thread->forum->name }}</a>
                                                        </span>
                                                        <span>
                                                            by <a href="#" class="text-blue-600 hover:text-blue-800">{{ $thread->author->name }}</a>
                                                        </span>
                                                        <span>{{ $thread->created_at->diffForHumans() }}</span>
                                                    </div>
                                                    
                                                    <div class="text-gray-600 text-sm mb-3">
                                                        {{ Str::limit(strip_tags($thread->content), 200) }}
                                                    </div>
                                                    
                                                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                                                        <span>
                                                            <i class="fas fa-eye mr-1"></i>{{ $thread->view_count }} views
                                                        </span>
                                                        <span>
                                                            <i class="fas fa-reply mr-1"></i>{{ $thread->reply_count }} replies
                                                        </span>
                                                        @if($thread->lastReplyBy)
                                                            <span>
                                                                Last reply by 
                                                                <a href="#" class="text-blue-600 hover:text-blue-800">{{ $thread->lastReplyBy->name }}</a>
                                                                {{ $thread->last_reply_at->diffForHumans() }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- No Results -->
                @if($forums->isEmpty() && $threads->isEmpty())
                    <div class="bg-white rounded-lg shadow-md p-8 text-center">
                        <div class="text-gray-400 mb-4">
                            <i class="fas fa-search text-6xl"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Results Found</h3>
                        <p class="text-gray-500 mb-4">
                            @if($query)
                                No forums or threads found matching "{{ $query }}"
                            @else
                                No results found for the selected filters
                            @endif
                        </p>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p>Try adjusting your search:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Use different keywords</li>
                                <li>Remove category filters</li>
                                <li>Check spelling</li>
                                <li>Use more general terms</li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <!-- Search Tips -->
            <div class="bg-white rounded-lg shadow-md p-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Search Tips
                </h2>
                
                <div class="grid md:grid-cols-2 gap-6 text-sm text-gray-700">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">Search Techniques</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start space-x-2">
                                <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                                <span>Use specific keywords related to your topic</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                                <span>Try different variations of your search terms</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                                <span>Use category filters to narrow down results</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-2">What You Can Find</h3>
                        <ul class="space-y-2">
                            <li class="flex items-start space-x-2">
                                <i class="fas fa-comments text-blue-500 mt-0.5"></i>
                                <span>Forums and discussion categories</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <i class="fas fa-list text-green-500 mt-0.5"></i>
                                <span>Individual threads and topics</span>
                            </li>
                            <li class="flex items-start space-x-2">
                                <i class="fas fa-users text-purple-500 mt-0.5"></i>
                                <span>Organization-specific discussions</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        You can only search forums and threads that you have permission to access. 
                        Private forums require organization membership.
                    </p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection