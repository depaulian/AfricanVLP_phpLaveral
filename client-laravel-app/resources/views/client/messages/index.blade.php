@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                Messages
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Communicate with other members and organizations
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <a href="{{ route('messages.create') }}" 
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Message
            </a>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="GET" action="{{ route('messages.search') }}" class="flex space-x-4">
                <div class="flex-1">
                    <input type="text" 
                           name="q" 
                           value="{{ request('q') }}"
                           placeholder="Search conversations and messages..."
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Search
                </button>
            </form>
        </div>
    </div>

    <!-- Conversations List -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            @if($conversations->count() > 0)
                <div class="space-y-4">
                    @foreach($conversations as $conversation)
                        <div class="border rounded-lg p-4 hover:bg-gray-50 transition-colors duration-200">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <a href="{{ route('messages.show', $conversation) }}" class="hover:text-blue-600">
                                                {{ $conversation->subject }}
                                            </a>
                                        </h3>
                                        @if($conversation->hasUnreadMessages(auth()->id()))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                New
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- Participants -->
                                    <div class="mt-2 flex items-center space-x-2 text-sm text-gray-500">
                                        <span>With:</span>
                                        @foreach($conversation->participants as $participant)
                                            @if($participant->user_id !== auth()->id())
                                                @if($participant->user)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                        </svg>
                                                        {{ $participant->user->full_name }}
                                                    </span>
                                                @elseif($participant->organization)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                        </svg>
                                                        {{ $participant->organization->name }}
                                                    </span>
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>

                                    <!-- Last Message -->
                                    @if($conversation->lastMessage)
                                        <div class="mt-3">
                                            <p class="text-sm text-gray-600">
                                                <span class="font-medium">{{ $conversation->lastMessage->user->full_name }}:</span>
                                                {{ Str::limit($conversation->lastMessage->message, 100) }}
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col items-end space-y-2">
                                    <span class="text-xs text-gray-500">
                                        {{ $conversation->updated_at->diffForHumans() }}
                                    </span>
                                    
                                    <!-- Actions -->
                                    <div class="flex space-x-2">
                                        <a href="{{ route('messages.show', $conversation) }}" 
                                           class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                            View
                                        </a>
                                        <form method="POST" action="{{ route('messages.archive', $conversation) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-gray-600 hover:text-gray-900 text-sm font-medium">
                                                Archive
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($conversations->hasPages())
                    <div class="mt-6">
                        {{ $conversations->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No conversations</h3>
                    <p class="mt-1 text-sm text-gray-500">Start a conversation with other members or organizations.</p>
                    <div class="mt-6">
                        <a href="{{ route('messages.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Start New Conversation
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection