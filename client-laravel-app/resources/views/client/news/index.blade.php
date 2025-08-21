@extends('layouts.app')

@section('title', 'News')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Latest News
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Stay updated with the latest news and announcements
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" 
                           name="search" 
                           id="search"
                           value="{{ request('search') }}"
                           placeholder="Search news..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Organization Filter -->
                <div>
                    <label for="organization_id" class="block text-sm font-medium text-gray-700">Organization</label>
                    <select name="organization_id" id="organization_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Organizations</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}" {{ request('organization_id') == $organization->id ? 'selected' : '' }}>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Region Filter -->
                <div>
                    <label for="region_id" class="block text-sm font-medium text-gray-700">Region</label>
                    <select name="region_id" id="region_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Regions</option>
                        @foreach($regions as $region)
                            <option value="{{ $region->id }}" {{ request('region_id') == $region->id ? 'selected' : '' }}>
                                {{ $region->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Actions -->
                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Filter
                    </button>
                    <a href="{{ route('news.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <!-- Main Content -->
        <div class="lg:col-span-3">
            <!-- News Grid -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-2">
                @forelse($news as $article)
                    <div class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow duration-200">
                        @if($article->image)
                            <div class="aspect-w-16 aspect-h-9">
                                <img class="w-full h-48 object-cover" src="{{ $article->image_url }}" alt="{{ $article->title }}">
                            </div>
                        @endif
                        
                        <div class="p-6">
                            <div class="flex items-center text-sm text-gray-500 mb-2">
                                @if($article->organization)
                                    <span class="font-medium text-blue-600">{{ $article->organization->name }}</span>
                                    <span class="mx-2">•</span>
                                @endif
                                <time datetime="{{ $article->published_at->toISOString() }}">
                                    {{ $article->published_at->format('M j, Y') }}
                                </time>
                                @if($article->is_featured)
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Featured
                                    </span>
                                @endif
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <a href="{{ route('news.show', $article) }}" class="hover:text-blue-600">
                                    {{ $article->title }}
                                </a>
                            </h3>
                            
                            <p class="text-gray-600 text-sm mb-4">
                                {{ $article->excerpt }}
                            </p>
                            
                            <div class="flex items-center justify-between">
                                <a href="{{ route('news.show', $article) }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Read more →
                                </a>
                                <div class="flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    {{ $article->views_count }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No news found</h3>
                        <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($news->hasPages())
                <div class="mt-8">
                    {{ $news->links() }}
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Featured News -->
            @if($featuredNews->count() > 0)
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Featured News</h3>
                        <div class="space-y-4">
                            @foreach($featuredNews as $featured)
                                <div class="border-l-4 border-yellow-400 pl-4">
                                    <h4 class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('news.show', $featured) }}" class="hover:text-blue-600">
                                            {{ Str::limit($featured->title, 60) }}
                                        </a>
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $featured->published_at->format('M j, Y') }}
                                        @if($featured->organization)
                                            • {{ $featured->organization->name }}
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quick Stats -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">News Stats</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total Articles</span>
                            <span class="text-sm font-medium text-gray-900">{{ $news->total() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Organizations</span>
                            <span class="text-sm font-medium text-gray-900">{{ $organizations->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Regions</span>
                            <span class="text-sm font-medium text-gray-900">{{ $regions->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection