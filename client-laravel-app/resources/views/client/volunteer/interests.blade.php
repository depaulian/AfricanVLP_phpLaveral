@extends('layouts.app')

@section('title', 'Volunteer Interests')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Volunteer Interests
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Set your volunteering interests to get personalized opportunity recommendations
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('volunteer.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Interest Stats -->
    @if(isset($recommendedCount))
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        @if($recommendedCount > 0)
                            Based on your current interests, we found <strong>{{ $recommendedCount }}</strong> recommended opportunities for you.
                            <a href="{{ route('volunteer.opportunities', ['recommended' => 1]) }}" class="font-medium underline hover:text-blue-600">
                                View them here
                            </a>
                        @else
                            Select your interests below to get personalized volunteer opportunity recommendations.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Interest Categories -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="POST" action="{{ route('volunteer.interests.update') }}">
                @csrf
                
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Select Your Volunteering Interests
                        </h3>
                        <p class="text-sm text-gray-600 mb-6">
                            Choose the areas you're most interested in volunteering for. You can select multiple categories and adjust your interest level for each.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($categories as $category)
                            <div class="relative">
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200 {{ in_array($category->id, $userInterests) ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="category_{{ $category->id }}" 
                                                   name="categories[]" 
                                                   type="checkbox" 
                                                   value="{{ $category->id }}"
                                                   {{ in_array($category->id, $userInterests) ? 'checked' : '' }}
                                                   class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <label for="category_{{ $category->id }}" class="font-medium text-gray-900 cursor-pointer">
                                                {{ $category->name }}
                                            </label>
                                            @if($category->description)
                                                <p class="text-sm text-gray-500 mt-1">
                                                    {{ $category->description }}
                                                </p>
                                            @endif
                                            
                                            <!-- Interest Level -->
                                            <div class="mt-3 {{ in_array($category->id, $userInterests) ? '' : 'hidden' }}" id="interest_level_{{ $category->id }}">
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Interest Level</label>
                                                <select name="interest_levels[{{ $category->id }}]" class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                    <option value="1" {{ (isset($userInterests[$category->id]) && $userInterests[$category->id]->interest_level == 1) ? 'selected' : '' }}>
                                                        Low Interest
                                                    </option>
                                                    <option value="2" {{ (isset($userInterests[$category->id]) && $userInterests[$category->id]->interest_level == 2) ? 'selected' : '' }}>
                                                        Moderate Interest
                                                    </option>
                                                    <option value="3" {{ (isset($userInterests[$category->id]) && $userInterests[$category->id]->interest_level == 3) || (!isset($userInterests[$category->id])) ? 'selected' : '' }}>
                                                        High Interest
                                                    </option>
                                                    <option value="4" {{ (isset($userInterests[$category->id]) && $userInterests[$category->id]->interest_level == 4) ? 'selected' : '' }}>
                                                        Very High Interest
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @error('categories')
                        <div class="text-red-600 text-sm mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                    <a href="{{ route('volunteer.index') }}" 
                       class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        Save Interests
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tips Section -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Tips for Setting Your Interests</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-700">
                        <strong>Be specific:</strong> Select categories that truly interest you to get the most relevant opportunities.
                    </p>
                </div>
            </div>
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-700">
                        <strong>Interest levels matter:</strong> Higher interest levels will prioritize those opportunities in your recommendations.
                    </p>
                </div>
            </div>
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-700">
                        <strong>Update regularly:</strong> Your interests may change over time, so feel free to update them.
                    </p>
                </div>
            </div>
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-700">
                        <strong>Try new things:</strong> Don't be afraid to explore categories you haven't tried before.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle checkbox changes to show/hide interest level selectors
    const checkboxes = document.querySelectorAll('input[name="categories[]"]');
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const categoryId = this.value;
            const interestLevelDiv = document.getElementById('interest_level_' + categoryId);
            const parentDiv = this.closest('.border');
            
            if (this.checked) {
                interestLevelDiv.classList.remove('hidden');
                parentDiv.classList.add('border-blue-500', 'bg-blue-50');
                parentDiv.classList.remove('border-gray-200');
            } else {
                interestLevelDiv.classList.add('hidden');
                parentDiv.classList.remove('border-blue-500', 'bg-blue-50');
                parentDiv.classList.add('border-gray-200');
            }
        });
    });
});
</script>
@endsection