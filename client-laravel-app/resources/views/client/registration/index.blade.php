@extends('layouts.app')

@section('title', 'Complete Your Registration')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Complete Your Profile</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Help us personalize your volunteering experience by completing your profile. 
                It only takes a few minutes!
            </p>
        </div>

        <!-- Progress Overview -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">Your Progress</h2>
                    <p class="text-gray-600">{{ $progress['completed_steps'] }} of {{ $progress['total_steps'] }} steps completed</p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-blue-600">{{ $progress['overall_percentage'] }}%</div>
                    <div class="text-sm text-gray-500">Complete</div>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 rounded-full h-3 mb-6">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-3 rounded-full transition-all duration-500" 
                     style="width: {{ $progress['overall_percentage'] }}%"></div>
            </div>
            
            <!-- Step Indicators -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($progress['steps'] as $stepName => $step)
                    <div class="flex flex-col items-center p-4 rounded-lg border-2 transition-all
                        {{ $step['is_completed'] ? 'border-green-500 bg-green-50' : '' }}
                        {{ $stepName === $nextStep ? 'border-blue-500 bg-blue-50' : '' }}
                        {{ !$step['is_completed'] && $stepName !== $nextStep ? 'border-gray-200 bg-gray-50' : '' }}">
                        
                        <!-- Step Icon -->
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3
                            {{ $step['is_completed'] ? 'bg-green-500 text-white' : '' }}
                            {{ $stepName === $nextStep ? 'bg-blue-500 text-white' : '' }}
                            {{ !$step['is_completed'] && $stepName !== $nextStep ? 'bg-gray-300 text-gray-600' : '' }}">
                            
                            @if($step['is_completed'])
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            @else
                                {{ array_search($stepName, array_keys($progress['steps'])) + 1 }}
                            @endif
                        </div>
                        
                        <!-- Step Info -->
                        <h3 class="font-medium text-gray-900 text-center mb-1">{{ $step['title'] }}</h3>
                        <p class="text-xs text-gray-600 text-center">{{ $step['description'] }}</p>
                        
                        <!-- Completion Percentage -->
                        @if(!$step['is_completed'])
                            <div class="mt-2 text-xs text-gray-500">
                                {{ $step['completion_percentage'] }}% complete
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Next Step Action -->
        @if($nextStep)
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            Next: {{ $progress['steps'][$nextStep]['title'] }}
                        </h3>
                        <p class="text-gray-600 mb-4">{{ $progress['steps'][$nextStep]['description'] }}</p>
                        
                        @if($progress['estimated_completion_time'])
                            <div class="flex items-center text-sm text-gray-500">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Estimated time: {{ $progress['estimated_completion_time'] }} minutes
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex flex-col space-y-3">
                        <a href="{{ route('registration.step', $nextStep) }}" 
                           class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium text-center">
                            Continue Registration
                        </a>
                        
                        @if(in_array($nextStep, ['interests']))
                            <button onclick="skipStep('{{ $nextStep }}')" 
                                    class="text-gray-500 hover:text-gray-700 text-sm">
                                Skip for now
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Benefits Section -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h3 class="text-xl font-semibold text-gray-900 mb-6">Why Complete Your Profile?</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium text-gray-900 mb-2">Better Matching</h4>
                    <p class="text-sm text-gray-600">Get matched with opportunities that align with your interests and skills.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium text-gray-900 mb-2">Connect with Organizations</h4>
                    <p class="text-sm text-gray-600">Organizations can find and connect with you based on your profile.</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <h4 class="font-medium text-gray-900 mb-2">Track Your Impact</h4>
                    <p class="text-sm text-gray-600">Build a portfolio of your volunteering experience and achievements.</p>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-6 w-6 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-lg font-medium text-blue-800">Need Help?</h3>
                    <div class="mt-2 text-blue-700">
                        <p>If you have any questions or need assistance completing your profile, our support team is here to help.</p>
                        <div class="mt-4 flex space-x-4">
                            <a href="{{ route('support.contact') }}" class="text-blue-600 hover:text-blue-500 font-medium">
                                Contact Support →
                            </a>
                            <a href="{{ route('help.registration') }}" class="text-blue-600 hover:text-blue-500 font-medium">
                                Registration Guide →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function skipStep(stepName) {
    if (confirm('Are you sure you want to skip this step? You can always complete it later from your profile.')) {
        fetch(`/registration/skip/${stepName}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Failed to skip step. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to skip step. Please try again.');
        });
    }
}

// Auto-refresh progress every 30 seconds
setInterval(function() {
    fetch('/registration/progress')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.progress.overall_percentage === 100) {
                window.location.href = '{{ route("profile.show") }}';
            }
        })
        .catch(error => console.error('Error checking progress:', error));
}, 30000);
</script>
@endpush
@endsection