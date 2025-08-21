@extends('layouts.client')

@section('title', 'My Volunteer Portfolio')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">My Volunteer Portfolio</h1>
                <p class="text-gray-600">A comprehensive overview of your volunteer journey and achievements</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="{{ route('client.volunteering.portfolio.export') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>
                    Export Portfolio
                </a>
            </div>
        </div>
    </div>

    <!-- Profile Summary -->
    <div class="bg-white rounded-lg shadow-sm border p-8 mb-8">
        <div class="flex flex-col md:flex-row items-start md:items-center">
            <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                @if($user->profile_photo)
                    <img src="{{ $user->profile_photo }}" 
                         alt="{{ $user->name }}" 
                         class="w-24 h-24 rounded-full object-cover">
                @else
                    <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-blue-600 text-3xl"></i>
                    </div>
                @endif
            </div>
            
            <div class="flex-grow">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $user->name }}</h2>
                <p class="text-lg text-blue-600 font-medium mb-2">{{ $user->volunteer_rank }}</p>
                
                @if($user->bio)
                    <p class="text-gray-600 mb-4">{{ $user->bio }}</p>
                @endif
                
                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-clock mr-2"></i>
                        <span>{{ number_format($totalHours, 1) }} volunteer hours</span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-trophy mr-2"></i>
                        <span>{{ $achievementStats['total_earned'] }} achievements</span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-certificate mr-2"></i>
                        <span>{{ $certificateStats['total_certificates'] }} certificates</span>
                    </div>
                    <div class="flex items-center text-gray-600">
                        <i class="fas fa-building mr-2"></i>
                        <span>{{ $organizationsServed->count() }} organizations served</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-clock text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Hours</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalHours, 1) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-trophy text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Achievements</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $achievementStats['total_earned'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-certificate text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Certificates</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $certificateStats['total_certificates'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-star text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Points Earned</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($achievementStats['total_points']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Recent Achievements -->
            @if($user->achievements->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Achievements</h3>
                        <a href="{{ route('client.volunteering.achievements') }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All
                        </a>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($user->achievements->take(4) as $userAchievement)
                            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                @if($userAchievement->achievement->icon)
                                    <i class="{{ $userAchievement->achievement->icon }} text-2xl text-yellow-500 mr-3"></i>
                                @else
                                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-trophy text-yellow-600"></i>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-900">{{ $userAchievement->achievement->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $userAchievement->earned_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Recent Certificates -->
            @if($user->certificates->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Certificates</h3>
                        <a href="{{ route('client.volunteering.certificates') }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        @foreach($user->certificates->take(3) as $certificate)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-certificate text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $certificate->title }}</p>
                                        <p class="text-sm text-gray-500">{{ $certificate->organization->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $certificate->issued_at->format('M j, Y') }}</p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="{{ route('client.certificates.show', $certificate) }}" 
                                       class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('client.certificates.download', $certificate) }}" 
                                       class="text-green-600 hover:text-green-800">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Volunteer History -->
            @if($user->volunteerApplications->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Volunteer History</h3>
                    
                    <div class="space-y-4">
                        @foreach($user->volunteerApplications->take(5) as $application)
                            <div class="flex items-start justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-start">
                                    @if($application->opportunity->organization->logo)
                                        <img src="{{ $application->opportunity->organization->logo }}" 
                                             alt="{{ $application->opportunity->organization->name }}" 
                                             class="w-10 h-10 rounded-lg object-cover mr-3">
                                    @else
                                        <div class="w-10 h-10 bg-gray-200 rounded-lg flex items-center justify-center mr-3">
                                            <i class="fas fa-building text-gray-500"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $application->opportunity->title }}</p>
                                        <p class="text-sm text-gray-600">{{ $application->opportunity->organization->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $application->created_at->format('M j, Y') }}</p>
                                        
                                        @if($application->assignments->count() > 0)
                                            <div class="mt-2">
                                                @php
                                                    $completedAssignments = $application->assignments->where('status', 'completed')->count();
                                                    $totalHours = $application->assignments->sum('hours_completed');
                                                @endphp
                                                <div class="flex space-x-4 text-xs text-gray-500">
                                                    <span>{{ $completedAssignments }} assignments completed</span>
                                                    @if($totalHours > 0)
                                                        <span>{{ number_format($totalHours, 1) }} hours</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $application->status === 'accepted' ? 'bg-green-100 text-green-800' : 
                                       ($application->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ ucfirst($application->status) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Skills -->
            @if($user->skills->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Skills</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($user->skills as $skill)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $skill->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Organizations Served -->
            @if($organizationsServed->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Organizations Served</h3>
                    <div class="space-y-3">
                        @foreach($organizationsServed as $organization)
                            <div class="flex items-center">
                                @if($organization->logo)
                                    <img src="{{ $organization->logo }}" 
                                         alt="{{ $organization->name }}" 
                                         class="w-8 h-8 rounded object-cover mr-3">
                                @else
                                    <div class="w-8 h-8 bg-gray-200 rounded flex items-center justify-center mr-3">
                                        <i class="fas fa-building text-gray-500 text-xs"></i>
                                    </div>
                                @endif
                                <span class="text-sm text-gray-900">{{ $organization->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Achievement Progress -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Achievement Progress</h3>
                
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Completion Rate</span>
                            <span>{{ $achievementStats['completion_percentage'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $achievementStats['completion_percentage'] }}%"></div>
                        </div>
                    </div>
                    
                    <div class="text-sm text-gray-600">
                        <p>{{ $achievementStats['total_earned'] }} of {{ $achievementStats['total_available'] }} achievements earned</p>
                    </div>
                    
                    @if($achievementStats['recent_achievements'] > 0)
                        <div class="text-sm text-green-600">
                            <p>{{ $achievementStats['recent_achievements'] }} new achievements this month!</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                
                <div class="space-y-3">
                    <a href="{{ route('client.volunteering.index') }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>
                        Find Opportunities
                    </a>
                    
                    <a href="{{ route('client.volunteering.achievements') }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-trophy mr-2"></i>
                        View Achievements
                    </a>
                    
                    <a href="{{ route('client.volunteering.certificates') }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-certificate mr-2"></i>
                        View Certificates
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection