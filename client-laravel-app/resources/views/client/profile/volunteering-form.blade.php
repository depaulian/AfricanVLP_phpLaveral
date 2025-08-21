@extends('layouts.client')

@section('title', isset($history) ? 'Edit Volunteering Experience' : 'Add Volunteering Experience')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-4">
                <a href="{{ route('profile.volunteering.timeline') }}" 
                   class="text-blue-600 hover:text-blue-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ isset($history) ? 'Edit Volunteering Experience' : 'Add Volunteering Experience' }}
                    </h1>
                    <p class="text-gray-600 mt-2">Share your volunteering journey and impact</p>
                </div>
            </div>
        </div>

        <!-- Form -->
        <form action="{{ isset($history) ? route('profile.volunteering.update', $history) : route('profile.volunteering.store') }}" 
              method="POST" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @if(isset($history))
                @method('PUT')
            @endif

            <!-- Basic Information -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Basic Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Organization -->
                    <div>
                        <label for="organization_search" class="block text-sm font-medium text-gray-700 mb-2">
                            Organization *
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   id="organization_search" 
                                   name="organization_search"
                                   value="{{ old('organization_name', $history->organization_name ?? '') }}"
                                   placeholder="Search for organization or enter manually"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   required>
                            <input type="hidden" name="organization_id" id="organization_id" value="{{ old('organization_id', $history->organization_id ?? '') }}">
                            <input type="hidden" name="organization_name" id="organization_name" value="{{ old('organization_name', $history->organization_name ?? '') }}">
                            
                            <!-- Search results dropdown -->
                            <div id="organization_results" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                        @error('organization_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role Title -->
                    <div>
                        <label for="role_title" class="block text-sm font-medium text-gray-700 mb-2">
                            Role/Position *
                        </label>
                        <input type="text" 
                               id="role_title" 
                               name="role_title"
                               value="{{ old('role_title', $history->role_title ?? '') }}"
                               placeholder="e.g., Volunteer Coordinator, Event Assistant"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                        @error('role_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Start Date *
                        </label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date"
                               value="{{ old('start_date', $history->start_date ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               required>
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            End Date
                        </label>
                        <div class="space-y-2">
                            <input type="date" 
                                   id="end_date" 
                                   name="end_date"
                                   value="{{ old('end_date', $history->end_date ?? '') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       id="is_current" 
                                       name="is_current"
                                       value="1"
                                       {{ old('is_current', $history->is_current ?? false) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-600">I currently volunteer here</span>
                            </label>
                        </div>
                        @error('end_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Total Hours -->
                    <div>
                        <label for="total_hours" class="block text-sm font-medium text-gray-700 mb-2">
                            Total Hours
                        </label>
                        <input type="number" 
                               id="total_hours" 
                               name="total_hours"
                               value="{{ old('total_hours', $history->total_hours ?? '') }}"
                               placeholder="Estimated total hours"
                               min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('total_hours')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Hours Per Week -->
                    <div>
                        <label for="hours_per_week" class="block text-sm font-medium text-gray-700 mb-2">
                            Hours Per Week
                        </label>
                        <input type="number" 
                               id="hours_per_week" 
                               name="hours_per_week"
                               value="{{ old('hours_per_week', $history->hours_per_week ?? '') }}"
                               placeholder="Average hours per week"
                               min="0"
                               step="0.5"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('hours_per_week')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Description and Impact -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Description and Impact</h3>
                
                <div class="space-y-6">
                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea id="description" 
                                  name="description"
                                  rows="4"
                                  placeholder="Describe your role, responsibilities, and activities..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('description', $history->description ?? '') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Achievements -->
                    <div>
                        <label for="achievements" class="block text-sm font-medium text-gray-700 mb-2">
                            Key Achievements
                        </label>
                        <textarea id="achievements" 
                                  name="achievements"
                                  rows="3"
                                  placeholder="Highlight your key accomplishments and impact..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">{{ old('achievements', $history->achievements ?? '') }}</textarea>
                        @error('achievements')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Skills Gained -->
                    <div>
                        <label for="skills_gained" class="block text-sm font-medium text-gray-700 mb-2">
                            Skills Gained
                        </label>
                        <input type="text" 
                               id="skills_gained" 
                               name="skills_gained"
                               value="{{ old('skills_gained', is_array($history->skills_gained ?? '') ? implode(', ', $history->skills_gained) : $history->skills_gained ?? '') }}"
                               placeholder="e.g., Leadership, Communication, Project Management (comma-separated)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <p class="mt-1 text-sm text-gray-500">Separate multiple skills with commas</p>
                        @error('skills_gained')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Impact Area -->
                    <div>
                        <label for="impact_area" class="block text-sm font-medium text-gray-700 mb-2">
                            Impact Area
                        </label>
                        <select id="impact_area" 
                                name="impact_area"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">Select an impact area</option>
                            <option value="education" {{ old('impact_area', $history->impact_area ?? '') == 'education' ? 'selected' : '' }}>Education</option>
                            <option value="health" {{ old('impact_area', $history->impact_area ?? '') == 'health' ? 'selected' : '' }}>Health & Wellness</option>
                            <option value="environment" {{ old('impact_area', $history->impact_area ?? '') == 'environment' ? 'selected' : '' }}>Environment</option>
                            <option value="community" {{ old('impact_area', $history->impact_area ?? '') == 'community' ? 'selected' : '' }}>Community Development</option>
                            <option value="youth" {{ old('impact_area', $history->impact_area ?? '') == 'youth' ? 'selected' : '' }}>Youth Development</option>
                            <option value="elderly" {{ old('impact_area', $history->impact_area ?? '') == 'elderly' ? 'selected' : '' }}>Elderly Care</option>
                            <option value="animals" {{ old('impact_area', $history->impact_area ?? '') == 'animals' ? 'selected' : '' }}>Animal Welfare</option>
                            <option value="disaster" {{ old('impact_area', $history->impact_area ?? '') == 'disaster' ? 'selected' : '' }}>Disaster Relief</option>
                            <option value="arts" {{ old('impact_area', $history->impact_area ?? '') == 'arts' ? 'selected' : '' }}>Arts & Culture</option>
                            <option value="other" {{ old('impact_area', $history->impact_area ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('impact_area')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- References -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">References (Optional)</h3>
                
                <div id="references-container">
                    @if(isset($history) && $history->references)
                        @foreach($history->references as $index => $reference)
                        <div class="reference-item border border-gray-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="font-medium text-gray-900">Reference {{ $index + 1 }}</h4>
                                <button type="button" onclick="removeReference(this)" class="text-red-600 hover:text-red-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="text" name="references[{{ $index }}][name]" value="{{ $reference['name'] ?? '' }}" placeholder="Full Name" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="text" name="references[{{ $index }}][title]" value="{{ $reference['title'] ?? '' }}" placeholder="Job Title" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="email" name="references[{{ $index }}][email]" value="{{ $reference['email'] ?? '' }}" placeholder="Email Address" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <input type="tel" name="references[{{ $index }}][phone]" value="{{ $reference['phone'] ?? '' }}" placeholder="Phone Number" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <select name="references[{{ $index }}][relationship]" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="supervisor" {{ ($reference['relationship'] ?? '') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                                    <option value="colleague" {{ ($reference['relationship'] ?? '') == 'colleague' ? 'selected' : '' }}>Colleague</option>
                                    <option value="coordinator" {{ ($reference['relationship'] ?? '') == 'coordinator' ? 'selected' : '' }}>Coordinator</option>
                                    <option value="other" {{ ($reference['relationship'] ?? '') == 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <label class="flex items-center">
                                    <input type="checkbox" name="references[{{ $index }}][can_contact]" value="1" {{ ($reference['can_contact'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-600">Can be contacted</span>
                                </label>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
                
                <button type="button" onclick="addReference()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Reference
                </button>
            </div>

            <!-- Certificates -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Certificates & Documents (Optional)</h3>
                
                <div>
                    <label for="certificates" class="block text-sm font-medium text-gray-700 mb-2">
                        Upload Certificates or Documents
                    </label>
                    <input type="file" 
                           id="certificates" 
                           name="certificates[]"
                           multiple
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="mt-1 text-sm text-gray-500">Accepted formats: PDF, JPG, PNG, DOC, DOCX (max 5MB each)</p>
                </div>

                @if(isset($history) && $history->certificates)
                <div class="mt-4">
                    <h4 class="font-medium text-gray-900 mb-2">Existing Certificates</h4>
                    <div class="space-y-2">
                        @foreach($history->certificates as $cert)
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded">
                            <span class="text-sm text-gray-700">{{ $cert['filename'] }}</span>
                            <a href="{{ Storage::url($cert['path']) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-6">
                <a href="{{ route('profile.volunteering.timeline') }}" 
                   class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    {{ isset($history) ? 'Update Experience' : 'Save Experience' }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
let referenceCount = {{ isset($history) && $history->references ? count($history->references) : 0 }};

// Organization search functionality
document.getElementById('organization_search').addEventListener('input', function() {
    const query = this.value;
    if (query.length < 2) {
        document.getElementById('organization_results').classList.add('hidden');
        return;
    }

    fetch(`/api/organizations/search?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('organization_results');
            if (data.length > 0) {
                resultsDiv.innerHTML = data.map(org => 
                    `<div class="p-3 hover:bg-gray-100 cursor-pointer border-b" onclick="selectOrganization(${org.id}, '${org.name}')">
                        <div class="font-medium">${org.name}</div>
                        <div class="text-sm text-gray-600">${org.location || ''}</div>
                    </div>`
                ).join('');
                resultsDiv.classList.remove('hidden');
            } else {
                resultsDiv.innerHTML = '<div class="p-3 text-gray-500">No organizations found. You can enter manually.</div>';
                resultsDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error searching organizations:', error);
        });
});

function selectOrganization(id, name) {
    document.getElementById('organization_search').value = name;
    document.getElementById('organization_id').value = id;
    document.getElementById('organization_name').value = name;
    document.getElementById('organization_results').classList.add('hidden');
}

// Handle manual organization entry
document.getElementById('organization_search').addEventListener('blur', function() {
    setTimeout(() => {
        document.getElementById('organization_results').classList.add('hidden');
        // If no organization was selected, use the entered text as organization name
        if (!document.getElementById('organization_id').value) {
            document.getElementById('organization_name').value = this.value;
        }
    }, 200);
});

// Current position checkbox
document.getElementById('is_current').addEventListener('change', function() {
    const endDateInput = document.getElementById('end_date');
    if (this.checked) {
        endDateInput.value = '';
        endDateInput.disabled = true;
    } else {
        endDateInput.disabled = false;
    }
});

// Add reference functionality
function addReference() {
    const container = document.getElementById('references-container');
    const referenceHtml = `
        <div class="reference-item border border-gray-200 rounded-lg p-4 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-medium text-gray-900">Reference ${referenceCount + 1}</h4>
                <button type="button" onclick="removeReference(this)" class="text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" name="references[${referenceCount}][name]" placeholder="Full Name" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" name="references[${referenceCount}][title]" placeholder="Job Title" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="email" name="references[${referenceCount}][email]" placeholder="Email Address" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="tel" name="references[${referenceCount}][phone]" placeholder="Phone Number" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <select name="references[${referenceCount}][relationship]" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="supervisor">Supervisor</option>
                    <option value="colleague">Colleague</option>
                    <option value="coordinator">Coordinator</option>
                    <option value="other">Other</option>
                </select>
                <label class="flex items-center">
                    <input type="checkbox" name="references[${referenceCount}][can_contact]" value="1" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">Can be contacted</span>
                </label>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', referenceHtml);
    referenceCount++;
}

function removeReference(button) {
    button.closest('.reference-item').remove();
}

// Auto-calculate total hours based on dates and hours per week
function calculateTotalHours() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const hoursPerWeek = parseFloat(document.getElementById('hours_per_week').value) || 0;
    const isCurrent = document.getElementById('is_current').checked;
    
    if (startDate && hoursPerWeek > 0) {
        const start = new Date(startDate);
        const end = isCurrent ? new Date() : (endDate ? new Date(endDate) : new Date());
        
        const diffTime = Math.abs(end - start);
        const diffWeeks = Math.ceil(diffTime / (1000 * 60 * 60 * 24 * 7));
        
        const totalHours = Math.round(diffWeeks * hoursPerWeek);
        document.getElementById('total_hours').value = totalHours;
    }
}

// Add event listeners for auto-calculation
document.getElementById('start_date').addEventListener('change', calculateTotalHours);
document.getElementById('end_date').addEventListener('change', calculateTotalHours);
document.getElementById('hours_per_week').addEventListener('input', calculateTotalHours);
document.getElementById('is_current').addEventListener('change', calculateTotalHours);
</script>
@endpush