@extends('layouts.client')

@section('title', 'Volunteer Opportunities')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Make a Difference</h1>
                <p class="text-xl mb-8">Discover meaningful volunteer opportunities that match your skills and interests</p>
                
                <!-- Search Bar -->
                <div class="max-w-2xl mx-auto">
                    <form action="{{ route('client.volunteering.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                            <input type="text" 
                                   name="search" 
                                   value="{{ $filters['search'] ?? '' }}"
                                   placeholder="Search opportunities..." 
                                   class="w-full px-4 py-3 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-300">
                        </div>
                        <div class="md:w-48">
                            <select name="category_id" class="w-full px-4 py-3 rounded-lg text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-8 py-3 rounded-lg transition duration-200">
                            Search
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar Filters -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h3 class="text-lg font-semibold mb-4">Filter Opportunities</h3>
                    
                    <form action="{{ route('client.volunteering.index') }}" method="GET" id="filterForm">
                        <input type="hidden" name="search" value="{{ $filters['search'] ?? '' }}">
                        
                        <!-- Location Type -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Location Type</label>
                            <div class="space-y-2">
                                @foreach(['onsite' => 'On-site', 'remote' => 'Remote', 'hybrid' => 'Hybrid'] as $value => $label)
                                    <label class="flex items-center">
                                        <input type="radio" name="location_type" value="{{ $value }}" 
                                               {{ ($filters['location_type'] ?? '') == $value ? 'checked' : '' }}
                                               class="mr-2 text-blue-600">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Experience Level -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Experience Level</label>
                            <select name="experience_level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Any Level</option>
                                @foreach(['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced', 'expert' => 'Expert'] as $value => $label)
                                    <option value="{{ $value }}" {{ ($filters['experience_level'] ?? '') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Location -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                            <select name="country_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Any Country</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ ($filters['country_id'] ?? '') == $country->id ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Featured Only -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input type="checkbox" name="featured" value="1" 
                                       {{ ($filters['featured'] ?? '') ? 'checked' : '' }}
                                       class="mr-2 text-blue-600">
                                Featured opportunities only
                            </label>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition duration-200">
                            Apply Filters
                        </button>
                        
                        @if(array_filter($filters))
                            <a href="{{ route('client.volunteering.index') }}" class="block text-center mt-2 text-blue-600 hover:text-blue-800">
                                Clear Filters
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:w-3/4">
                <!-- Recommended Opportunities (for authenticated users) -->
                @auth
                    @if($recommendedOpportunities->isNotEmpty())
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Recommended for You</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                @foreach($recommendedOpportunities->take(4) as $opportunity)
                                    @include('client.volunteering.partials.opportunity-card', ['opportunity' => $opportunity, 'showMatchScore' => true])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endauth

                <!-- Featured Opportunities -->
                @if($featuredOpportunities->isNotEmpty())
                    <div class="mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Featured Opportunities</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                            @foreach($featuredOpportunities as $opportunity)
                                @include('client.volunteering.partials.opportunity-card', ['opportunity' => $opportunity, 'featured' => true])
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- All Opportunities -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">
                        All Opportunities
                        <span class="text-sm font-normal text-gray-600">({{ $opportunities->total() }} found)</span>
                    </h2>
                    
                    <!-- Sort Options -->
                    <div class="flex items-center space-x-2">
                        <label class="text-sm text-gray-600">Sort by:</label>
                        <select name="sort_by" onchange="updateSort()" class="px-3 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="created_at" {{ ($filters['sort_by'] ?? 'created_at') == 'created_at' ? 'selected' : '' }}>Newest</option>
                            <option value="deadline" {{ ($filters['sort_by'] ?? '') == 'deadline' ? 'selected' : '' }}>Deadline</option>
                            <option value="title" {{ ($filters['sort_by'] ?? '') == 'title' ? 'selected' : '' }}>Title</option>
                            <option value="organization" {{ ($filters['sort_by'] ?? '') == 'organization' ? 'selected' : '' }}>Organization</option>
                        </select>
                    </div>
                </div>

                <!-- Opportunities Grid -->
                @if($opportunities->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        @foreach($opportunities as $opportunity)
                            @include('client.volunteering.partials.opportunity-card', ['opportunity' => $opportunity])
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div class="flex justify-center">
                        {{ $opportunities->appends($filters)->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No opportunities found</h3>
                        <p class="text-gray-600 mb-4">Try adjusting your search criteria or browse all opportunities.</p>
                        <a href="{{ route('client.volunteering.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition duration-200">
                            View All Opportunities
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function updateSort() {
    const sortBy = event.target.value;
    const url = new URL(window.location);
    url.searchParams.set('sort_by', sortBy);
    window.location.href = url.toString();
}

// Auto-submit filter form on change
document.querySelectorAll('#filterForm input, #filterForm select').forEach(element => {
    element.addEventListener('change', function() {
        if (this.type !== 'text') {
            document.getElementById('filterForm').submit();
        }
    });
});
</script>
@endpush
@endsection