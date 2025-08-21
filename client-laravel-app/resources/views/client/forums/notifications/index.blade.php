@extends('layouts.client')

@section('title', 'Forum Notifications')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Forum Notifications</h1>
                <p class="text-gray-600 mt-2">Stay updated with forum activities</p>
            </div>
            <div class="flex space-x-4">
                <a href="{{ route('forums.notifications.preferences') }}" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-cog mr-2"></i>Preferences
                </a>
                <a href="{{ route('forums.notifications.subscriptions') }}" 
                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-bell mr-2"></i>Subscriptions
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">{{ $notifications->total() }}</div>
                    <div class="text-gray-600">Total Notifications</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-orange-600">{{ $unreadCount }}</div>
                    <div class="text-gray-600">Unread</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">{{ $notifications->total() - $unreadCount }}</div>
                    <div class="text-gray-600">Read</div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        @if($unreadCount > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex justify-between items-center">
                <div class="text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    You have {{ $unreadCount }} unread notifications
                </div>
                <button onclick="markAllAsRead()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                    Mark All as Read
                </button>
            </div>
        </div>
        @endif

        <!-- Notifications List -->
        <div class="space-y-4">
            @forelse($notifications as $notification)
            <div class="bg-white rounded-lg shadow-sm border {{ $notification->isUnread() ? 'border-l-4 border-l-blue-500' : '' }} 
                        hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4 flex-1">
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center {{ $notification->color_class }}">
                                    <i class="{{ $notification->icon }}"></i>
                                </div>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $notification->title }}</h3>
                                    @if($notification->isUnread())
                                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">New</span>
                                    @endif
                                </div>
                                
                                <p class="text-gray-600 mb-3">{{ $notification->message }}</p>
                                
                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                    <span>
                                        <i class="fas fa-clock mr-1"></i>
                                        {{ $notification->time_ago }}
                                    </span>
                                    @if($notification->data['forum_name'] ?? null)
                                    <span>
                                        <i class="fas fa-comments mr-1"></i>
                                        {{ $notification->data['forum_name'] }}
                                    </span>
                                    @endif
                                    @if($notification->data['author_name'] ?? null)
                                    <span>
                                        <i class="fas fa-user mr-1"></i>
                                        {{ $notification->data['author_name'] }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center space-x-2 ml-4">
                            @if($notification->isUnread())
                            <button onclick="markAsRead({{ $notification->id }})" 
                                    class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50 transition-colors"
                                    title="Mark as read">
                                <i class="fas fa-check"></i>
                            </button>
                            @endif
                            
                            <a href="{{ $notification->url }}" 
                               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                                View
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="bg-white rounded-lg shadow-sm border p-12 text-center">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-bell-slash text-6xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Notifications</h3>
                <p class="text-gray-500">You don't have any notifications yet.</p>
                <a href="{{ route('forums.index') }}" 
                   class="inline-block mt-4 bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Browse Forums
                </a>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($notifications->hasPages())
        <div class="mt-8">
            {{ $notifications->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function markAsRead(notificationId) {
    fetch(`/forums/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to mark notification as read');
    });
}

function markAllAsRead() {
    if (!confirm('Mark all notifications as read?')) {
        return;
    }
    
    fetch('/forums/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to mark all notifications as read');
    });
}
</script>
@endpush
@endsection