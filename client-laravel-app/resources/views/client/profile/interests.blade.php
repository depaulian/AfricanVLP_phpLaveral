@extends('layouts.app')

@section('title', 'Volunteering Interests')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Volunteering Interests</h1>
                    <p class="text-gray-600 mt-1">Select your volunteering interests to get matched with relevant opportunities</p>
                </div>
                <a href="{{ route('profile.index') }}" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    Back to Profile
                </a>
            </div>
        </div>

        <!-- Current Interests Summary -->
        @if($userInterests->count() > 0)
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Your Current Interests</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ $userInterests->count() }} {{ Str::plural('interest', $userInterests->count()) }} selected</p>
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap gap-2">
                        @foreach($userInterests as $interest)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ $interest->category->name }}
                                <button onclick="removeInterest({{ $interest->id }})" 
                                        class="ml-2 text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <!-- Interest Categories -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Categories List -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg sticky top-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Categories</h3>
                        <p class="text-sm text-gray-600 mt-1">Browse by category</p>
                    </div>
                    <div class="p-4">
                        <div class="space-y-2">
                            <button onclick="showAllCategories()" 
                                    class="w-full text-left px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors category-filter active"
                                    data-category="all">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">All Categories</span>
                                    <span class="text-sm text-gray-500">{{ $categories->sum('opportunities_count') }}</span>
                                </div>
                            </button>
                            
                            @foreach($categories as $category)
                                <button onclick="filterByCategory({{ $category->id }})" 
                                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors category-filter"
                                        data-category="{{ $category->id }}">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-700">{{ $category->name }}</span>
                                        <span class="text-sm text-gray-500">{{ $category->opportunities_count }}</span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Grid -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="categoriesGrid">
                    @foreach($categories as $category)
                        <div class="category-card bg-white shadow rounded-lg hover:shadow-lg transition-shadow" 
                             data-category="{{ $category->id }}">
                            <div class="p-6">
                                <!-- Category Header -->
                                <div class="flex items-start justify-between mb-4">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-gray-900">{{ $category->name }}</h4>
                                        <p class="text-sm text-gray-600 mt-1">{{ $category->description }}</p>
                                    </div>
                                    
                                    <!-- Interest Toggle -->
                                    <div class="ml-4">
                                        @php
                                            $isInterested = $userInterests->contains('volunteering_category_id', $category->id);
                                            $userInterest = $userInterests->firstWhere('volunteering_category_id', $category->id);
                                        @endphp
                                        
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" 
                                                   class="sr-only peer interest-toggle" 
                                                   data-category="{{ $category->id }}"
                                                   {{ $isInterested ? 'checked' : '' }}>
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Interest Level (shown when interested) -->
                                <div class="interest-level {{ $isInterested ? '' : 'hidden' }}" data-category="{{ $category->id }}">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Interest Level</label>
                                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 interest-level-select"
                                            data-category="{{ $category->id }}">
                                        <option value="low" {{ $userInterest && $userInterest->interest_level === 'low' ? 'selected' : '' }}>Low Interest</option>
                                        <option value="medium" {{ $userInterest && $userInterest->interest_level === 'medium' ? 'selected' : '' }}>Medium Interest</option>
                                        <option value="high" {{ $userInterest && $userInterest->interest_level === 'high' ? 'selected' : '' }}>High Interest</option>
                                    </select>
                                </div>

                                <!-- Category Stats -->
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Active Opportunities</span>
                                            <div class="font-semibold text-gray-900">{{ $category->opportunities_count }}</div>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Organizations</span>
                                            <div class="font-semibold text-gray-900">{{ $category->organizations_count }}</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sample Opportunities -->
                                @if($category->sample_opportunities && $category->sample_opportunities->count() > 0)
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <h5 class="text-sm font-medium text-gray-700 mb-2">Recent Opportunities</h5>
                                        <div class="space-y-2">
                                            @foreach($category->sample_opportunities->take(3) as $opportunity)
                                                <div class="text-sm">
                                                    <div class="font-medium text-gray-900">{{ Str::limit($opportunity->title, 40) }}</div>
                                                    <div class="text-gray-500">{{ $opportunity->organization->name }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Save Button -->
                <div class="mt-8 flex justify-center">
                    <button onclick="saveInterests()" 
                            class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Save Interest Preferences
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
        <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-gray-700">Saving preferences...</span>
    </div>
</div>

@push('scripts')
<script>
let pendingChanges = new Map();

// Category filtering
function showAllCategories() {
    document.querySelectorAll('.category-card').forEach(card => {
        card.style.display = 'block';
    });
    updateActiveFilter('all');
}

function filterByCategory(categoryId) {
    document.querySelectorAll('.category-card').forEach(card => {
        if (card.dataset.category === categoryId.toString()) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
    updateActiveFilter(categoryId);
}

function updateActiveFilter(categoryId) {
    document.querySelectorAll('.category-filter').forEach(filter => {
        filter.classList.remove('active', 'bg-blue-100', 'text-blue-800');
        if (filter.dataset.category === categoryId.toString()) {
            filter.classList.add('active', 'bg-blue-100', 'text-blue-800');
        }
    });
}

// Interest toggle handling
document.querySelectorAll('.interest-toggle').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const categoryId = this.dataset.category;
        const interestLevelDiv = document.querySelector(`.interest-level[data-category="${categoryId}"]`);
        const interestLevelSelect = document.querySelector(`.interest-level-select[data-category="${categoryId}"]`);
        
        if (this.checked) {
            interestLevelDiv.classList.remove('hidden');
            // Add to pending changes
            pendingChanges.set(categoryId, {
                action: 'add',
                interest_level: interestLevelSelect.value
            });
        } else {
            interestLevelDiv.classList.add('hidden');
            // Add to pending changes
            pendingChanges.set(categoryId, {
                action: 'remove'
            });
        }
        
        updateSaveButtonState();
    });
});

