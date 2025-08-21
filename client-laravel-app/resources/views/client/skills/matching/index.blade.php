@extends('layouts.client')

@section('title', 'Skills & Interests Matching')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Skills & Interests Matching</h1>
        <p class="text-gray-600">Discover opportunities that match your skills and interests, get skill recommendations, and build your volunteer profile.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Matching Opportunities</p>
                    <p class="text-2xl font-semibold text-gray-900" id="matching-count">{{ $matchingOpportunities->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Your Skills</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ auth()->user()->skills()->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Your Interests</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ auth()->user()->volunteeringInterests()->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Verified Skills</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ auth()->user()->skills()->where('verified', true)->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Matching Opportunities -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">Matching Opportunities</h2>
                        <button class="text-blue-600 hover:text-blue-800 text-sm font-medium" onclick="loadMoreOpportunities()">
                            View All
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div id="matching-opportunities" class="space-y-4">
                        @forelse($matchingOpportunities as $opportunity)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900 mb-1">
                                            <a href="{{ route('volunteering.show', $opportunity) }}" class="hover:text-blue-600">
                                                {{ $opportunity->title }}
                                            </a>
                                        </h3>
                                        <p class="text-sm text-gray-600 mb-2">{{ $opportunity->organization->name }}</p>
                                        <p class="text-sm text-gray-500 mb-3">{{ $opportunity->formatted_location }}</p>
                                        
                                        @if(!empty($opportunity->matched_skills))
                                            <div class="flex flex-wrap gap-1 mb-2">
                                                @foreach($opportunity->matched_skills as $skill)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        {{ $skill['skill'] }}
                                                        @if($skill['verified'])
                                                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4 text-right">
                                        <div class="text-lg font-semibold text-green-600">
                                            {{ number_format($opportunity->skill_match_score, 0) }}%
                                        </div>
                                        <div class="text-xs text-gray-500">Match</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No matching opportunities</h3>
                                <p class="mt-1 text-sm text-gray-500">Add more skills to your profile to find matching opportunities.</p>
                                <div class="mt-6">
                                    <a href="{{ route('profile.skills.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        Add Skills
                                    </a>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Skill Gap Analysis -->
            @if(!empty($skillGaps['skill_gaps']))
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Skill Gap Analysis</h2>
                        <p class="text-sm text-gray-600 mt-1">Skills that could improve your volunteer opportunities</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach(array_slice($skillGaps['skill_gaps'], 0, 5) as $gap)
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $gap['skill'] }}</h4>
                                        <p class="text-sm text-gray-600">{{ $gap['demand_count'] }} opportunities require this skill</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <div class="text-sm font-medium text-orange-600">
                                            Priority: {{ number_format($gap['priority'], 0) }}
                                        </div>
                                        <button class="text-blue-600 hover:text-blue-800 text-sm font-medium" onclick="addSkillToProfile('{{ $gap['skill'] }}')">
                                            Add Skill
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Trending Skills -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Trending Skills</h3>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        @foreach(array_slice($trendingSkills, 0, 8) as $skill)
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">{{ $skill['skill'] }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs text-gray-500">{{ $skill['demand_count'] }}</span>
                                    <button class="text-blue-600 hover:text-blue-800 text-xs" onclick="addSkillToProfile('{{ $skill['skill'] }}')">
                                        Add
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Skill Suggestions -->
            @if(!empty($skillSuggestions))
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Suggested Skills</h3>
                        <p class="text-sm text-gray-600 mt-1">Based on your profile</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach(array_slice($skillSuggestions, 0, 5) as $suggestion)
                                <div class="p-3 border border-gray-200 rounded-lg">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-900">{{ $suggestion['skill_name'] }}</span>
                                        <button class="text-blue-600 hover:text-blue-800 text-xs" onclick="addSkillToProfile('{{ $suggestion['skill_name'] }}')">
                                            Add
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-600">{{ implode(', ', $suggestion['reasons']) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('profile.skills.index') }}" class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Manage Skills
                    </a>
                    <a href="{{ route('profile.interests.index') }}" class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Update Interests
                    </a>
                    <button onclick="showImportModal()" class="block w-full text-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Import Skills
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Skills Modal -->
<div id="importModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Import Skills</h3>
                <button onclick="hideImportModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="space-y-4">
                <button onclick="importFromResume()" class="w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="font-medium text-gray-900">From Resume/CV</div>
                    <div class="text-sm text-gray-600">Upload your resume to extract skills</div>
                </button>
                
                <button onclick="importFromLinkedIn()" class="w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="font-medium text-gray-900">From LinkedIn</div>
                    <div class="text-sm text-gray-600">Connect your LinkedIn profile</div>
                </button>
                
                <button onclick="importFromFile()" class="w-full text-left p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="font-medium text-gray-900">From File</div>
                    <div class="text-sm text-gray-600">Upload CSV or JSON file</div>
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function loadMoreOpportunities() {
    // Implementation for loading more opportunities
    console.log('Loading more opportunities...');
}

function addSkillToProfile(skillName) {
    // Implementation for adding skill to profile
    console.log('Adding skill:', skillName);
}

function showImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
}

function hideImportModal() {
    document.getElementById('importModal').classList.add('hidden');
}

function importFromResume() {
    // Implementation for resume import
    console.log('Import from resume');
    hideImportModal();
}

function importFromLinkedIn() {
    // Implementation for LinkedIn import
    console.log('Import from LinkedIn');
    hideImportModal();
}

function importFromFile() {
    // Implementation for file import
    console.log('Import from file');
    hideImportModal();
}
</script>
@endpush