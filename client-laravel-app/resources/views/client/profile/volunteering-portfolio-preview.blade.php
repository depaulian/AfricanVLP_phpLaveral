@extends('layouts.client')

@section('title', 'Volunteering Portfolio Preview')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Portfolio Preview</h1>
                    <p class="text-gray-600 mt-2">Preview your volunteering portfolio before export</p>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('profile.volunteering.timeline') }}" 
                       class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                        Back to Timeline
                    </a>
                    <button onclick="exportPortfolio('pdf')" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Export as PDF
                    </button>
                    <button onclick="exportPortfolio('json')" 
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        Export as JSON
                    </button>
                </div>
            </div>
        </div>

        <!-- Portfolio Statistics -->
        @if(isset($statistics))
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Portfolio Statistics</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $statistics['total_experiences'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Total Experiences</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $statistics['portfolio_experiences'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Portfolio Ready</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $statistics['total_pages_estimated'] ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Estimated Pages</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">{{ $statistics['file_size_estimated'] ?? 'N/A' }}</div>
                    <div class="text-sm text-gray-600">Estimated Size</div>
                </div>
            </div>
        </div>
        @endif

        <!-- Portfolio Cover -->
        <div class="bg-white rounded-lg shadow-md p-8 mb-8 text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-2">{{ $portfolio['portfolio_info']['title'] }}</h2>
            <p class="text-xl text-gray-600 mb-4">{{ $portfolio['portfolio_info']['subtitle'] }}</p>
            <div class="text-lg text-gray-700">
                <p class="font-semibold">{{ $portfolio['volunteer_profile']['name'] }}</p>
                <p class="text-gray-600">Member since {{ $portfolio['volunteer_profile']['member_since'] }}</p>
                <div class="mt-4 inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 rounded-full">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $portfolio['portfolio_info']['verification_level'] }} Level Verification
                </div>
            </div>
        </div>

        <!-- Executive Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Executive Summary</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ number_format($portfolio['executive_summary']['total_volunteer_hours']) }}</div>
                    <div class="text-sm text-gray-600">Total Hours</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">{{ $portfolio['executive_summary']['organizations_served'] }}</div>
                    <div class="text-sm text-gray-600">Organizations</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600">{{ $portfolio['executive_summary']['impact_score'] }}</div>
                    <div class="text-sm text-gray-600">Impact Score</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-orange-600">${{ number_format($portfolio['executive_summary']['economic_value_contributed']) }}</div>
                    <div class="text-sm text-gray-600">Economic Value</div>
                </div>
            </div>

            @if($portfolio['executive_summary']['people_directly_helped'] > 0 || $portfolio['executive_summary']['funds_raised'] > 0)
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h4 class="font-semibold text-gray-900 mb-4">Direct Impact Metrics</h4>
                <div class="grid grid-cols-3 gap-4">
                    @if($portfolio['executive_summary']['people_directly_helped'] > 0)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-600">{{ number_format($portfolio['executive_summary']['people_directly_helped']) }}</div>
                        <div class="text-sm text-gray-600">People Helped</div>
                    </div>
                    @endif
                    @if($portfolio['executive_summary']['funds_raised'] > 0)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-600">${{ number_format($portfolio['executive_summary']['funds_raised'], 2) }}</div>
                        <div class="text-sm text-gray-600">Funds Raised</div>
                    </div>
                    @endif
                    @if($portfolio['executive_summary']['events_organized'] > 0)
                    <div class="text-center">
                        <div class="text-2xl font-bold text-indigo-600">{{ $portfolio['executive_summary']['events_organized'] }}</div>
                        <div class="text-sm text-gray-600">Events Organized</div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Skills and Competencies -->
        @if(!empty($portfolio['skills_and_competencies']['core_skills']))
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Skills & Competencies</h3>
            <div class="mb-6">
                <h4 class="font-semibold text-gray-900 mb-3">Core Skills</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($portfolio['skills_and_competencies']['core_skills'] as $skill)
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">{{ $skill }}</span>
                    @endforeach
                </div>
            </div>
            
            @if(!empty($portfolio['skills_and_competencies']['transferable_skills']))
            <div>
                <h4 class="font-semibold text-gray-900 mb-3">Transferable Skills</h4>
                <div class="flex flex-wrap gap-2">
                    @foreach($portfolio['skills_and_competencies']['transferable_skills'] as $skill)
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">{{ $skill }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Volunteer Experiences -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Volunteer Experiences</h3>
            <div class="space-y-6">
                @foreach($portfolio['volunteer_experiences'] as $experience)
                <div class="border-l-4 border-blue-500 pl-6 pb-6">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">{{ $experience['role_title'] }}</h4>
                            <p class="text-blue-600 font-medium">{{ $experience['organization'] }}</p>
                        </div>
                        @if($experience['verification_status'] === 'Verified')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Verified
                        </span>
                        @endif
                    </div>
                    
                    <div class="text-sm text-gray-600 mb-3">
                        {{ $experience['period'] }} • {{ $experience['hours_contributed'] }} hours • Impact Score: {{ $experience['impact_score'] }}
                    </div>
                    
                    @if($experience['description'])
                    <p class="text-gray-700 mb-3">{{ $experience['description'] }}</p>
                    @endif
                    
                    @if($experience['key_achievements'])
                    <div class="mb-3">
                        <h5 class="font-medium text-gray-900 mb-1">Key Achievements</h5>
                        <p class="text-gray-700">{{ $experience['key_achievements'] }}</p>
                    </div>
                    @endif
                    
                    @if($experience['impact_metrics']['people_helped'] > 0 || $experience['impact_metrics']['funds_raised'] > 0 || $experience['impact_metrics']['events_organized'] > 0)
                    <div class="mb-3">
                        <h5 class="font-medium text-gray-900 mb-2">Impact Metrics</h5>
                        <div class="flex space-x-4 text-sm">
                            @if($experience['impact_metrics']['people_helped'] > 0)
                            <span class="text-red-600">{{ number_format($experience['impact_metrics']['people_helped']) }} people helped</span>
                            @endif
                            @if($experience['impact_metrics']['funds_raised'] > 0)
                            <span class="text-yellow-600">${{ number_format($experience['impact_metrics']['funds_raised'], 2) }} raised</span>
                            @endif
                            @if($experience['impact_metrics']['events_organized'] > 0)
                            <span class="text-indigo-600">{{ $experience['impact_metrics']['events_organized'] }} events organized</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    @if(!empty($experience['skills_gained']))
                    <div class="mb-3">
                        <h5 class="font-medium text-gray-900 mb-2">Skills Gained</h5>
                        <div class="flex flex-wrap gap-1">
                            @foreach(array_slice($experience['skills_gained'], 0, 8) as $skill)
                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">{{ $skill }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    @if(!empty($experience['certificates']) || !empty($experience['recognitions']))
                    <div class="flex space-x-4 text-sm text-gray-600">
                        @if(!empty($experience['certificates']))
                        <span>{{ count($experience['certificates']) }} {{ Str::plural('certificate', count($experience['certificates'])) }}</span>
                        @endif
                        @if(!empty($experience['recognitions']))
                        <span>{{ count($experience['recognitions']) }} {{ Str::plural('recognition', count($experience['recognitions'])) }}</span>
                        @endif
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Achievements and Recognition -->
        @if(!empty($portfolio['achievements_and_recognition']['recent_achievements']))
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Recent Achievements & Recognition</h3>
            <div class="space-y-4">
                @foreach($portfolio['achievements_and_recognition']['recent_achievements'] as $achievement)
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        @if($achievement['type'] === 'certificate')
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                        </div>
                        @else
                        <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                        </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">{{ $achievement['title'] }}</h4>
                        <p class="text-sm text-gray-600">{{ $achievement['organization'] }} • {{ \Carbon\Carbon::parse($achievement['date'])->format('M Y') }}</p>
                        @if(isset($achievement['description']) && $achievement['description'])
                        <p class="text-sm text-gray-700 mt-1">{{ $achievement['description'] }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Export Actions -->
        <div class="bg-gray-50 rounded-lg p-6 text-center">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ready to Export Your Portfolio?</h3>
            <p class="text-gray-600 mb-6">Choose your preferred format to download your comprehensive volunteering portfolio.</p>
            <div class="flex justify-center space-x-4">
                <button onclick="exportPortfolio('pdf')" 
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export as PDF
                </button>
                <button onclick="exportPortfolio('json')" 
                        class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    Export as JSON
                </button>
                <button onclick="sharePortfolio()" 
                        class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors font-medium">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
                    </svg>
                    Share Portfolio
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="shareModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Share Your Portfolio</h3>
                <button onclick="closeShareModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Public Portfolio Link</label>
                <div class="flex">
                    <input type="text" id="shareUrl" readonly 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50">
                    <button onclick="copyShareUrl()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Copy
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">This link allows others to view your portfolio without signing in.</p>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function exportPortfolio(format) {
    const url = `{{ route('profile.volunteering.export-portfolio') }}?format=${format}`;
    window.open(url, '_blank');
}

function sharePortfolio() {
    const shareUrl = `{{ route('profile.volunteering.public-portfolio', ['user' => auth()->id(), 'token' => hash('sha256', auth()->id() . auth()->user()->email . config('app.key') . 'portfolio')]) }}`;
    document.getElementById('shareUrl').value = shareUrl;
    document.getElementById('shareModal').classList.remove('hidden');
}

function closeShareModal() {
    document.getElementById('shareModal').classList.add('hidden');
}

function copyShareUrl() {
    const shareUrl = document.getElementById('shareUrl');
    shareUrl.select();
    shareUrl.setSelectionRange(0, 99999);
    document.execCommand('copy');
    
    // Show feedback
    const button = event.target;
    const originalText = button.textContent;
    button.textContent = 'Copied!';
    button.classList.add('bg-green-600');
    button.classList.remove('bg-blue-600');
    
    setTimeout(() => {
        button.textContent = originalText;
        button.classList.remove('bg-green-600');
        button.classList.add('bg-blue-600');
    }, 2000);
}

// Close modal when clicking outside
document.getElementById('shareModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeShareModal();
    }
});
</script>
@endpush