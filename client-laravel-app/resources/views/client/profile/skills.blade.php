@extends('layouts.app')

@section('title', 'Manage Skills')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Manage Skills</h1>
                    <p class="text-gray-600 mt-1">Add and manage your skills to help organizations find you</p>
                </div>
                <a href="{{ route('profile.index') }}" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    Back to Profile
                </a>
            </div>
        </div>

        <!-- Add New Skill -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Add New Skill</h3>
                <p class="text-sm text-gray-600 mt-1">Search and add skills that match your expertise</p>
            </div>
            <div class="p-6">
                <form action="{{ route('profile.skills.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Skill Search -->
                        <div class="md:col-span-2">
                            <label for="skill_search" class="block text-sm font-medium text-gray-700 mb-2">
                                Search Skills
                            </label>
                            <div class="relative">
                                <input type="text" id="skill_search" name="skill_name" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Type to search skills..."
                                       autocomplete="off">
                                <div id="skill_suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                                    <!-- Suggestions will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- Proficiency Level -->
                        <div>
                            <label for="proficiency_level" class="block text-sm font-medium text-gray-700 mb-2">
                                Proficiency Level
                            </label>
                            <select id="proficiency_level" name="proficiency_level" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="beginner">Beginner</option>
                                <option value="intermediate" selected>Intermediate</option>
                                <option value="advanced">Advanced</option>
                                <option value="expert">Expert</option>
                            </select>
                        </div>
                    </div>

                    <!-- Years of Experience -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="years_experience" class="block text-sm font-medium text-gray-700 mb-2">
                                Years of Experience
                            </label>
                            <input type="number" id="years_experience" name="years_experience" min="0" max="50"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="0">
                        </div>

                        <div>
                            <label for="is_verified" class="flex items-center mt-8">
                                <input type="checkbox" id="is_verified" name="is_verified" value="1"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">I can provide verification for this skill</span>
                            </label>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description (Optional)
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Describe your experience with this skill..."></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                            Add Skill
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Skills -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Your Skills</h3>
                <p class="text-sm text-gray-600 mt-1">Manage your current skills and proficiency levels</p>
            </div>
            <div class="p-6">
                @if($skills->count() > 0)
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($skills as $skill)
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <h4 class="text-lg font-medium text-gray-900">{{ $skill->name }}</h4>
                                            
                                            <!-- Proficiency Badge -->
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $skill->proficiency_level === 'expert' ? 'bg-purple-100 text-purple-800' : '' }}
                                                {{ $skill->proficiency_level === 'advanced' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $skill->proficiency_level === 'intermediate' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $skill->proficiency_level === 'beginner' ? 'bg-gray-100 text-gray-800' : '' }}">
                                                {{ ucfirst($skill->proficiency_level) }}
                                            </span>

                                            <!-- Verification Status -->
                                            @if($skill->is_verified)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                    </svg>
                                                    Verified
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Experience and Description -->
                                        <div class="mt-2 space-y-1">
                                            @if($skill->years_experience)
                                                <p class="text-sm text-gray-600">
                                                    <span class="font-medium">Experience:</span> {{ $skill->years_experience }} {{ Str::plural('year', $skill->years_experience) }}
                                                </p>
                                            @endif
                                            
                                            @if($skill->description)
                                                <p class="text-sm text-gray-600">{{ $skill->description }}</p>
                                            @endif
                                        </div>

                                        <!-- Proficiency Progress Bar -->
                                        <div class="mt-3">
                                            <div class="flex items-center justify-between text-sm text-gray-600 mb-1">
                                                <span>Proficiency Level</span>
                                                <span>{{ ucfirst($skill->proficiency_level) }}</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="h-2 rounded-full
                                                    {{ $skill->proficiency_level === 'expert' ? 'bg-purple-600 w-full' : '' }}
                                                    {{ $skill->proficiency_level === 'advanced' ? 'bg-green-600 w-3/4' : '' }}
                                                    {{ $skill->proficiency_level === 'intermediate' ? 'bg-blue-600 w-1/2' : '' }}
                                                    {{ $skill->proficiency_level === 'beginner' ? 'bg-gray-600 w-1/4' : '' }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        <button onclick="editSkill({{ $skill->id }})" 
                                                class="text-blue-600 hover:text-blue-800 p-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        
                                        <form action="{{ route('profile.skills.destroy', $skill) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    onclick="return confirm('Are you sure you want to remove this skill?')"
                                                    class="text-red-600 hover:text-red-800 p-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    @if($skills->hasPages())
                        <div class="mt-6">
                            {{ $skills->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No skills added yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by adding your first skill above.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Edit Skill Modal -->
<div id="editSkillModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Edit Skill</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form id="editSkillForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label for="edit_proficiency_level" class="block text-sm font-medium text-gray-700 mb-2">
                    Proficiency Level
                </label>
                <select id="edit_proficiency_level" name="proficiency_level" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                    <option value="expert">Expert</option>
                </select>
            </div>

            <div>
                <label for="edit_years_experience" class="block text-sm font-medium text-gray-700 mb-2">
                    Years of Experience
                </label>
                <input type="number" id="edit_years_experience" name="years_experience" min="0" max="50"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-2">
                    Description
                </label>
                <textarea id="edit_description" name="description" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
            </div>

            <div>
                <label for="edit_is_verified" class="flex items-center">
                    <input type="checkbox" id="edit_is_verified" name="is_verified" value="1"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <span class="ml-2 text-sm text-gray-700">I can provide verification for this skill</span>
                </label>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeEditModal()"
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Update Skill
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Skill search functionality
let skillSearchTimeout;
const skillSearch = document.getElementById('skill_search');
const skillSuggestions = document.getElementById('skill_suggestions');

skillSearch.addEventListener('input', function(e) {
    clearTimeout(skillSearchTimeout);
    const query = e.target.value.trim();
    
    if (query.length < 2) {
        skillSuggestions.classList.add('hidden');
        return;
    }
    
    skillSearchTimeout = setTimeout(() => {
        fetch(`/api/skills/search?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                displaySkillSuggestions(data);
            })
            .catch(error => {
                console.error('Error searching skills:', error);
            });
    }, 300);
});

function displaySkillSuggestions(skills) {
    if (skills.length === 0) {
        skillSuggestions.classList.add('hidden');
        return;
    }
    
    skillSuggestions.innerHTML = skills.map(skill => 
        `<div class="px-4 py-2 hover:bg-gray-100 cursor-pointer" onclick="selectSkill('${skill.name}')">
            <div class="font-medium">${skill.name}</div>
            ${skill.category ? `<div class="text-sm text-gray-500">${skill.category}</div>` : ''}
        </div>`
    ).join('');
    
    skillSuggestions.classList.remove('hidden');
}

function selectSkill(skillName) {
    skillSearch.value = skillName;
    skillSuggestions.classList.add('hidden');
}

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    if (!skillSearch.contains(e.target) && !skillSuggestions.contains(e.target)) {
        skillSuggestions.classList.add('hidden');
    }
});

// Edit skill functionality
function editSkill(skillId) {
    fetch(`/profile/skills/${skillId}`)
        .then(response => response.json())
        .then(skill => {
            document.getElementById('edit_proficiency_level').value = skill.proficiency_level;
            document.getElementById('edit_years_experience').value = skill.years_experience || '';
            document.getElementById('edit_description').value = skill.description || '';
            document.getElementById('edit_is_verified').checked = skill.is_verified;
            
            document.getElementById('editSkillForm').action = `/profile/skills/${skillId}`;
            document.getElementById('editSkillModal').classList.remove('hidden');
            document.getElementById('editSkillModal').classList.add('flex');
        })
        .catch(error => {
            console.error('Error loading skill:', error);
            alert('Error loading skill details');
        });
}

function closeEditModal() {
    document.getElementById('editSkillModal').classList.add('hidden');
    document.getElementById('editSkillModal').classList.remove('flex');
}
</script>
@endpush
@endsection