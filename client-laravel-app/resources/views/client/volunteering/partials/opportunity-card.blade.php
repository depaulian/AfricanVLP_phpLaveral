@php
    $cardClasses = 'bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-200 overflow-hidden';
    if (isset($featured) && $featured) {
        $cardClasses .= ' ring-2 ring-yellow-400';
    }
@endphp

<div class="{{ $cardClasses }}">
    @if(isset($featured) && $featured)
        <div class="bg-yellow-400 text-gray-900 text-xs font-semibold px-3 py-1">
            ⭐ FEATURED
        </div>
    @endif

    @if($opportunity->image_url)
        <div class="h-48 bg-gray-200 overflow-hidden">
            <img src="{{ $opportunity->image_url }}" alt="{{ $opportunity->title }}" class="w-full h-full object-cover">
        </div>
    @endif

    <div class="p-6">
        <!-- Header -->
        <div class="flex justify-between items-start mb-3">
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 mb-1 line-clamp-2">
                    <a href="{{ route('client.volunteering.show', $opportunity) }}" class="hover:text-blue-600 transition-colors">
                        {{ $opportunity->title }}
                    </a>
                </h3>
                <p class="text-sm text-gray-600">
                    <a href="#" class="hover:text-blue-600">{{ $opportunity->organization->name }}</a>
                </p>
            </div>
            
            @if(isset($showMatchScore) && $showMatchScore && isset($opportunity->match_score))
                <div class="ml-3 flex-shrink-0">
                    <div class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full">
                        {{ round($opportunity->match_score) }}% match
                    </div>
                </div>
            @endif
        </div>

        <!-- Category and Location -->
        <div class="flex flex-wrap gap-2 mb-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ $opportunity->category->name }}
            </span>
            
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                @if($opportunity->location_type === 'remote')
                    🌐 Remote
                @elseif($opportunity->location_type === 'hybrid')
                    🏢 Hybrid
                @else
                    📍 {{ $opportunity->city->name ?? 'On-site' }}
                @endif
            </span>

            @if($opportunity->experience_level !== 'any')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    {{ ucfirst($opportunity->experience_level) }}
                </span>
            @endif
        </div>

        <!-- Description -->
        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
            {{ Str::limit($opportunity->description, 150) }}
        </p>

        <!-- Skills Required -->
        @if($opportunity->required_skills && count($opportunity->required_skills) > 0)
            <div class="mb-4">
                <p class="text-xs text-gray-500 mb-1">Skills needed:</p>
                <div class="flex flex-wrap gap-1">
                    @foreach(array_slice($opportunity->required_skills, 0, 3) as $skill)
                        <span class="inline-block bg-gray-200 text-gray-700 text-xs px-2 py-1 rounded">
                            {{ $skill }}
                        </span>
                    @endforeach
                    @if(count($opportunity->required_skills) > 3)
                        <span class="text-xs text-gray-500">+{{ count($opportunity->required_skills) - 3 }} more</span>
                    @endif
                </div>
            </div>
        @endif

        <!-- Footer -->
        <div class="flex justify-between items-center pt-4 border-t border-gray-100">
            <div class="text-sm text-gray-500">
                @if($opportunity->application_deadline)
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Apply by {{ $opportunity->application_deadline->format('M j, Y') }}
                    </div>
                @endif
                
                @if($opportunity->volunteers_needed)
                    <div class="flex items-center mt-1">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        {{ $opportunity->spots_remaining }} spots left
                    </div>
                @endif
            </div>

            <div class="flex space-x-2">
                <a href="{{ route('client.volunteering.show', $opportunity) }}" 
                   class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    View Details
                </a>
                
                @auth
                    @can('apply', $opportunity)
                        <a href="{{ route('client.volunteering.apply', $opportunity) }}" 
                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Apply Now
                        </a>
                    @else
                        <span class="inline-flex items-center px-3 py-2 text-sm leading-4 font-medium rounded-md text-gray-500 bg-gray-100">
                            @if($opportunity->applications()->where('user_id', auth()->id())->exists())
                                Applied
                            @elseif(!$opportunity->is_accepting_applications)
                                Closed
                            @elseif($opportunity->spots_remaining <= 0)
                                Full
                            @else
                                Unavailable
                            @endif
                        </span>
                    @endcan
                @else
                    <a href="{{ route('login') }}" 
                       class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        Login to Apply
                    </a>
                @endauth
            </div>
        </div>
    </div>
</div>