@extends('layouts.client')

@section('title', 'Volunteer Notifications')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Notifications</h1>
                <p class="text-gray-600">Stay updated with your volunteer activities</p>
            </div>
            
            <div class="mt-4 md:mt-0 flex flex-wrap gap-4">
                <button onclick="markAllAsRead()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Mark All as Read
                </button>
                
                <a href="{{ route('client.volunteering.notifications.preferences') }}" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Preferences
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-bell text-blue-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-envelope text-orange-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Unread</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['unread'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-chart-line text-green-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">This Week</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['recent_activity'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-filter text-purple-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Filtered</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $notifications->total() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select name="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Types</option>
                    @foreach($types as $type)
                        <option value="{{ $type['value'] }}" {{ ($filters['type'] ?? '') == $type['value'] ? 'selected' : '' }}>
                            {{ $type['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
                <select name="channel" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Channels</option>
                    @foreach($channels as $channel)
                        <option value="{{ $channel['value'] }}" {{ ($filters['channel'] ?? '') == $channel['value'] ? 'selected' : '' }}>
                            {{ $channel['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="is_read" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="0" {{ isset($filters['is_read']) && $filters['is_read'] == '0' ? 'selected' : '' }}>Unread</option>
                    <option value="1" {{ isset($filters['is_read']) && $filters['is_read'] == '1' ? 'selected' : '' }}>Read</option>
                </select>
            </div>

            <div class="flex-1 min-w-48">
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select name="priority" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Priorities</option>
                    <option value="1" {{ ($filters['priority'] ?? '') == '1' ? 'selected' : '' }}>High</option>
                    <option value="2" {{ ($filters['priority'] ?? '') == '2' ? 'selected' : '' }}>Medium</option>
                    <option value="3" {{ ($filters['priority'] ?? '') == '3' ? 'selected' : '' }}>Low</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Filter
                </button>
                <a href="{{ route('client.volunteering.notifications.index') }}" class="ml-2 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    @if($notifications->count() > 0)
    <div class="bg-white rounded-lg shadow-md p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <label for="selectAll" class="text-sm font-medium text-gray-700">Select All</label>
                <span id="selectedCount" class="text-sm text-gray-500">0 selected</span>
            </div>
            
            <div id="bulkActions" class="hidden space-x-2">
                <button onclick="bulkAction('mark_read')" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                    Mark as Read
                </button>
                <button onclick="bulkAction('delete')" class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                    Delete
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Notifications List -->
    <div class="space-y-4">
        @forelse($notifications as $notification)
        <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 {{ !$notification->is_read ? 'border-l-4 border-blue-500' : '' }}">
            <div class="p-6">
                <div class="flex items-start space-x-4">
                    <input type="checkbox" class="notification-checkbox mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                           value="{{ $notification->id }}">
                    
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $notification->priority_color === 'red' ? 'bg-red-100' : ($notification->priority_color === 'yellow' ? 'bg-yellow-100' : 'bg-green-100') }}">
                            <i class="{{ $notification->icon }} {{ $notification->priority_color === 'red' ? 'text-red-600' : ($notification->priority_color === 'yellow' ? 'text-yellow-600' : 'text-green-600') }}"></i>
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 {{ !$notification->is_read ? 'font-bold' : '' }}">
                                {{ $notification->title }}
                            </h3>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $notification->priority_color === 'red' ? 'bg-red-100 text-red-800' : ($notification->priority_color === 'yellow' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                    {{ $notification->priority_display }}
                                </span>
                                <span class="text-sm text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 mt-1">{{ $notification->message }}</p>
                        
                        <div class="flex items-center justify-between mt-4">
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $notification->type_display }}
                                </span>
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $notification->channel_display }}
                                </span>
                                @if(!$notification->is_read)
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-orange-100 text-orange-800">
                                    Unread
                                </span>
                                @endif
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                @if($notification->url)
                                <a href="{{ $notification->url }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                   onclick="markAsRead({{ $notification->id }})">
                                    View Details
                                </a>
                                @endif
                                
                                @if(!$notification->is_read)
                                <button onclick="markAsRead({{ $notification->id }})" 
                                        class="text-green-600 hover:text-green-800 text-sm font-medium">
                                    Mark as Read
                                </button>
                                @endif
                                
                                <button onclick="deleteNotification({{ $notification->id }})" 
                                        class="text-red-600 hover:text-red-800 text-sm font-medium">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg shadow-md p-12 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-bell-slash text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications found</h3>
            <p class="text-gray-600">You're all caught up! Check back later for new notifications.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($notifications->hasPages())
    <div class="mt-8">
        {{ $notifications->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkActions();
});

// Individual checkbox functionality
document.querySelectorAll('.notification-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActions);
});

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.notification-checkbox');
    const checkedBoxes = document.querySelectorAll('.notification-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    selectedCount.textContent = `${checkedBoxes.length} selected`;
    
    if (checkedBoxes.length > 0) {
        bulkActions.classList.remove('hidden');
    } else {
        bulkActions.classList.add('hidden');
    }
    
    // Update select all checkbox
    const selectAll = document.getElementById('selectAll');
    selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
    selectAll.checked = checkedBoxes.length === checkboxes.length && checkboxes.length > 0;
}

function markAsRead(notificationId) {
    fetch(`/volunteering/notifications/${notificationId}/mark-read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) return;
    
    fetch('/volunteering/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteNotification(notificationId) {
    if (!confirm('Delete this notification?')) return;
    
    fetch(`/volunteering/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function bulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.notification-checkbox:checked');
    const notificationIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    if (notificationIds.length === 0) return;
    
    const actionText = action === 'mark_read' ? 'mark as read' : 'delete';
    if (!confirm(`${actionText} ${notificationIds.length} notification(s)?`)) return;
    
    fetch('/volunteering/notifications/bulk-action', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: action,
            notification_ids: notificationIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>
@endpush
@endsection