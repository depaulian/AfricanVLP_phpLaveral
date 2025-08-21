@extends('layouts.client')

@section('title', 'Security Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Security Dashboard</h1>
                <p class="text-gray-600 mt-2">Monitor and manage your account security</p>
            </div>
            <div class="flex space-x-4">
                <a 
                    href="{{ route('profile.security.events') }}" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                >
                    <i class="fas fa-history mr-2"></i>
                    Security Events
                </a>
                <a 
                    href="{{ route('profile.security.sessions') }}" 
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
                >
                    <i class="fas fa-desktop mr-2"></i>
                    Active Sessions
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Security Overview -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Security Score Card -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Security Score</h2>
                    <button 
                        onclick="refreshSecurityData()" 
                        class="text-gray-500 hover:text-gray-700 transition-colors"
                        title="Refresh Data"
                    >
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>

                <div class="text-center mb-6">
                    <div class="relative inline-flex items-center justify-center w-32 h-32 mb-4">
                        <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="40" stroke="#e5e7eb" stroke-width="8" fill="none"/>
                            <circle 
                                cx="50" cy="50" r="40" 
                                stroke="{{ $user->security_level === 'excellent' ? '#10b981' : ($user->security_level === 'good' ? '#3b82f6' : ($user->security_level === 'fair' ? '#f59e0b' : '#ef4444')) }}" 
                                stroke-width="8" 
                                fill="none"
                                stroke-dasharray="{{ 2 * pi() * 40 }}"
                                stroke-dashoffset="{{ 2 * pi() * 40 * (1 - $user->security_score / 100) }}"
                                class="transition-all duration-1000 ease-out"
                            />
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-gray-900">{{ $user->security_score }}</div>
                                <div class="text-sm text-gray-600">Score</div>
                            </div>
                        </div>
                    </div>
                    <div class="text-lg font-semibold capitalize text-{{ $user->security_level === 'excellent' ? 'green' : ($user->security_level === 'good' ? 'blue' : ($user->security_level === 'fair' ? 'yellow' : 'red')) }}-600">
                        {{ $user->security_level }} Security
                    </div>
                </div>

                <!-- Security Metrics -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ $activeSessions->count() }}</div>
                        <div class="text-sm text-gray-600">Active Sessions</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-2xl font-bold text-{{ $highRiskEvents > 0 ? 'red' : 'green' }}-600">{{ $highRiskEvents }}</div>
                        <div class="text-sm text-gray-600">High Risk Events</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-2xl font-bold text-{{ $user->hasTwoFactorEnabled() ? 'green' : 'red' }}-600">
                            <i class="fas fa-{{ $user->hasTwoFactorEnabled() ? 'check' : 'times' }}"></i>
                        </div>
                        <div class="text-sm text-gray-600">Two-Factor Auth</div>
                    </div>
                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                        <div class="text-2xl font-bold text-{{ $user->hasVerifiedEmail() ? 'green' : 'red' }}-600">
                            <i class="fas fa-{{ $user->hasVerifiedEmail() ? 'check' : 'times' }}"></i>
                        </div>
                        <div class="text-sm text-gray-600">Email Verified</div>
                    </div>
                </div>
            </div>

            <!-- Recent Security Events -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Security Events</h2>
                    <a 
                        href="{{ route('profile.security.events') }}" 
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                        View All →
                    </a>
                </div>

                @if($recentEvents->count() > 0)
                    <div class="space-y-4">
                        @foreach($recentEvents->take(5) as $event)
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg border-l-4 border-{{ $event->risk_level === 'critical' ? 'red' : ($event->risk_level === 'high' ? 'orange' : ($event->risk_level === 'medium' ? 'yellow' : 'green')) }}-500">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-10 h-10 bg-{{ $event->risk_level === 'critical' ? 'red' : ($event->risk_level === 'high' ? 'orange' : ($event->risk_level === 'medium' ? 'yellow' : 'green')) }}-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-{{ $event->event_type === 'login_success' ? 'sign-in-alt' : ($event->event_type === 'login_failed' ? 'exclamation-triangle' : ($event->event_type === 'password_change' ? 'key' : 'shield-alt')) }} text-{{ $event->risk_level === 'critical' ? 'red' : ($event->risk_level === 'high' ? 'orange' : ($event->risk_level === 'medium' ? 'yellow' : 'green')) }}-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ $event->getTypeLabel() }}</h3>
                                            <p class="text-sm text-gray-600 mt-1">{{ $event->event_description }}</p>
                                            <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                <span>
                                                    <i class="fas fa-clock mr-1"></i>
                                                    {{ $event->created_at->diffForHumans() }}
                                                </span>
                                                @if($event->ip_address)
                                                    <span>
                                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                                        {{ $event->ip_address }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        @if(!$event->is_resolved && $event->isHighRisk())
                                            <div class="mt-3">
                                                <button 
                                                    onclick="resolveSecurityEvent({{ $event->id }})"
                                                    class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded transition-colors"
                                                >
                                                    Mark as Resolved
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-shield-alt text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">All Clear!</h3>
                        <p class="text-gray-600">No recent security events to display.</p>
                    </div>
                @endif
            </div>

            <!-- Active Sessions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Active Sessions</h2>
                    <a 
                        href="{{ route('profile.security.sessions') }}" 
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    >
                        Manage All →
                    </a>
                </div>

                @if($activeSessions->count() > 0)
                    <div class="space-y-4">
                        @foreach($activeSessions->take(3) as $session)
                            <div class="flex items-start p-4 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0 mr-4">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-{{ $session->device_type === 'mobile' ? 'mobile-alt' : ($session->device_type === 'tablet' ? 'tablet-alt' : 'desktop') }} text-blue-600"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="font-medium text-gray-900">{{ $session->getDeviceDescription() }}</h3>
                                            <p class="text-sm text-gray-600 mt-1">{{ $session->getLocationString() }}</p>
                                            <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                <span>
                                                    <i class="fas fa-clock mr-1"></i>
                                                    {{ $session->last_activity ? $session->last_activity->diffForHumans() : 'Unknown' }}
                                                </span>
                                                @if($session->ip_address)
                                                    <span>
                                                        <i class="fas fa-globe mr-1"></i>
                                                        {{ $session->ip_address }}
                                                    </span>
                                                @endif
                                                @if($session->is_current)
                                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                                        Current Session
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        @if(!$session->is_current)
                                            <button 
                                                onclick="terminateSession('{{ $session->session_id }}')"
                                                class="text-red-600 hover:text-red-800 text-sm"
                                                title="Terminate Session"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($activeSessions->count() > 1)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <button 
                                onclick="terminateAllOtherSessions()"
                                class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
                            >
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                Terminate All Other Sessions
                            </button>
                        </div>
                    @endif
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-desktop text-blue-600 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Single Session</h3>
                        <p class="text-gray-600">You only have one active session.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Security Recommendations & Quick Actions -->
        <div class="space-y-6">
            <!-- Security Recommendations -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Security Recommendations</h2>

                @if(count($securityRecommendations) > 0)
                    <div class="space-y-4">
                        @foreach($securityRecommendations as $recommendation)
                            <div class="flex items-start p-4 border border-{{ $recommendation['priority'] === 'high' ? 'red' : ($recommendation['priority'] === 'medium' ? 'yellow' : 'blue') }}-200 bg-{{ $recommendation['priority'] === 'high' ? 'red' : ($recommendation['priority'] === 'medium' ? 'yellow' : 'blue') }}-50 rounded-lg">
                                <div class="flex-shrink-0 mr-3">
                                    <div class="w-8 h-8 bg-{{ $recommendation['priority'] === 'high' ? 'red' : ($recommendation['priority'] === 'medium' ? 'yellow' : 'blue') }}-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-{{ $recommendation['priority'] === 'high' ? 'exclamation-triangle' : ($recommendation['priority'] === 'medium' ? 'info-circle' : 'lightbulb') }} text-{{ $recommendation['priority'] === 'high' ? 'red' : ($recommendation['priority'] === 'medium' ? 'yellow' : 'blue') }}-600 text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-medium text-gray-900">{{ $recommendation['title'] }}</h3>
                                    <p class="text-sm text-gray-600 mt-1">{{ $recommendation['description'] }}</p>
                                    <div class="mt-3">
                                        <button 
                                            onclick="handleRecommendationAction('{{ $recommendation['type'] }}')"
                                            class="text-sm bg-{{ $recommendation['priority'] === 'high' ? 'red' : ($recommendation['priority'] === 'medium' ? 'yellow' : 'blue') }}-600 hover:bg-{{ $recommendation['priority'] === 'high' ? 'red' : ($recommendation['priority'] === 'medium' ? 'yellow' : 'blue') }}-700 text-white px-3 py-1 rounded transition-colors"
                                        >
                                            {{ $recommendation['action'] }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Great Security!</h3>
                        <p class="text-gray-600">No security recommendations at this time.</p>
                    </div>
                @endif
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Quick Actions</h2>
                
                <div class="space-y-3">
                    <button 
                        onclick="showChangePasswordModal()"
                        class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <div class="flex items-center">
                            <i class="fas fa-key text-blue-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Change Password</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>

                    <a 
                        href="{{ route('profile.privacy') }}"
                        class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <div class="flex items-center">
                            <i class="fas fa-user-shield text-green-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Privacy Settings</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </a>

                    <button 
                        onclick="exportSecurityData()"
                        class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <div class="flex items-center">
                            <i class="fas fa-download text-purple-600 mr-3"></i>
                            <span class="font-medium text-gray-900">Export Security Data</span>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>

                    @if(!$user->hasTwoFactorEnabled())
                        <button 
                            onclick="enableTwoFactor()"
                            class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <div class="flex items-center">
                                <i class="fas fa-mobile-alt text-orange-600 mr-3"></i>
                                <span class="font-medium text-gray-900">Enable 2FA</span>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Security Tips -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Security Tips</h2>
                <div class="space-y-3 text-sm text-gray-700">
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                        <span>Use a unique, strong password for your account</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                        <span>Enable two-factor authentication for extra security</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                        <span>Regularly review your active sessions</span>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2 mt-0.5"></i>
                        <span>Be cautious when accessing your account on public networks</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
                        <button onclick="hideChangePasswordModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form id="changePasswordForm" onsubmit="changePassword(event)">
                        <div class="space-y-4">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    Current Password
                                </label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                                    New Password
                                </label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    required
                                    oninput="checkPasswordStrength(this.value)"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                <div id="passwordStrength" class="mt-2 hidden">
                                    <div class="flex items-center space-x-2">
                                        <div class="flex-1 bg-gray-200 rounded-full h-2">
                                            <div id="strengthBar" class="h-2 rounded-full transition-all duration-300"></div>
                                        </div>
                                        <span id="strengthText" class="text-sm font-medium"></span>
                                    </div>
                                    <div id="strengthFeedback" class="mt-1 text-xs text-gray-600"></div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirm New Password
                                </label>
                                <input 
                                    type="password" 
                                    id="new_password_confirmation" 
                                    name="new_password_confirmation" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button 
                                type="button" 
                                onclick="hideChangePasswordModal()"
                                class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors"
                            >
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Refresh security data
    function refreshSecurityData() {
        fetch('{{ route("profile.security.dashboard.data") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update security score and other dynamic elements
                    location.reload(); // Simple refresh for now
                }
            })
            .catch(error => {
                console.error('Error refreshing security data:', error);
            });
    }

    // Resolve security event
    function resolveSecurityEvent(eventId) {
        if (!confirm('Are you sure you want to mark this security event as resolved?')) {
            return;
        }

        fetch(`/profile/security/events/${eventId}/resolve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Security event marked as resolved', 'success');
                location.reload();
            } else {
                showNotification(data.message || 'Failed to resolve security event', 'error');
            }
        })
        .catch(error => {
            console.error('Error resolving security event:', error);
            showNotification('An error occurred', 'error');
        });
    }

    // Terminate session
    function terminateSession(sessionId) {
        if (!confirm('Are you sure you want to terminate this session?')) {
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

    // Handle recommendation actions
    function handleRecommendationAction(type) {
        switch (type) {
            case 'two_factor':
                enableTwoFactor();
                break;
            case 'password_age':
                showChangePasswordModal();
                break;
            case 'multiple_sessions':
                window.location.href = '{{ route("profile.security.sessions") }}';
                break;
            case 'security_alerts':
                window.location.href = '{{ route("profile.security.events") }}';
                break;
            default:
                console.log('Unknown recommendation type:', type);
        }
    }

    // Show change password modal
    function showChangePasswordModal() {
        document.getElementById('changePasswordModal').classList.remove('hidden');
    }

    // Hide change password modal
    function hideChangePasswordModal() {
        document.getElementById('changePasswordModal').classList.add('hidden');
        document.getElementById('changePasswordForm').reset();
        document.getElementById('passwordStrength').classList.add('hidden');
    }

    // Change password
    function changePassword(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData);

        fetch('{{ route("profile.security.password.change") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Password changed successfully', 'success');
                hideChangePasswordModal();
            } else {
                showNotification(data.message || 'Failed to change password', 'error');
                if (data.feedback) {
                    console.log('Password feedback:', data.feedback);
                }
            }
        })
        .catch(error => {
            console.error('Error changing password:', error);
            showNotification('An error occurred', 'error');
        });
    }

    // Check password strength
    function checkPasswordStrength(password) {
        if (!password) {
            document.getElementById('passwordStrength').classList.add('hidden');
            return;
        }

        fetch('{{ route("profile.security.password.check-strength") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ password: password })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePasswordStrengthDisplay(data.strength);
            }
        })
        .catch(error => {
            console.error('Error checking password strength:', error);
        });
    }

    // Update password strength display
    function updatePasswordStrengthDisplay(strength) {
        const strengthElement = document.getElementById('passwordStrength');
        const barElement = document.getElementById('strengthBar');
        const textElement = document.getElementById('strengthText');
        const feedbackElement = document.getElementById('strengthFeedback');

        strengthElement.classList.remove('hidden');

        const colors = {
            weak: { bg: 'bg-red-500', text: 'text-red-600', width: '33%' },
            medium: { bg: 'bg-yellow-500', text: 'text-yellow-600', width: '66%' },
            strong: { bg: 'bg-green-500', text: 'text-green-600', width: '100%' }
        };

        const config = colors[strength.strength] || colors.weak;
        
        barElement.className = `h-2 rounded-full transition-all duration-300 ${config.bg}`;
        barElement.style.width = config.width;
        
        textElement.textContent = strength.strength.charAt(0).toUpperCase() + strength.strength.slice(1);
        textElement.className = `text-sm font-medium ${config.text}`;
        
        if (strength.feedback && strength.feedback.length > 0) {
            feedbackElement.textContent = strength.feedback.join(', ');
        } else {
            feedbackElement.textContent = '';
        }
    }

    // Export security data
    function exportSecurityData() {
        fetch('{{ route("profile.security.export") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `security-data-${new Date().toISOString().split('T')[0]}.json`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    showNotification('Security data exported successfully', 'success');
                } else {
                    showNotification('Failed to export security data', 'error');
                }
            })
            .catch(error => {
                console.error('Error exporting security data:', error);
                showNotification('An error occurred', 'error');
            });
    }

    // Enable two-factor authentication
    function enableTwoFactor() {
        // This would typically redirect to a 2FA setup page
        showNotification('Two-factor authentication setup coming soon', 'info');
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