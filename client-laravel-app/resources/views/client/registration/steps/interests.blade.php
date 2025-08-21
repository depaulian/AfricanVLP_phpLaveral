<!-- Interests Step -->
<div class="space-y-6">
    <div>
        <h4 class="text-lg font-medium text-gray-900 mb-4">What causes are you passionate about?</h4>
        <p class="text-sm text-gray-600 mb-6">Select the areas where you'd like to make a difference. You can choose multiple categories.</p>
    </div>

    <!-- Interest Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($volunteeringCategories as $category)
            @php
                $isSelected = in_array($category->id, old('interests', $userInterests->pluck('volunteering_category_id')->toArray()));
                $userInterest = $userInterests->firstWhere('volunteering_category_id', $category->id);
            @endphp
            
            <div class="interest-category border-2 rounded-lg p-4 cursor-pointer transition-all hover:shadow-md
                {{ $isSelected ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}"
                 onclick="toggleInterest({{ $category->id }})">
                
                <!-- Category Icon -->
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-3 rounded-full
                    {{ $isSelected ? 'bg-blue-100' : 'bg-gray-100' }}">
                    @switch($category->slug)
                        @case('education')
                            <svg class="w-6 h-6 {{ $isSelected ? 'text-blue-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            @break
                        @case('healthcare')
                            <svg class="w-6 h-6 {{ $isSelected ? 'text-blue-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            @break
                        @case('environment')
                            <svg class="w-6 h-6 {{ $isSelected ? 'text-blue-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            @break
                        @default
                            <svg class="w-6 h-6 {{ $isSelected ? 'text-blue-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                    @endswitch
                </div>
                
                <!-- Category Info -->
                <div class="text-center">
                    <h5 class="font-medium text-gray-900 mb-1">{{ $category->name }}</h5>
                    <p class="text-xs text-gray-600 mb-3">{{ Str::limit($category->description, 60) }}</p>
                    
                    <!-- Checkbox -->
                    <input type="checkbox" name="interests[]" value="{{ $category->id }}" 
                           {{ $isSelected ? 'checked' : '' }}
                           class="interest-checkbox hidden">
                    
                    <!-- Interest Level (shown when selected) -->
                    <div class="interest-level {{ $isSelected ? '' : 'hidden' }}" data-category="{{ $category->id }}">
                        <select name="interest_levels[{{ $category->id }}]" 
                                class="w-full px-2 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="low" {{ $userInterest && $userInterest->interest_level === 'low' ? 'selected' : '' }}>Low Interest</option>
                            <option value="medium" {{ $userInterest && $userInterest->interest_level === 'medium' ? 'selected' : '' }}>Medium Interest</option>
                            <option value="high" {{ $userInterest && $userInterest->interest_level === 'high' ? 'selected' : '' }}>High Interest</option>
                        </select>
                    </div>
                    
                    <!-- Stats -->
                    <div class="text-xs text-gray-500 mt-2">
                        {{ $category->opportunities_count ?? 0 }} opportunities
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Selected Interests Summary -->
    <div id="selectedSummary" class="bg-green-50 border border-green-200 rounded-lg p-4 hidden">
        <h5 class="font-medium text-green-800 mb-2">Selected Interests</h5>
        <div id="selectedList" class="flex flex-wrap gap-2"></div>
    </div>

    <!-- Additional Preferences -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-lg font-medium text-gray-900 mb-4">Additional Preferences</h4>
        
        <div class="space-y-4">
            <!-- Commitment Level -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Preferred Commitment Level
                </label>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="commitment_level" value="one_time" 
                               {{ old('commitment_level', $user->profile?->commitment_level) === 'one_time' ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">One-time</div>
                            <div class="text-xs text-gray-600">Single events or projects</div>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="commitment_level" value="short_term" 
                               {{ old('commitment_level', $user->profile?->commitment_level) === 'short_term' ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Short-term</div>
                            <div class="text-xs text-gray-600">Few weeks to months</div>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-3 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="radio" name="commitment_level" value="long_term" 
                               {{ old('commitment_level', $user->profile?->commitment_level) === 'long_term' ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Long-term</div>
                            <div class="text-xs text-gray-600">Ongoing commitment</div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Group vs Individual -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Preferred Working Style
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="prefers_group_work" value="1" 
                               {{ old('prefers_group_work', $user->profile?->prefers_group_work) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">I enjoy working in groups</span>
                    </label>
                    
                    <label class="flex items-center">
                        <input type="checkbox" name="prefers_individual_work" value="1" 
                               {{ old('prefers_individual_work', $user->profile?->prefers_individual_work) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-gray-700">I prefer working independently</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Motivation -->
    <div>
        <label for="motivation" class="block text-sm font-medium text-gray-700 mb-2">
            What motivates you to volunteer? (Optional)
        </label>
        <textarea id="motivation" name="motivation" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Share what drives your passion for volunteering...">{{ old('motivation', $user->profile?->motivation) }}</textarea>
        <p class="text-sm text-gray-500 mt-1">This helps organizations understand your values and match you with meaningful opportunities.</p>
    </div>
</div>

<script>
function toggleInterest(categoryId) {
    const categoryDiv = document.querySelector(`[onclick="toggleInterest(${categoryId})"]`);
    const checkbox = categoryDiv.querySelector('.interest-checkbox');
    const interestLevel = categoryDiv.querySelector('.interest-level');
    
    // Toggle checkbox
    checkbox.checked = !checkbox.checked;
    
    // Update visual state
    if (checkbox.checked) {
        categoryDiv.classList.remove('border-gray-200', 'hover:border-gray-300');
        categoryDiv.classList.add('border-blue-500', 'bg-blue-50');
        categoryDiv.querySelector('.w-12').classList.remove('bg-gray-100');
        categoryDiv.querySelector('.w-12').classList.add('bg-blue-100');
        categoryDiv.querySelector('svg').classList.remove('text-gray-600');
        categoryDiv.querySelector('svg').classList.add('text-blue-600');
        interestLevel.classList.remove('hidden');
    } else {
        categoryDiv.classList.remove('border-blue-500', 'bg-blue-50');
        categoryDiv.classList.add('border-gray-200', 'hover:border-gray-300');
        categoryDiv.querySelector('.w-12').classList.remove('bg-blue-100');
        categoryDiv.querySelector('.w-12').classList.add('bg-gray-100');
        categoryDiv.querySelector('svg').classList.remove('text-blue-600');
        categoryDiv.querySelector('svg').classList.add('text-gray-600');
        interestLevel.classList.add('hidden');
    }
    
    updateSelectedSummary();
}

function updateSelectedSummary() {
    const selectedCheckboxes = document.querySelectorAll('.interest-checkbox:checked');
    const summaryDiv = document.getElementById('selectedSummary');
    const listDiv = document.getElementById('selectedList');
    
    if (selectedCheckboxes.length > 0) {
        summaryDiv.classList.remove('hidden');
        listDiv.innerHTML = '';
        
        selectedCheckboxes.forEach(checkbox => {
            const categoryDiv = checkbox.closest('.interest-category');
            const categoryName = categoryDiv.querySelector('h5').textContent;
            
            const badge = document.createElement('span');
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
            badge.textContent = categoryName;
            
            listDiv.appendChild(badge);
        });
    } else {
        summaryDiv.classList.add('hidden');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedSummary();
});
</script>