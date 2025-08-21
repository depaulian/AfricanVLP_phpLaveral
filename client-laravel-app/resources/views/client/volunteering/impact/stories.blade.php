@extends('layouts.client')

@section('title', 'Impact Stories')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Impact Stories</h1>
        <p class="text-gray-600">Inspiring stories of positive change from our volunteer community</p>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" 
                       placeholder="Search stories..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="organization_id" class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
                <select id="organization_id" name="organization_id" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Organizations</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>
                            {{ $org->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="story_type" class="block text-sm font-medium text-gray-700 mb-1">Story Type</label>
                <select id="story_type" name="story_type" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Types</option>
                    @foreach($storyTypes as $type => $label)
                        <option value="{{ $type }}" {{ request('story_type') == $type ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- Featured Stories -->
    @if($featuredStories->isNotEmpty() && !request()->hasAny(['search', 'organization_id', 'story_type']))
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Featured Stories</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                @foreach($featuredStories as $story)
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow">
                        @if($story->featured_image_url)
                            <div class="aspect-w-16 aspect-h-9">
                                <img src="{{ $story->featured_image_url }}" 
                                     alt="{{ $story->title }}"
                                     class="w-full h-48 object-cover">
                            </div>
                        @endif
                        
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $story->story_type_color }} bg-opacity-10">
                                    <i class="{{ $story->story_type_icon }} mr-1"></i>
                                    {{ $story->story_type_display }}
                                </span>
                                <span class="ml-2 inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                    <i class="fas fa-star mr-1"></i>
                                    Featured
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $story->title }}</h3>
                            <p class="text-gray-600 mb-4">{{ $story->excerpt }}</p>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-building mr-1"></i>
                                    {{ $story->organization->name }}
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-1"></i>
                                    {{ $story->story_date->format('M j, Y') }}
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span><i class="fas fa-eye mr-1"></i>{{ number_format($story->views_count) }}</span>
                                    <span><i class="fas fa-heart mr-1"></i>{{ number_format($story->likes_count) }}</span>
                                    <span><i class="fas fa-clock mr-1"></i>{{ $story->reading_time }} min read</span>
                                </div>
                                <a href="{{ route('client.volunteering.impact.story', $story) }}" 
                                   class="text-blue-600 hover:text-blue-800 font-medium">
                                    Read More →
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- All Stories -->
    <div>
        @if(!request()->hasAny(['search', 'organization_id', 'story_type']) && $featuredStories->isNotEmpty())
            <h2 class="text-2xl font-bold text-gray-900 mb-6">All Stories</h2>
        @endif
        
        @if($stories->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($stories as $story)
                    <div class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow">
                        @if($story->featured_image_url)
                            <div class="aspect-w-16 aspect-h-9">
                                <img src="{{ $story->featured_image_url }}" 
                                     alt="{{ $story->title }}"
                                     class="w-full h-48 object-cover">
                            </div>
                        @endif
                        
                        <div class="p-6">
                            <div class="flex items-center mb-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $story->story_type_color }} bg-opacity-10">
                                    <i class="{{ $story->story_type_icon }} mr-1"></i>
                                    {{ $story->story_type_display }}
                                </span>
                                @if($story->is_featured)
                                    <span class="ml-2 inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                        <i class="fas fa-star mr-1"></i>
                                        Featured
                                    </span>
                                @endif
                            </div>
                            
                            <h3 class="text-lg font-bold text-gray-900 mb-3">{{ $story->title }}</h3>
                            <p class="text-gray-600 mb-4 text-sm">{{ $story->excerpt }}</p>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-building mr-1"></i>
                                    <span class="truncate">{{ $story->organization->name }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-calendar mr-1"></i>
                                    {{ $story->story_date->format('M j') }}
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3 text-xs text-gray-500">
                                    <span><i class="fas fa-eye mr-1"></i>{{ number_format($story->views_count) }}</span>
                                    <span><i class="fas fa-heart mr-1"></i>{{ number_format($story->likes_count) }}</span>
                                    <span><i class="fas fa-clock mr-1"></i>{{ $story->reading_time }}m</span>
                                </div>
                                <a href="{{ route('client.volunteering.impact.story', $story) }}" 
                                   class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                    Read →
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $stories->withQueryString()->links() }}
            </div>
        @else
            <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
                <i class="fas fa-book-open text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Stories Found</h3>
                <p class="text-gray-500 mb-6">
                    @if(request()->hasAny(['search', 'organization_id', 'story_type']))
                        No stories match your current filters. Try adjusting your search criteria.
                    @else
                        There are no published impact stories yet. Check back soon for inspiring stories from our volunteer community!
                    @endif
                </p>
                @if(request()->hasAny(['search', 'organization_id', 'story_type']))
                    <a href="{{ route('client.volunteering.impact.stories') }}" 
                       class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        View All Stories
                    </a>
                @endif
            </div>
        @endif
    </div>

    <!-- Call to Action -->
    <div class="mt-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-8 text-white text-center">
        <h3 class="text-2xl font-bold mb-4">Have an Impact Story to Share?</h3>
        <p class="text-blue-100 mb-6">
            Your volunteer work creates positive change. Help inspire others by documenting and sharing your impact.
        </p>
        <div class="flex justify-center space-x-4">
            <a href="{{ route('client.volunteering.impact.create') }}" 
               class="bg-white text-blue-600 px-6 py-3 rounded-lg font-medium hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white">
                <i class="fas fa-plus mr-2"></i>
                Record Your Impact
            </a>
            <a href="{{ route('client.volunteering.impact.dashboard') }}" 
               class="bg-blue-700 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-white">
                <i class="fas fa-chart-line mr-2"></i>
                View Your Dashboard
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filters change
    const filterForm = document.querySelector('form');
    const filterSelects = filterForm.querySelectorAll('select');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });

    // Search input with debounce
    const searchInput = document.getElementById('search');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 3 || this.value.length === 0) {
                filterForm.submit();
            }
        }, 500);
    });
});
</script>
@endpush