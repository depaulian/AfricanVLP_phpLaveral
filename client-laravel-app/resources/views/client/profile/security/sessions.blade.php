@extends('layouts.client')

@section('title', 'Active Sessions')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Active Sessions</h1>
                <p class="text-gray-600 mt-2">Manage your active sessions and review recent login activity</p>
            </div>
            <div class="flex space-x-4">
                <a 
                    href="{{ route('profile.security.dashboard') }}" 
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
                @if($activeSessions->count() > 1)
                    <button 
                        onclick="terminateAllOtherSessions()"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
                    >
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Terminate All Others
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Active Sessions -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">Active Sessions</h2>
                        <div class="text-sm text-gray-600">
                            {{ $activeSessions->count() }} active session{{ $activeSessions->count() !== 1 ? 's' : '' }}
                        </div>
                    </div>
                </div>

                @if($activeSessions->count() > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach($activeSessions as $session)
                            <div class="p-6 {{ $session->is_current_session ? 'bg-blue-50' : 'hover:bg-gray-50' }} transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-4">
                                        <!-- Device Icon -->
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 bg-{{ $session->is_current_session ? 'blue' : 'gray' }}-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-{{ $session->device_type === 'mobile' ? 'mobile-alt' : ($session->device_type === 'tablet' ? 'tablet-alt' : 'desktop') }} text-{{ $session->is_current_session ? 'blue' : 'gray' }}-600 text-lg"></i>
                                            </div>
                                        </div>

                                        <!-- Session Details -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-3 mb-2">
                                                <h3 class="text-lg font-medium text-gray-900">{{ $session->getDeviceDescription() }}</h3>
                                                @if($session->is_current_session)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <i class="fas fa-circle mr-1 text-xs"></i>
                                                        Current Session
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <div class="space-y-2 text-sm text-gray-600">
                                                <div class="flex items-center">
                                                    <i class="fas fa-map-marker-alt mr-2 text-gray-400 w-4"></i>
                                                    <span>{{ $session->getLocationString() }}</span>
                                                </div>
                                                
                                                <div class="flex items-center">
                                                    <i class="fas fa-globe mr-2 text-gray-400 w-4"></i>
                                                    <span>{{ $session->ip_address ?: 'Unknown IP' }}</span>
                                                </div>
                                                
                                                <div class="flex items-center">
                                                    <i class="fas fa-clock mr-2 text-gray-400 w-4"></i>
                                                    <span>
                                                        Last active {{ $session->last_activity ? $session->last_activity->diffForHumans() : 'Unknown' }}
                                                    </span>
                                                </div>
                                                
                                                <div class="flex items-center">
                                                    <i class="fas fa-calendar mr-2 text-gray-400 w-4"></i>
                                                    <span>Started {{ $session->created_at->diffForHumans() }}</span>
                                                </div>
                                                
                                                @if($session->getDurationInMinutes() > 0)
                                                    <div class="flex items-center">
                                                        <i class="fas fa-hourglass-half mr-2 text-gray-400 w-4"></i>
                                                        <span>
                                                            Duration: 
                                                            @if($session->getDurationInMinutes() < 60)
                                                                {{ $session->getDurationInMinutes() }} minutes
                                                            @else
                                                                {{ round($session->getDurationInMinutes() / 60, 1) }} hours
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Security Indicators -->
                                            <div class="mt-3 flex items-center space-x-4">
                                                @if($session->isSuspiciousLocation())
                                                    <div class="flex items-center text-xs text-orange-600 bg-orange-100 px-2 py-1 rounded-full">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                        New Location
                                                    </div>
                                                @endif
                                                
                                                @if($session->isActive())
                                                    <div class="flex items-center text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        Active
                                                    </div>
                                                @else
                                                    <div class="flex items-center text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        Expired
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex-shrink-0 ml-4">
                                        @if(!$session->is_current_session)
                                            <button 
                                                onclick="terminateSession('{{ $session->session_id }}')"
                                                class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm transition-colors"
                                                title="Terminate Session"
                                            >
                                                <i class="fas fa-times mr-1"></i>
                                                Terminate
                                            </button>
                                        @else
                                            <div class="text-sm text-gray-500 italic">
                                                Current session
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-desktop text-gray-400 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Active Sessions</h3>
                        <p class="text-gray-600">There are no active sessions for your account.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Session Statistics -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Session Statistics</h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Active Sessions</span>
                        <span class="text-lg font-semibold text-blue-600">{{ $activeSessions->count() }}</span>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Recent Sessions (30 days)</span>
                        <span class="text-lg font-semibold text-gray-900">{{ $recentSessions->count() + $activeSessions->count() }}</span>
                    </div>
                    
                    @php
                        $uniqueLocations = $activeSessions->pluck('location_data')
                            ->filter()
                            ->map(fn($data) => $data['country'] ?? 'Unknown')
                            ->unique()
                            ->count();
                    @endphp
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Unique Locations</span>
                        <span class="text-lg font-semibold text-gray-900">{{ $uniqueLocations }}</span>
                    </div>
                    
                    @php
                        $deviceTypes = $activeSessions->pluck('device_type')->unique()->count();
                    @endphp
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Device Types</span>
                        <span class="text-lg font-semibold text-gray-900">{{ $deviceTypes }}</span>
                    </div>
                </div>
            </div>

            <!-- Security Tips -->
            <div class="bg-gradient-to-br from-yellow-50 to-orange-100 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Session Security Tips</h3>
                
                <div class="space-y-3 text-sm text-gray-700">
                    <div class="flex items-start">
                        <i class="fas fa-shield-alt text-yellow-600 mr-2 mt-0.5 flex-shrink-0"></i>
                        <span>Regularly review and terminate unused sessions</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-orange-600 mr-2 mt-0.5 flex-shrink-0"></i>
                        <span>Be cautious of sessions from unfamiliar locations</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-wifi text-red-600 mr-2 mt-0.5 flex-shrink-0"></i>
                        <span>Always log out when using public computers</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-mobile-alt text-blue-600 mr-2 mt-0.5 flex-shrink-0"></i>
                        <span>Enable automatic logout on mobile devices</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                
                <div class="space-y-3">
                    @if($activeSessions->count() > 1)
                        <button 
                            onclick="terminateAllOtherSessions()"
                            class="w-full flex items-center justify-center p-3 bg-red-50 hover:bg-red-100 text-red-700 rounded-lg transition-colors"
                        >
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            <span>Terminate All Other Sessions</span>
                        </button>
                    @endif
                    
                    <a 
                        href="{{ route('profile.security.events') }}"
                        class="w-full flex items-center justify-center p-3 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg transition-colors"
                    >
                        <i class="fas fa-history mr-2"></i>
                        <span>View Security Events</span>
                    </a>
                    
                    <a 
                        href="{{ route('profile.privacy') }}"
                        class="w-full flex items-center justify-center p-3 bg-green-50 hover:bg-green-100 text-green-700 rounded-lg transition-colors"
                    >
                        <i class="fas fa-user-shield mr-2"></i>
                        <span>Privacy Settings</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sessions -->
    @if($recentSessions->count() > 0)
        <div class="mt-8">
            <div class="bg-white rounded-xl shadow-lg">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Sessions</h2>
                    <p class="text-sm text-gray-600 mt-1">Sessions that have ended in the last 30 days</p>
                </div>

                <div class="divide-y divide-gray-200">
                    @foreach($recentSessions as $session)
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start space-x-4">
                                <!-- Device Icon -->
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-{{ $session->device_type === 'mobile' ? 'mobile-alt' : ($session->device_type === 'tablet' ? 'tablet-alt' : 'desktop') }} text-gray-500"></i>
                                    </div>
                                </div>

                                <!-- Session Details -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ $session->getDeviceDescription() }}</h3>
                                            <div class="mt-1 space-y-1 text-sm text-gray-600">
                                                <div>{{ $session->getLocationString() }} • {{ $session->ip_address ?: 'Unknown IP' }}</div>
                                                <div>
                                                    {{ $session->created_at->format('M j, Y \a\t g:i A') }} - 
                                                    {{ $session->expires_at ? $session->expires_at->format('M j, Y \a\t g:i A') : 'Unknown end time' }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <span class="bg-gray-100 px-2 py-1 rounded-full">Ended</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Terminate specific session
    function terminateSession(sessionId) {
        if (!confirm('Are you sure you want to terminate this session? This will log out the device immediately.')) {
            return;
        }

        fetch('{{ route("profile.security.sessions.terminate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ session_id: sessionId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Session terminated successfully', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'Failed to terminate session', 'error');
            }
        })
        .catch(error => {
            console.error('Error terminating session:', error);
            showNotification('An error occurred', 'error');
        });
    }

    // Terminate all other sessions
    function terminateAllOtherSessions() {
        if (!confirm('Are you sure you want to terminate all other sessions? This will log you out from all other devices.')) {
            return;
        }

        fetch('{{ route("profile.security.sessions.terminate-all") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Terminated ${data.terminated_count} other sessions`, 'success');
                location.reload();
            } else {
                showNotification(data.message || 'Failed to terminate sessions', 'error');
            }
        })
        .catch(error => {
            console.error('Error terminating sessions:', error);
            showNotification('An error occurred', 'error');
        });
    }

    // Show notification
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
</script>
@endsection