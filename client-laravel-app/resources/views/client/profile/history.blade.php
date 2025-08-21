@extends('layouts.app')

@section('title', 'Volunteering History')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Volunteering History</h1>
                    <p class="text-gray-600 mt-1">Track your volunteering experience and build your portfolio</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="openAddModal()" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Add Experience
                    </button>
                    <a href="{{ route('profile.index') }}" 
                       class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        Back to Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Hours</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['total_hours'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Organizations</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['organizations_count'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Experiences</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $statistics['experiences_count'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Avg Rating</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ number_format($statistics['average_rating'], 1) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Experience Timeline</h3>
                        <p class="text-sm text-gray-600 mt-1">Your volunteering journey over time</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Filter Options -->
                        <select id="yearFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                        
                        <select id="organizationFilter" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Organizations</option>
                            @foreach($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                @if($experiences->count() > 0)
                    <div class="flow-root">
                        <ul class="-mb-8" id="experienceTimeline">
                            @foreach($experiences as $index => $experience)
                                <li class="experience-item" data-year="{{ $experience->start_date->year }}" data-organization="{{ $experience->organization_id }}">
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <!-- Timeline Icon -->
                                            <div>
                                                <span class="h-8 w-8 rounded-full 
                                                    {{ $experience->is_current ? 'bg-green-500' : 'bg-blue-500' }} 
                                                    flex items-center justify-center ring-8 ring-white">
                                                    @if($experience->is_current)
                                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    @else
                                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    @endif
                                                </span>
                                            </div>
                                            
                                            <!-- Experience Content -->
                                            <div class="min-w-0 flex-1">
                                                <div class="bg-gray-50 rounded-lg p-4">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <h4 class="text-lg font-semibold text-gray-900">{{ $experience->role }}</h4>
                                                            <p class="text-blue-600 font-medium">{{ $experience->organization->name }}</p>
                                                            
                                                            <!-- Duration and Status -->
                                                            <div class="flex items-center space-x-4 mt-2 text-sm text-gray-600">
                                                                <span>
                                                                    {{ $experience->start_date->format('M Y') }} - 
                                                                    {{ $experience->is_current ? 'Present' : $experience->end_date->format('M Y') }}
                                                                </span>
                                                                <span>•</span>
                                                                <span>{{ $experience->total_hours }} hours</span>
                                                                @if($experience->is_current)
                                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                        Current
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            
                                                            <!-- Description -->
                                                            @if($experience->description)
                                                                <p class="text-gray-700 mt-3">{{ $experience->description }}</p>
                                                            @endif
                                                            
                                                            <!-- Skills Used -->
                                                            @if($experience->skills_used)
                                                                <div class="mt-3">
                                                                    <p class="text-sm font-medium text-gray-700 mb-2">Skills Used:</p>
                                                                    <div class="flex flex-wrap gap-2">
                                                                        @foreach(explode(',', $experience->skills_used) as $skill)
                                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                                {{ trim($skill) }}
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            
                                                            <!-- Impact and Achievements -->
                                                            @if($experience->impact_description)
                                                                <div class="mt-3">
                                                                    <p class="text-sm font-medium text-gray-700 mb-1">Impact & Achievements:</p>
                                                                    <p class="text-gray-600 text-sm">{{ $experience->impact_description }}</p>
                                                                </div>
                                                            @endif
                                                            
                                                            <!-- Reference Contact -->
                                                            @if($experience->reference_name)
                                                                <div class="mt-3 p-3 bg-white rounded border">
                                                                    <p class="text-sm font-medium text-gray-700">Reference Contact:</p>
                                                                    <p class="text-sm text-gray-600">
                                                                        {{ $experience->reference_name }}
                                                                        @if($experience->reference_email)
                                                                            - {{ $experience->reference_email }}
                                                                        @endif
                                                                        @if($experience->reference_phone)
                                                                            - {{ $experience->reference_phone }}
                                                                        @endif
                                                                    </p>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        
                                                        <!-- Actions -->
                                                        <div class="flex items-center space-x-2 ml-4">
                                                            <button onclick="editExperience({{ $experience->id }})" 
                                                                    class="text-blue-600 hover:text-blue-800 p-2">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                </svg>
                                                            </button>
                                                            
                                                            <form action="{{ route('profile.history.destroy', $experience) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" 
                                                                        onclick="return confirm('Are you sure you want to delete this experience?')"
                                                                        class="text-red-600 hover:text-red-800 p-2">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    
                    <!-- Pagination -->
                    @if($experiences->hasPages())
                        <div class="mt-6">
                            {{ $experiences->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No volunteering experience yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by adding your first volunteering experience.</p>
                        <div class="mt-6">
                            <button onclick="openAddModal()" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Add Your First Experience
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Experience Modal -->
<div id="experienceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Add Volunteering Experience</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="experienceForm" method="POST" class="space-y-6">
            @csrf
            <div id="methodField"></div>
            
            <!-- Organization and Role -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="organization_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Organization *
                    </label>
                    <select id="organization_id" name="organization_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Organization</option>
                        @foreach($allOrganizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                        Role/Position *
                    </label>
                    <input type="text" id="role" name="role" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., Volunteer Coordinator">
                </div>
            </div>
            
            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Start Date *
                    </label>
                    <input type="date" id="start_date" name="start_date" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                        End Date
                    </label>
                    <input type="date" id="end_date" name="end_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="flex items-end">
                    <label for="is_current" class="flex items-center">
                        <input type="checkbox" id="is_current" name="is_current" value="1"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">Currently volunteering</span>
                    </label>
                </div>
            </div>
            
            <!-- Hours and Skills -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="total_hours" class="block text-sm font-medium text-gray-700 mb-2">
                        Total Hours
                    </label>
                    <input type="number" id="total_hours" name="total_hours" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="0">
                </div>
                
                <div>
                    <label for="skills_used" class="block text-sm font-medium text-gray-700 mb-2">
                        Skills Used
                    </label>
                    <input type="text" id="skills_used" name="skills_used"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="e.g., Project Management, Communication">
                    <p class="text-xs text-gray-500 mt-1">Separate multiple skills with commas</p>
                </div>
            </div>
            
            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea id="description" name="description" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Describe your role, responsibilities, and activities..."></textarea>
            </div>
            
            <!-- Impact -->
            <div>
                <label for="impact_description" class="block text-sm font-medium text-gray-700 mb-2">
                    Impact & Achievements
                </label>
                <textarea id="impact_description" name="impact_description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Describe the impact you made and any achievements..."></textarea>
            </div>
            
            <!-- Reference Contact -->
            <div class="border-t border-gray-200 pt-6">
                <h4 class="text-md font-medium text-gray-900 mb-4">Reference Contact (Optional)</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="reference_name" class="block text-sm font-medium text-gray-700 mb-2">
                            Name
                        </label>
                        <input type="text" id="reference_name" name="reference_name"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="reference_email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email
                        </label>
                        <input type="email" id="reference_email" name="reference_email"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="reference_phone" class="block text-sm font-medium text-gray-700 mb-2">
                            Phone
                        </label>
                        <input type="tel" id="reference_phone" name="reference_phone"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeModal()"
                        class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Save Experience
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Modal management
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Volunteering Experience';
    document.getElementById('experienceForm').action = '{{ route("profile.history.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('experienceForm').reset();
    document.getElementById('experienceModal').classList.remove('hidden');
    document.getElementById('experienceModal').classList.add('flex');
}

function editExperience(experienceId) {
    fetch(`/profile/history/${experienceId}`)
        .then(response => response.json())
        .then(experience => {
            document.getElementById('modalTitle').textContent = 'Edit Volunteering Experience';
            document.getElementById('experienceForm').action = `/profile/history/${experienceId}`;
            document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
            
            // Populate form fields
            document.getElementById('organization_id').value = experience.organization_id;
            document.getElementById('role').value = experience.role;
            document.getElementById('start_date').value = experience.start_date;
            document.getElementById('end_date').value = experience.end_date || '';
            document.getElementById('is_current').checked = experience.is_current;
            document.getElementById('total_hours').value = experience.total_hours || '';
            document.getElementById('skills_used').value = experience.skills_used || '';
            document.getElementById('description').value = experience.description || '';
            document.getElementById('impact_description').value = experience.impact_description || '';
            document.getElementById('reference_name').value = experience.reference_name || '';
            document.getElementById('reference_email').value = experience.reference_email || '';
            document.getElementById('reference_phone').value = experience.reference_phone || '';
            
            document.getElementById('experienceModal').classList.remove('hidden');
            document.getElementById('experienceModal').classList.add('flex');
        })
        .catch(error => {
            console.error('Error loading experience:', error);
            alert('Error loading experience details');
        });
}

function closeModal() {
    document.getElementById('experienceModal').classList.add('hidden');
    document.getElementById('experienceModal').classList.remove('flex');
}

// Current position checkbox handling
document.getElementById('is_current').addEventListener('change', function() {
    const endDateField = document.getElementById('end_date');
    if (this.checked) {
        endDateField.value = '';
        endDateField.disabled = true;
        endDateField.classList.add('bg-gray-100');
    } else {
        endDateField.disabled = false;
        endDateField.classList.remove('bg-gray-100');
    }
});

// Filtering functionality
document.getElementById('yearFilter').addEventListener('change', filterExperiences);
document.getElementById('organizationFilter').addEventListener('change', filterExperiences);

function filterExperiences() {
    const yearFilter = document.getElementById('yearFilter').value;
    const organizationFilter = document.getElementById('organizationFilter').value;
    const experienceItems = document.querySelectorAll('.experience-item');
    
    experienceItems.forEach(item => {
        let showItem = true;
        
        if (yearFilter && item.dataset.year !== yearFilter) {
            showItem = false;
        }
        
        if (organizationFilter && item.dataset.organization !== organizationFilter) {
            showItem = false;
        }
        
        item.style.display = showItem ? 'block' : 'none';
    });
}
</script>
@endpush
@endsection