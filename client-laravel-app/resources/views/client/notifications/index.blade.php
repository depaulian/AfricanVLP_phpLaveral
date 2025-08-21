@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Notifications
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Stay updated with your activities and messages
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="inline">
                @csrf
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Mark All Read
                </button>
            </form>
            <form method="POST" action="{{ route('notifications.delete-read') }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        onclick="return confirm('Are you sure you want to delete all read notifications?')">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Read
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white shadow rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <a href="{{ route('notifications.index', ['filter' => 'all']) }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $filter === 'all' ? 'border-blue-500 text-blue-600' : '' }}">
                    All Notifications
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $filter === 'unread' ? 'border-blue-500 text-blue-600' : '' }}">
                    Unread
                    @if(auth()->user()->notifications()->unread()->count() > 0)
                        <span class="bg-red-100 text-red-800 ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                            {{ auth()->user()->notifications()->unread()->count() }}
                        </span>
                    @endif
                </a>
                <a href="{{ route('notifications.index', ['filter' => 'read']) }}" 
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $filter === 'read' ? 'border-blue-500 text-blue-600' : '' }}">
                    Read
                </a>
            </nav>
        </div>

        <!-- Notifications List -->
        <div class="px-4 py-5 sm:p-6">
            @if($notifications->count() > 0)
                <div class="space-y-4">
                    @foreach($notifications as $notification)
                        <div class="flex items-start space-x-4 p-4 rounded-lg border {{ $notification->isRead() ? 'bg-white border-gray-200' : 'bg-blue-50 border-blue-200' }}">
                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-{{ $notification->color }}-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-{{ $notification->color }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($notification->icon === 'mail')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        @elseif($notification->icon === 'chat-bubble-left-right')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        @elseif($notification->icon === 'hand-raised')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"></path>
                                        @elseif($notification->icon === 'calendar')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        @elseif($notification->icon === 'building-office')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.343 12.344l7.539 7.539a2.25 2.25 0 003.182 0l7.539-7.539a2.25 2.25 0 000-3.182L15.464 2.023a2.25 2.25 0 00-3.182 0L4.743 9.162a2.25 2.25 0 000 3.182z"></path>
                                        @endif
                                    </svg>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-medium text-gray-900">
                                            {{ $notification->title }}
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $notification->message }}
                                        </p>
                                        <p class="mt-2 text-xs text-gray-500">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        @if($notification->action_url)
                                            <a href="{{ route('notifications.mark-read', $notification) }}" 
                                               class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                View
                                            </a>
                                        @endif
                                        
                                        @if(!$notification->isRead())
                                            <form method="POST" action="{{ route('notifications.mark-read', $notification) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                                    Mark Read
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('notifications.destroy', $notification) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-900 text-sm font-medium"
                                                    onclick="return confirm('Are you sure you want to delete this notification?')">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Unread Indicator -->
                            @if(!$notification->isRead())
                                <div class="flex-shrink-0">
                                    <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($notifications->hasPages())
                    <div class="mt-6">
                        {{ $notifications->appends(['filter' => $filter])->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.343 12.344l7.539 7.539a2.25 2.25 0 003.182 0l7.539-7.539a2.25 2.25 0 000-3.182L15.464 2.023a2.25 2.25 0 00-3.182 0L4.743 9.162a2.25 2.25 0 000 3.182z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        @if($filter === 'unread')
                            No unread notifications
                        @elseif($filter === 'read')
                            No read notifications
                        @else
                            No notifications
                        @endif
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if($filter === 'unread')
                            You're all caught up! Check back later for new notifications.
                        @elseif($filter === 'read')
                            You haven't read any notifications yet.
                        @else
                            You'll receive notifications here when there's activity related to your account.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection