@extends('layouts.app')

@section('title', 'Apply for Volunteer Opportunity')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center space-x-4">
                <a href="{{ route('client.volunteering.opportunities.show', $opportunity) }}" 
                   class="text-gray-600 hover:text-gray-900 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Apply for Volunteer Opportunity</h1>
                    <p class="text-gray-600">Submit your application for "{{ $opportunity->title }}"</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Application Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <form action="{{ route('client.volunteering.applications.store', $opportunity) }}" method="POST" class="space-y-6">
                        @csrf
                        
                        <!-- Cover Letter -->
                        <div>
                            <label for="cover_letter" class="block text-sm font-medium text-gray-700 mb-2">
                                Cover Letter <span class="text-red-500">*</span>
                            </label>
                            <textarea name="cover_letter" id="cover_letter" rows="6" required
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('cover_letter') border-red-500 @enderror"
                                      placeholder="Tell us why you're interested in this opportunity and what you can contribute...">{{ old('cover_letter') }}</textarea>
                            @error('cover_letter')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Availability -->
                        <div>
                            <label for="availability" class="block text-sm font-medium text-gray-700 mb-2">
                                Availability <span class="text-red-500">*</span>
                            </label>
                            <textarea name="availability" id="availability" rows="3" required
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('availability') border-red-500 @enderror"
                                      placeholder="Please describe your availability (days, times, duration)...">{{ old('availability') }}</textarea>
                            @error('availability')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Relevant Experience -->
                        <div>
                            <label for="experience" class="block text-sm font-medium text-gray-700 mb-2">
                                Relevant Experience
                            </label>
                            <textarea name="experience" id="experience" rows="4"
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('experience') border-red-500 @enderror"
                                      placeholder="Describe any relevant experience, skills, or qualifications...">{{ old('experience') }}</textarea>
                            @error('experience')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Skills -->
                        <div>
                            <label for="skills" class="block text-sm font-medium text-gray-700 mb-2">
                                Skills
                            </label>
                            <div class="space-y-2">
                                <input type="text" id="skill-input" 
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Type a skill and press Enter to add it">
                                <div id="skills-container" class="flex flex-wrap gap-2 min-h-[2rem]">
                                    <!-- Skills will be added here dynamically -->
                                </div>
                                <input type="hidden" name="skills" id="skills-hidden">
                            </div>
                            @error('skills')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="flex items-start space-x-3">
                            <input type="checkbox" id="terms" name="terms" required
                                   class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="terms" class="text-sm text-gray-700">
                                I agree to the <a href="#" class="text-blue-600 hover:text-blue-800">terms and conditions</a> 
                                and understand that this application will be reviewed by the organization.
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('client.volunteering.opportunities.show', $opportunity) }}" 
                               class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Submit Application
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Opportunity Summary -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Opportunity Summary</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $opportunity->title }}</h4>
                            <p class="text-sm text-gray-600">{{ $opportunity->organization->name }}</p>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-3">
                            <div>
                                <span class="text-xs font-medium text-gray-500">Category</span>
                                <p class="text-sm text-gray-900">{{ $opportunity->category->name }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500">Location</span>
                                <p class="text-sm text-gray-900">{{ $opportunity->location }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500">Duration</span>
                                <p class="text-sm text-gray-900">{{ $opportunity->duration }}</p>
                            </div>
                            <div>
                                <span class="text-xs font-medium text-gray-500">Time Commitment</span>
                                <p class="text-sm text-gray-900">{{ $opportunity->time_commitment }}</p>
                            </div>
                        </div>

                        @if($opportunity->application_deadline)
                        <div class="p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-sm text-yellow-800">
                                <strong>Application Deadline:</strong><br>
                                {{ $opportunity->application_deadline->format('M d, Y') }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Application Tips -->
                <div class="bg-blue-50 rounded-lg border border-blue-200 p-6">
                    <h3 class="text-lg font-semibold text-blue-900 mb-4">Application Tips</h3>
                    
                    <ul class="space-y-2 text-sm text-blue-800">
                        <li class="flex items-start space-x-2">
                            <svg class="w-4 h-4 mt-0.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Be specific about your motivation and interest</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <svg class="w-4 h-4 mt-0.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Highlight relevant skills and experience</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <svg class="w-4 h-4 mt-0.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Be clear about your availability</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <svg class="w-4 h-4 mt-0.5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span>Proofread your application before submitting</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const skillInput = document.getElementById('skill-input');
    const skillsContainer = document.getElementById('skills-container');
    const skillsHidden = document.getElementById('skills-hidden');
    let skills = [];

    // Load existing skills if any
    const oldSkills = @json(old('skills', []));
    if (oldSkills && oldSkills.length > 0) {
        skills = oldSkills;
        updateSkillsDisplay();
    }

    skillInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addSkill();
        }
    });

    function addSkill() {
        const skill = skillInput.value.trim();
        if (skill && !skills.includes(skill)) {
            skills.push(skill);
            skillInput.value = '';
            updateSkillsDisplay();
            updateHiddenInput();
        }
    }

    function removeSkill(skill) {
        skills = skills.filter(s => s !== skill);
        updateSkillsDisplay();
        updateHiddenInput();
    }

    function updateSkillsDisplay() {
        skillsContainer.innerHTML = '';
        skills.forEach(skill => {
            const skillTag = document.createElement('span');
            skillTag.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800';
            skillTag.innerHTML = `
                ${skill}
                <button type="button" class="ml-2 text-blue-600 hover:text-blue-800" onclick="removeSkill('${skill}')">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            `;
            skillsContainer.appendChild(skillTag);
        });
    }

    function updateHiddenInput() {
        skillsHidden.value = JSON.stringify(skills);
    }

    // Make removeSkill function global
    window.removeSkill = removeSkill;
});
</script>
@endpush