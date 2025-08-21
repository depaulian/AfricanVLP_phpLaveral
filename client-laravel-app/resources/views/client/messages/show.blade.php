@extends('layouts.app')

@section('title', $conversation->subject)

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                {{ $conversation->subject }}
            </h2>
            <div class="mt-1 flex items-center space-x-2 text-sm text-gray-500">
                <span>Conversation with:</span>
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
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <form method="POST" action="{{ route('messages.archive', $conversation) }}" class="inline">
                @csrf
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l6 6 6-6"></path>
                    </svg>
                    Archive
                </button>
            </form>
            <a href="{{ route('messages.index') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Messages
            </a>
        </div>
    </div>

    <!-- Messages -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-6">
                @forelse($messages as $message)
                    <div class="flex {{ $message->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-xs lg:max-w-md">
                            <div class="flex items-start space-x-3">
                                @if($message->user_id !== auth()->id())
                                    <div class="flex-shrink-0">
                                        @if($message->user->profile_image)
                                            <img class="h-8 w-8 rounded-full" src="{{ $message->user->profile_image_url }}" alt="{{ $message->user->full_name }}">
                                        @else
                                            <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700">
                                                    {{ substr($message->user->first_name, 0, 1) }}{{ substr($message->user->last_name, 0, 1) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                
                                <div class="flex-1">
                                    <div class="bg-{{ $message->user_id === auth()->id() ? 'blue-600' : 'gray-100' }} rounded-lg px-4 py-2">
                                        <p class="text-sm {{ $message->user_id === auth()->id() ? 'text-white' : 'text-gray-900' }}">
                                            {{ $message->message }}
                                        </p>
                                    </div>
                                    <div class="mt-1 flex items-center space-x-2 text-xs text-gray-500">
                                        <span>{{ $message->user->full_name }}</span>
                                        <span>•</span>
                                        <span>{{ $message->created_at->format('M j, Y g:i A') }}</span>
                                        @if($message->read_at && $message->user_id === auth()->id())
                                            <span>•</span>
                                            <span class="text-green-600">Read</span>
                                        @endif
                                    </div>
                                </div>

                                @if($message->user_id === auth()->id())
                                    <div class="flex-shrink-0">
                                        @if(auth()->user()->profile_image)
                                            <img class="h-8 w-8 rounded-full" src="{{ auth()->user()->profile_image_url }}" alt="{{ auth()->user()->full_name }}">
                                        @else
                                            <div class="h-8 w-8 rounded-full bg-blue-600 flex items-center justify-center">
                                                <span class="text-sm font-medium text-white">
                                                    {{ substr(auth()->user()->first_name, 0, 1) }}{{ substr(auth()->user()->last_name, 0, 1) }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <p class="text-gray-500">No messages in this conversation yet.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($messages->hasPages())
                <div class="mt-6">
                    {{ $messages->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Reply Form -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form method="POST" action="{{ route('messages.reply', $conversation) }}" class="space-y-4">
                @csrf
                
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">
                        Reply
                    </label>
                    <textarea name="message" 
                              id="message" 
                              rows="4" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                              placeholder="Type your reply here..."
                              required>{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                        Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection