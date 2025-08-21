@extends('layouts.client')

@section('title', 'Volunteering Timeline')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Volunteering Timeline</h1>
                    <p class="text-gray-600 mt-2">Your complete volunteering journey and impact</p>
                </div>
                <div class="flex space-x-4">
                    <a href="{{ route('profile.volunteering.create') }}" 
                       class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Add Experience
                    </a>
                    <button onclick="exportPortfolio()" 
                            class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        Export Portfolio
                    </button>
                </div>
            </div>
        </div>

        <!-- Impact Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Hours</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($impact['total_hours']) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Organizations</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $impact['total_organizations'] }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Verified Hours</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($impact['verified_hours']) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Economic Value</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($impact['estimated_economic_value']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Skills Gained -->
        @if(!empty($impact['skills_gained']))
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Skills Gained</h3>
            <div class="flex flex-wrap gap-2">
                @foreach(array_slice($impact['skills_gained'], 0, 15) as $skill)
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                    {{ $skill }}
                </span>
                @endforeach
                @if(count($impact['skills_gained']) > 15)
                <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                    +{{ count($impact['skills_gained']) - 15 }} more
                </span>
                @endif
            </div>
        </div>
        @endif

        <!-- Timeline -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Experience Timeline</h3>
            
            @if(empty($timeline))
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No volunteering history</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by adding your first volunteering experience.</p>
                <div class="mt-6">
                    <a href="{{ route('profile.volunteering.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Add Experience
                    </a>
                </div>
            </div>
            @else
            <div class="relative">
                <!-- Timeline line -->
                <div class="absolute left-8 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                
                @foreach($timeline as $yearData)
                <div class="mb-8">
                    <!-- Year header -->
                    <div class="flex items-center mb-4">
                        <div class="flex items-center justify-center w-16 h-16 bg-blue-600 text-white rounded-full font-bold text-lg relative z-10">
                            {{ $yearData['year'] }}
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold text-gray-900">{{ $yearData['year'] }}</h4>
                            <p class="text-sm text-gray-600">
                                {{ $yearData['total_entries'] }} {{ Str::plural('experience', $yearData['total_entries']) }} • 
                                {{ number_format($yearData['total_hours']) }} hours
                            </p>
                        </div>
                    </div>
                    
                    <!-- Year entries -->
                    <div class="ml-20 space-y-4">
                        @foreach($yearData['entries'] as $entry)
                        <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-2">
                                        <h5 class="font-semibold text-gray-900">{{ $entry['role'] }}</h5>
                                        @if($entry['is_verified'])
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Verified
                                        </span>
                                        @endif
                                    </div>
                                    <p class="text-blue-600 font-medium mb-1">{{ $entry['organization'] }}</p>
                                    <p class="text-sm text-gray-600 mb-2">
                                        {{ \Carbon\Carbon::parse($entry['start_date'])->format('M Y') }} - 
                                        {{ $entry['end_date'] ? \Carbon\Carbon::parse($entry['end_date'])->format('M Y') : 'Present' }}
                                        @if($entry['total_hours'])
                                        • {{ number_format($entry['total_hours']) }} hours
                                        @endif
                                    </p>
                                    @if($entry['description'])
                                    <p class="text-sm text-gray-700 mb-2">{{ Str::limit($entry['description'], 150) }}</p>
                                    @endif
                                    @if($entry['skills_gained'])
                                    <div class="flex flex-wrap gap-1 mb-2">
                                        @foreach(array_slice(is_array($entry['skills_gained']) ? $entry['skills_gained'] : explode(',', $entry['skills_gained']), 0, 5) as $skill)
                                        <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs">{{ trim($skill) }}</span>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                <div class="ml-4 flex space-x-2">
                                    <a href="{{ route('profile.volunteering.edit', $entry['id']) }}" 
                                       class="text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <button onclick="viewDetails({{ $entry['id'] }})" 
                                            class="text-gray-600 hover:text-gray-800">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Experience Details Modal -->
<div id="experienceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-screen overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Experience Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function viewDetails(experienceId) {
    // Load experience details via AJAX
    fetch(`/profile/volunteering/${experienceId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalContent').innerHTML = `
                <div class="space-y-4">
                    <div>
                        <h4 class="font-semibold text-gray-900">${data.role_title}</h4>
                        <p class="text-blue-600">${data.organization}</p>
                        <p class="text-sm text-gray-600">${data.period} • ${data.total_hours} hours</p>
                    </div>
                    ${data.description ? `<div><h5 class="font-medium text-gray-900 mb-2">Description</h5><p class="text-gray-700">${data.description}</p></div>` : ''}
                    ${data.achievements ? `<div><h5 class="font-medium text-gray-900 mb-2">Achievements</h5><p class="text-gray-700">${data.achievements}</p></div>` : ''}
                    ${data.skills_gained ? `<div><h5 class="font-medium text-gray-900 mb-2">Skills Gained</h5><div class="flex flex-wrap gap-2">${data.skills_gained.map(skill => `<span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">${skill}</span>`).join('')}</div></div>` : ''}
                    ${data.references && data.references.length > 0 ? `<div><h5 class="font-medium text-gray-900 mb-2">References</h5>${data.references.map(ref => `<div class="bg-gray-50 p-3 rounded"><p class="font-medium">${ref.name}</p><p class="text-sm text-gray-600">${ref.title} • ${ref.relationship}</p></div>`).join('')}</div>` : ''}
                </div>
            `;
            document.getElementById('experienceModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error loading experience details:', error);
        });
}

function closeModal() {
    document.getElementById('experienceModal').classList.add('hidden');
}

function exportPortfolio() {
    window.location.href = '{{ route("profile.volunteering.export-portfolio") }}';
}

// Close modal when clicking outside
document.getElementById('experienceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
@endpush