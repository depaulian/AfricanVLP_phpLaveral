@extends('layouts.client')

@section('title', 'Security Events')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Security Events</h1>
                <p class="text-gray-600 mt-2">Monitor all security-related activities on your account</p>
            </div>
            <div class="flex space-x-4">
                <a 
                    href="{{ route('profile.security.dashboard') }}" 
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
                <button 
                    onclick="exportEvents()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                >
                    <i class="fas fa-download mr-2"></i>
                    Export Events
                </button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Filter Events</h2>
        
        <form method="GET" action="{{ route('profile.security.events') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                <select 
                    name="type" 
                    id="type" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Types</option>
                    @foreach($eventTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="risk_level" class="block text-sm font-medium text-gray-700 mb-1">Risk Level</label>
                <select 
                    name="risk_level" 
                    id="risk_level" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="">All Levels</option>
                    @foreach($riskLevels as $key => $label)
                        <option value="{{ $key }}" {{ request('risk_level') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input 
                    type="date" 
                    name="date_from" 
                    id="date_from" 
                    value="{{ request('date_from') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>
            
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input 
                    type="date" 
                    name="date_to" 
                    id="date_to" 
                    value="{{ request('date_to') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>
            
            <div class="md:col-span-4 flex justify-end space-x-3">
                <a 
                    href="{{ route('profile.security.events') }}" 
                    class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-md transition-colors"
                >
                    Clear Filters
                </a>
                <button 
                    type="submit"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors"
                >
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Events List -->
    <div class="bg-white rounded-xl shadow-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Security Events</h2>
                <div class="text-sm text-gray-600">
                    Showing {{ $events->firstItem() ?? 0 }} to {{ $events->lastItem() ?? 0 }} of {{ $events->total() }} events
                </div>
            </div>
        </div>

        @if($events->count() > 0)
            <div class="divide-y divide-gray-200">
                @foreach($events as $event)
                    <div class="p-6 hover:bg-gray-50 transition-colors">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start space-x-4">
                                <!-- Event Icon -->
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-{{ $event->risk_level === 'critical' ? 'red' : ($event->risk_level === 'high' ? 'orange' : ($event->risk_level === 'medium' ? 'yellow' : 'green')) }}-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-{{ $event->event_type === 'login_success' ? 'sign-in-alt' : ($event->event_type === 'login_failed' ? 'exclamation-triangle' : ($event->event_type === 'password_change' ? 'key' : ($event->event_type === 'suspicious_activity' ? 'shield-alt' : 'info-circle'))) }} text-{{ $event->risk_level === 'critical' ? 'red' : ($event->risk_level === 'high' ? 'orange' : ($event->risk_level === 'medium' ? 'yellow' : 'green')) }}-600 text-lg"></i>
                                    </div>
                                </div>

                                <!-- Event Details -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h3 class="text-lg font-medium text-gray-900">{{ $event->getTypeLabel() }}</h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $event->risk_level === 'critical' ? 'red' : ($event->risk_level === 'high' ? 'orange' : ($event->risk_level === 'medium' ? 'yellow' : 'green')) }}-100 text-{{ $event->risk_level === 'critical' ? 'red' : ($event->risk_level === 'high' ? 'orange' : ($event->risk_level === 'medium' ? 'yellow' : 'green')) }}-800">
                                            {{ $event->getRiskLevelLabel() }}
                                        </span>
                                        @if($event->is_resolved)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>
                                                Resolved
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <p class="text-gray-700 mb-3">{{ $event->event_description }}</p>
                                    
                                    <!-- Event Metadata -->
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                        <div class="flex items-center">
                                            <i class="fas fa-clock mr-2 text-gray-400"></i>
                                            <span>{{ $event->created_at->format('M j, Y \a\t g:i A') }}</span>
                                        </div>
                                        
                                        @if($event->ip_address)
                                            <div class="flex items-center">
                                                <i class="fas fa-globe mr-2 text-gray-400"></i>
                                                <span>{{ $event->ip_address }}</span>
                                            </div>
                                        @endif
                                        
                                        @if($event->location_data)
                                            <div class="flex items-center">
                                                <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                                                <span>
                                                    @php
                                                        $location = array_filter([
                                                            $event->location_data['city'] ?? null,
                                                            $event->location_data['region'] ?? null,
                                                            $event->location_data['country'] ?? null,
                                                        ]);
                                                    @endphp
                                                    {{ implode(', ', $location) ?: 'Unknown Location' }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Additional Data -->
                                    @if($event->additional_data && count($event->additional_data) > 0)
                                        <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                            <h4 class="text-sm font-medium text-gray-900 mb-2">Additional Information</h4>
                                            <div class="text-sm text-gray-600">
                                                @foreach($event->additional_data as $key => $value)
                                                    <div class="flex justify-between py-1">
                                                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                        <span>
                                                            @if(is_array($value))
                                                                {{ implode(', ', $value) }}
                                                            @else
                                                                {{ $value }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Resolution Info -->
                                    @if($event->is_resolved && $event->resolved_at)
                                        <div class="mt-3 p-3 bg-green-50 rounded-lg">
                                            <div class="flex items-center text-sm text-green-800">
                                                <i class="fas fa-check-circle mr-2"></i>
                                                <span>
                                                    Resolved on {{ $event->resolved_at->format('M j, Y \a\t g:i A') }}
                                                    @if($event->resolver)
                                                        by {{ $event->resolver->name }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex-shrink-0 ml-4">
                                @if(!$event->is_resolved && $event->isHighRisk())
                                    <button 
                                        onclick="resolveSecurityEvent({{ $event->id }})"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors"
                                    >
                                        Mark as Resolved
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="p-6 border-t border-gray-200">
                {{ $events->links() }}
            </div>
        @else
            <div class="p-12 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-shield-alt text-gray-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Security Events</h3>
                <p class="text-gray-600">
                    @if(request()->hasAny(['type', 'risk_level', 'date_from', 'date_to']))
                        No security events match your current filters.
                    @else
                        No security events have been recorded for your account.
                    @endif
                </p>
                @if(request()->hasAny(['type', 'risk_level', 'date_from', 'date_to']))
                    <div class="mt-4">
                        <a 
                            href="{{ route('profile.security.events') }}" 
                            class="text-blue-600 hover:text-blue-800 font-medium"
                        >
                            Clear filters to see all events
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<script>
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

    // Export events
    function exportEvents() {
        const params = new URLSearchParams(window.location.search);
        const exportUrl = '{{ route("profile.security.export") }}?' + params.toString();
        
        fetch(exportUrl)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `security-events-${new Date().toISOString().split('T')[0]}.json`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    showNotification('Security events exported successfully', 'success');
                } else {
                    showNotification('Failed to export security events', 'error');
                }
            })
            .catch(error => {
                console.error('Error exporting security events:', error);
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