// Interest level change handling
document.querySelectorAll('.interest-level-select').forEach(select => {
    select.addEventListener('change', function() {
        const categoryId = this.dataset.category;
        const toggle = document.querySelector(`.interest-toggle[data-category="${categoryId}"]`);
        
        if (toggle.checked) {
            pendingChanges.set(categoryId, {
                action: 'add',
                interest_level: this.value
            });
            updateSaveButtonState();
        }
    });
});

function updateSaveButtonState() {
    const saveButton = document.querySelector('button[onclick="saveInterests()"]');
    if (pendingChanges.size > 0) {
        saveButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        saveButton.classList.add('bg-green-600', 'hover:bg-green-700');
        saveButton.textContent = `Save Changes (${pendingChanges.size})`;
    } else {
        saveButton.classList.remove('bg-green-600', 'hover:bg-green-700');
        saveButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
        saveButton.textContent = 'Save Interest Preferences';
    }
}

function saveInterests() {
    if (pendingChanges.size === 0) {
        return;
    }
    
    const loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.classList.remove('hidden');
    loadingOverlay.classList.add('flex');
    
    const changes = Array.from(pendingChanges.entries()).map(([categoryId, change]) => ({
        category_id: parseInt(categoryId),
        ...change
    }));
    
    fetch('{{ route("profile.interests.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ changes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear pending changes
            pendingChanges.clear();
            updateSaveButtonState();
            
            // Show success message
            showNotification('Interest preferences saved successfully!', 'success');
            
            // Optionally reload the page to update the current interests summary
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification('Error saving preferences: ' + (data.message || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error saving interests:', error);
        showNotification('Error saving preferences. Please try again.', 'error');
    })
    .finally(() => {
        loadingOverlay.classList.add('hidden');
        loadingOverlay.classList.remove('flex');
    });
}

function removeInterest(interestId) {
    if (confirm('Are you sure you want to remove this interest?')) {
        fetch(`{{ route("profile.interests.destroy", "") }}/${interestId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Interest removed successfully!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification('Error removing interest: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error removing interest:', error);
            showNotification('Error removing interest. Please try again.', 'error');
        });
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'
    }`;
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                ${type === 'success' 
                    ? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>'
                    : '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>'
                }
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove notification after 5 seconds
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
</script>
@endpush
@endsection