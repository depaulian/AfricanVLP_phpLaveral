@extends('layouts.client')

@section('title', 'Provide Feedback')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Provide Feedback</h1>
        <p class="text-gray-600">Share your experience and help improve the volunteer program</p>
    </div>

    <!-- Assignment Info -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Assignment Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-600">Opportunity</p>
                <p class="text-gray-900">{{ $assignment->opportunity->title }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Organization</p>
                <p class="text-gray-900">{{ $assignment->organization->name }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Duration</p>
                <p class="text-gray-900">
                    {{ $assignment->start_date->format('M j, Y') }} - 
                    {{ $assignment->end_date ? $assignment->end_date->format('M j, Y') : 'Ongoing' }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Feedback Type</p>
                <p class="text-gray-900">{{ ucwords(str_replace('_', ' ', $feedbackType)) }}</p>
            </div>
        </div>
    </div>

    <!-- Feedback Form -->
    <form action="{{ route('client.volunteering.feedback.store') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="assignment_id" value="{{ $assignment->id }}">
        <input type="hidden" name="feedback_type" value="{{ $feedbackType }}">
        @if($template)
            <input type="hidden" name="template_id" value="{{ $template->id }}">
        @endif

        <!-- Template Info -->
        @if($template)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Using Template: {{ $template->name }}</h3>
                        @if($template->description)
                            <p class="mt-1 text-sm text-blue-700">{{ $template->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Rating Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ratings</h3>
            
            @if($template && $template->rating_categories)
                @foreach($template->rating_categories as $categoryKey => $category)
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $category['label'] }}
                            @if($category['required'] ?? false)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        @if($category['description'] ?? false)
                            <p class="text-sm text-gray-500 mb-2">{{ $category['description'] }}</p>
                        @endif
                        <div class="flex items-center space-x-2">
                            @for($i = 1; $i <= 5; $i++)
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="structured_ratings[{{ $categoryKey }}]" value="{{ $i }}" 
                                           class="sr-only rating-input" data-category="{{ $categoryKey }}">
                                    <svg class="w-8 h-8 text-gray-300 hover:text-yellow-400 rating-star" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                </label>
                            @endfor
                            <span class="ml-2 text-sm text-gray-500 rating-text" data-category="{{ $categoryKey }}">Click to rate</span>
                        </div>
                    </div>
                @endforeach
            @else
                <!-- Default rating categories -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Overall Rating *</label>
                    <div class="flex items-center space-x-2">
                        @for($i = 1; $i <= 5; $i++)
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="overall_rating" value="{{ $i }}" class="sr-only rating-input" data-category="overall">
                                <svg class="w-8 h-8 text-gray-300 hover:text-yellow-400 rating-star" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </label>
                        @endfor
                        <span class="ml-2 text-sm text-gray-500 rating-text" data-category="overall">Click to rate</span>
                    </div>
                </div>

                <!-- Additional rating categories -->
                @foreach(['communication' => 'Communication', 'reliability' => 'Reliability', 'skill' => 'Skills & Competence', 'attitude' => 'Attitude & Behavior', 'impact' => 'Impact & Contribution'] as $key => $label)
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ $label }}</label>
                        <div class="flex items-center space-x-2">
                            @for($i = 1; $i <= 5; $i++)
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="{{ $key }}_rating" value="{{ $i }}" class="sr-only rating-input" data-category="{{ $key }}">
                                    <svg class="w-8 h-8 text-gray-300 hover:text-yellow-400 rating-star" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                </label>
                            @endfor
                            <span class="ml-2 text-sm text-gray-500 rating-text" data-category="{{ $key }}">Click to rate</span>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Written Feedback Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Written Feedback</h3>
            
            <div class="space-y-6">
                <div>
                    <label for="positive_feedback" class="block text-sm font-medium text-gray-700 mb-2">
                        What went well? (Positive feedback)
                    </label>
                    <textarea id="positive_feedback" name="positive_feedback" rows="4" 
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Share what you appreciated or what worked well...">{{ old('positive_feedback') }}</textarea>
                </div>

                <div>
                    <label for="improvement_feedback" class="block text-sm font-medium text-gray-700 mb-2">
                        Areas for improvement
                    </label>
                    <textarea id="improvement_feedback" name="improvement_feedback" rows="4" 
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Suggest areas where things could be improved...">{{ old('improvement_feedback') }}</textarea>
                </div>

                <div>
                    <label for="additional_comments" class="block text-sm font-medium text-gray-700 mb-2">
                        Additional comments
                    </label>
                    <textarea id="additional_comments" name="additional_comments" rows="3" 
                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Any other thoughts or comments...">{{ old('additional_comments') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Tags Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tags</h3>
            <p class="text-sm text-gray-600 mb-4">Select tags that describe your experience:</p>
            
            <div id="tags-container" class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <!-- Tags will be loaded dynamically -->
            </div>
            
            <div class="mt-4">
                <label for="custom_tag" class="block text-sm font-medium text-gray-700 mb-2">Add custom tag</label>
                <div class="flex">
                    <input type="text" id="custom_tag" 
                           class="flex-1 rounded-l-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="Enter custom tag...">
                    <button type="button" id="add-custom-tag" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Add
                    </button>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Privacy Settings</h3>
            
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="is_anonymous" name="is_anonymous" type="checkbox" value="1"
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_anonymous" class="font-medium text-gray-700">Submit anonymously</label>
                        <p class="text-gray-500">Your name will not be shown with this feedback</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input id="is_public" name="is_public" type="checkbox" value="1"
                               class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_public" class="font-medium text-gray-700">Make feedback public</label>
                        <p class="text-gray-500">Allow others to see this feedback (subject to review)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Section -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('client.volunteering.dashboard') }}" 
               class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Submit Feedback
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Rating stars functionality
    const ratingInputs = document.querySelectorAll('.rating-input');
    const ratingStars = document.querySelectorAll('.rating-star');
    const ratingTexts = document.querySelectorAll('.rating-text');

    ratingInputs.forEach(input => {
        input.addEventListener('change', function() {
            const category = this.dataset.category;
            const value = parseInt(this.value);
            const categoryStars = document.querySelectorAll(`[data-category="${category}"] .rating-star`);
            const categoryText = document.querySelector(`[data-category="${category}"].rating-text`);

            // Update stars
            categoryStars.forEach((star, index) => {
                if (index < value) {
                    star.classList.remove('text-gray-300');
                    star.classList.add('text-yellow-400');
                } else {
                    star.classList.remove('text-yellow-400');
                    star.classList.add('text-gray-300');
                }
            });

            // Update text
            const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
            if (categoryText) {
                categoryText.textContent = ratingLabels[value] || 'Click to rate';
            }
        });
    });

    // Load available tags
    loadAvailableTags();

    // Custom tag functionality
    const customTagInput = document.getElementById('custom_tag');
    const addCustomTagBtn = document.getElementById('add-custom-tag');

    addCustomTagBtn.addEventListener('click', function() {
        const tagValue = customTagInput.value.trim();
        if (tagValue) {
            addTag(tagValue, true);
            customTagInput.value = '';
        }
    });

    customTagInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addCustomTagBtn.click();
        }
    });

    function loadAvailableTags() {
        const feedbackType = '{{ $feedbackType }}';
        
        fetch(`/client/volunteering/feedback/tags?feedback_type=${feedbackType}`)
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('tags-container');
                container.innerHTML = '';
                
                data.tags.forEach(tag => {
                    const tagElement = createTagElement(tag, false);
                    container.appendChild(tagElement);
                });
            })
            .catch(error => {
                console.error('Error loading tags:', error);
            });
    }

    function createTagElement(tag, isSelected) {
        const div = document.createElement('div');
        div.className = 'flex items-center';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = 'tags[]';
        checkbox.value = tag;
        checkbox.id = `tag_${tag}`;
        checkbox.className = 'focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded';
        checkbox.checked = isSelected;
        
        const label = document.createElement('label');
        label.htmlFor = `tag_${tag}`;
        label.className = 'ml-2 text-sm text-gray-700 cursor-pointer';
        label.textContent = tag;
        
        div.appendChild(checkbox);
        div.appendChild(label);
        
        return div;
    }

    function addTag(tag, isSelected) {
        const container = document.getElementById('tags-container');
        const tagElement = createTagElement(tag, isSelected);
        container.appendChild(tagElement);
    }
});
</script>
@endpush
@endsection