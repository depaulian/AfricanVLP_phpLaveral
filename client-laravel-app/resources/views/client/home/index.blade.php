@extends('layouts.app')

@section('title', 'Welcome to AU-VLP')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .font-inter { font-family: 'Inter', sans-serif; }
    .gradient-bg { background: linear-gradient(135deg, #0F5132 0%, #16a34a 100%); }
    .glass-effect {
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .slide {
        transition: opacity 0.8s ease-in-out, transform 0.8s ease-in-out;
    }
    .slide.active { opacity: 1; transform: translateX(0); }
    .slide.inactive { opacity: 0; transform: translateX(100px); }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    .animate-float { animation: float 6s ease-in-out infinite; }
    .hover-scale { transition: transform 0.3s ease; }
    .hover-scale:hover { transform: scale(1.05); }
    .tab-btn.active {
        background-color: #16a34a !important;
        color: white !important;
    }
</style>
@endpush

@section('content')
<div class="font-inter">
    <!-- Hero Section with Slider -->
    <section class="relative h-screen overflow-hidden gradient-bg">
        <!-- Slides Container -->
        <div class="relative h-full">
            @if(isset($sliders) && $sliders->count() > 0)
                @foreach($sliders as $index => $slider)
                <div class="slide {{ $index === 0 ? 'active' : 'inactive' }} absolute inset-0 flex items-center justify-center"
                     @if($slider->image) style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.3)), url('{{ asset('storage/sliders/' . $slider->image) }}'); background-size: cover; background-position: center;" @endif>
                    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <div class="animate-slide-up">
                            <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight">
                                {!! str_replace(['AU-VLP', 'Africa', 'African'], ['<span class="bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">AU-VLP</span>', '<span class="bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">Africa</span>', '<span class="bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">African</span>'], $slider->title) !!}
                            </h1>
                            @if($slider->description)
                            <p class="text-xl md:text-2xl text-gray-200 mb-10 max-w-3xl mx-auto leading-relaxed">
                                {{ $slider->description }}
                            </p>
                            @endif
                            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                                @if($slider->button_text && $slider->button_url)
                                <a href="{{ $slider->button_url }}" 
                                   class="bg-yellow-400 text-black px-8 py-4 rounded-2xl font-semibold text-lg hover:bg-yellow-300 transition-all hover-scale shadow-lg">
                                    {{ $slider->button_text }}
                                </a>
                                @endif
                                <button class="glass-effect text-white px-8 py-4 rounded-2xl font-semibold text-lg hover:bg-white/20 transition-all">
                                    Learn More
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Floating Elements -->
                    <div class="absolute top-20 left-10 w-20 h-20 bg-yellow-400/20 rounded-full animate-float"></div>
                    <div class="absolute bottom-32 right-16 w-16 h-16 bg-white/10 rounded-full animate-float" style="animation-delay: 2s;"></div>
                    <div class="absolute top-1/2 right-8 w-12 h-12 bg-green-400/30 rounded-full animate-float" style="animation-delay: 4s;"></div>
                </div>
                @endforeach
            @else
                <!-- Default Hero Content -->
                <div class="slide active absolute inset-0 flex items-center justify-center">
                    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight">
                            Empowering
                            <span class="bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">
                                Africa
                            </span>
                            <br>Through Volunteerism
                        </h1>
                        <p class="text-xl md:text-2xl text-gray-200 mb-10 max-w-3xl mx-auto leading-relaxed">
                            African Union Volunteer Leadership Platform - Connecting volunteers across 55 member states
                        </p>
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            @guest
                                <a href="{{ route('registration.index') }}" 
                                   class="bg-yellow-400 text-black px-8 py-4 rounded-2xl font-semibold text-lg hover:bg-yellow-300 transition-all hover-scale shadow-lg">
                                    Join AU-VLP
                                </a>
                                <a href="{{ route('login') }}" 
                                   class="glass-effect text-white px-8 py-4 rounded-2xl font-semibold text-lg hover:bg-white/20 transition-all">
                                    Sign In
                                </a>
                            @else
                                <a href="{{ route('volunteer.dashboard') }}" 
                                   class="bg-yellow-400 text-black px-8 py-4 rounded-2xl font-semibold text-lg hover:bg-yellow-300 transition-all hover-scale shadow-lg">
                                    Go to Dashboard
                                </a>
                            @endguest
                        </div>
                    </div>
                    <div class="absolute top-20 left-10 w-20 h-20 bg-yellow-400/20 rounded-full animate-float"></div>
                    <div class="absolute bottom-32 right-16 w-16 h-16 bg-white/10 rounded-full animate-float" style="animation-delay: 2s;"></div>
                </div>
            @endif
        </div>
        
        <!-- Slider Navigation -->
        @if(isset($sliders) && $sliders->count() > 1)
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex space-x-3 z-20">
            @foreach($sliders as $index => $slider)
            <button class="slider-dot {{ $index === 0 ? 'active bg-white' : 'bg-white/50' }} w-4 h-4 rounded-full transition-all" onclick="goToSlide({{ $index }})"></button>
            @endforeach
        </div>
        @endif
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 right-8 text-white animate-bounce">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    Why Choose <span class="text-green-600">AU-VLP</span>?
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Discover opportunities, build skills, and create lasting impact across Africa's 55 member states
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                @if(isset($page_sections) && count($page_sections) > 0)
                    @foreach($page_sections->take(4) as $index => $section)
                    <div class="group bg-white p-8 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                        <div class="w-16 h-16 {{ ['bg-gradient-to-br from-green-500 to-green-600', 'bg-gradient-to-br from-yellow-400 to-yellow-500', 'bg-gradient-to-br from-blue-500 to-blue-600', 'bg-gradient-to-br from-purple-500 to-purple-600'][$index % 4] }} rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            @if($section->image)
                                <img src="{{ asset('storage/sections/' . $section->image) }}" alt="{{ $section->title }}" class="w-8 h-8 object-contain">
                            @else
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            @endif
                        </div>
                        @if($section->settings && isset($section->settings['link']) && $section->settings['link'])
                            <a href="{{ $section->settings['link'] }}" class="text-decoration-none">
                        @endif
                        <h3 class="text-xl font-bold text-gray-900 mb-4 group-hover:text-green-600 transition-colors">{{ $section->title }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ $section->content }}</p>
                        @if($section->settings && isset($section->settings['link']) && $section->settings['link'])
                            </a>
                        @endif
                    </div>
                    @endforeach
                @else
                    <!-- Default Feature Cards -->
                    <div class="group bg-white p-8 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <a href="{{ route('events.index') }}" class="text-decoration-none">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 group-hover:text-green-600 transition-colors">Volunteer Opportunities</h3>
                            <p class="text-gray-600 leading-relaxed">Access thousands of volunteer opportunities across education, healthcare, environment, and technology sectors.</p>
                        </a>
                    </div>
                    
                    <div class="group bg-white p-8 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                        <div class="w-16 h-16 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                        <a href="{{ route('about') }}" class="text-decoration-none">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 group-hover:text-green-600 transition-colors">Skill Development</h3>
                            <p class="text-gray-600 leading-relaxed">Develop professional skills through training programs and hands-on volunteer experience with certified outcomes.</p>
                        </a>
                    </div>
                    
                    <div class="group bg-white p-8 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <a href="{{ route('resources.index') }}" class="text-decoration-none">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 group-hover:text-green-600 transition-colors">Pan-African Network</h3>
                            <p class="text-gray-600 leading-relaxed">Connect with volunteers, organizations, and communities across all 55 African Union member states.</p>
                        </a>
                    </div>
                    
                    <div class="group bg-white p-8 rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <a href="#" class="text-decoration-none">
                            <h3 class="text-xl font-bold text-gray-900 mb-4 group-hover:text-green-600 transition-colors">Impact Tracking</h3>
                            <p class="text-gray-600 leading-relaxed">Track your volunteer impact with detailed analytics, certificates, and recognition for your contributions.</p>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-20 gradient-bg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center">
                <div class="group">
                    <div class="text-5xl lg:text-6xl font-bold text-white mb-4 group-hover:scale-110 transition-transform">
                        {{ isset($statistics['volunteers']) ? number_format($statistics['volunteers']) : '2.5K+' }}
                    </div>
                    <div class="text-xl text-gray-200">Active Volunteers</div>
                </div>
                <div class="group">
                    <div class="text-5xl lg:text-6xl font-bold text-yellow-400 mb-4 group-hover:scale-110 transition-transform">
                        {{ isset($statistics['organizations']) ? number_format($statistics['organizations']) : '150+' }}
                    </div>
                    <div class="text-xl text-gray-200">Partner Organizations</div>
                </div>
                <div class="group">
                    <div class="text-5xl lg:text-6xl font-bold text-white mb-4 group-hover:scale-110 transition-transform">
                        {{ isset($statistics['countries']) ? number_format($statistics['countries']) : '55' }}
                    </div>
                    <div class="text-xl text-gray-200">African Countries</div>
                </div>
                <div class="group">
                    <div class="text-5xl lg:text-6xl font-bold text-yellow-400 mb-4 group-hover:scale-110 transition-transform">
                        {{ isset($statistics['projects']) ? number_format($statistics['projects']) : '500+' }}
                    </div>
                    <div class="text-xl text-gray-200">Projects Completed</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Updates Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">Latest Updates</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Stay informed about new opportunities, success stories, and important announcements
                </p>
            </div>
            
            <!-- Tab Navigation -->
            <div class="flex justify-center mb-12">
                <div class="bg-white p-2 rounded-2xl shadow-lg">
                    <button class="tab-btn active px-8 py-3 rounded-xl font-semibold transition-all text-gray-600 hover:text-green-600" data-tab="opportunities">
                        Opportunities
                    </button>
                    <button class="tab-btn px-8 py-3 rounded-xl font-semibold transition-all text-gray-600 hover:text-green-600" data-tab="news">
                        News
                    </button>
                    <button class="tab-btn px-8 py-3 rounded-xl font-semibold transition-all text-gray-600 hover:text-green-600" data-tab="resources">
                        Resources
                    </button>
                    <button class="tab-btn px-8 py-3 rounded-xl font-semibold transition-all text-gray-600 hover:text-green-600" data-tab="blogs">
                        Blogs
                    </button>
                </div>
            </div>
            
            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Opportunities Tab -->
                <div id="opportunities" class="tab-pane active">
                    @if(isset($upcoming_events) && $upcoming_events->count() > 0)
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach($upcoming_events->take(6) as $event)
                        <div class="bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2">
                            <div class="h-48 {{ ['bg-gradient-to-br from-green-400 to-green-600', 'bg-gradient-to-br from-blue-400 to-blue-600', 'bg-gradient-to-br from-purple-400 to-purple-600', 'bg-gradient-to-br from-yellow-400 to-yellow-600', 'bg-gradient-to-br from-red-400 to-red-600', 'bg-gradient-to-br from-indigo-400 to-indigo-600'][($loop->index) % 6] }} relative overflow-hidden"
                                 @if($event->image) style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.5)), url('{{ asset('storage/events/' . $event->image) }}'); background-size: cover; background-position: center;" @endif>
                                <div class="absolute bottom-4 left-4 right-4">
                                    <span class="bg-yellow-400 text-black px-3 py-1 rounded-full text-sm font-semibold">
                                        {{ $event->category ?? 'Opportunity' }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">
                                    {{ $event->title }}
                                </h3>
                                <p class="text-gray-600 mb-4">{{ Str::limit($event->description, 120) }}</p>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">📍 {{ $event->location }}</span>
                                    <a href="{{ route('events.public.show', $event->slug) }}" 
                                       class="text-green-600 font-semibold hover:text-green-700 transition-colors">
                                        Apply Now →
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($upcoming_events->count() > 6)
                    <div class="text-center mt-12">
                        <a href="#" 
                           class="bg-green-600 text-white px-8 py-4 rounded-2xl font-semibold hover:bg-green-700 transition-colors inline-flex items-center">
                            View All Opportunities
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                            </svg>
                        </a>
                    </div>
                    @endif
                    @else
                    <div class="text-center py-12">
                        <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0H8m8 0v2a2 2 0 01-2 2H10a2 2 0 01-2-2V6"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">No Opportunities Available</h3>
                        <p class="text-gray-600 mb-8">New volunteer opportunities will be posted here regularly.</p>
                    </div>
                    @endif
                </div>

                <!-- News Tab -->
                <div id="news" class="tab-pane hidden">
                    @if(isset($recent_news) && $recent_news->count() > 0)
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach($recent_news->take(6) as $news)
                        <div class="bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2">
                            <div class="h-48 bg-gradient-to-br from-gray-400 to-gray-600 relative overflow-hidden"
                                 @if($news->featured_image) style="background-image: url('{{ asset('storage/news/' . $news->featured_image) }}'); background-size: cover; background-position: center;" @endif>
                                <div class="absolute inset-0 bg-black/20"></div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">
                                    {{ $news->title }}
                                </h3>
                                <p class="text-gray-600 mb-4">{{ Str::limit(strip_tags($news->content), 120) }}</p>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">{{ optional($news->published_at)->format('M d, Y') }}</span>
                                    <a href="{{ route('news.public.show', $news->slug) }}" 
                                       class="text-green-600 font-semibold hover:text-green-700 transition-colors">
                                        Read More →
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12">
                        <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m0 0V6a2 2 0 012-2h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">No News Available</h3>
                        <p class="text-gray-600 mb-8">Latest news and updates will be posted here.</p>
                    </div>
                    @endif
                </div>

                <!-- Resources Tab -->
                <div id="resources" class="tab-pane hidden">
                    @if(isset($featured_content) && isset($featured_content['resources']) && count($featured_content['resources']) > 0)
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach($featured_content['resources'] as $resource)
                        <div class="bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2">
                            <div class="p-6">
                                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-6">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">
                                    {{ $resource->title }}
                                </h3>
                                <p class="text-gray-600 mb-4">{{ Str::limit($resource->description, 120) }}</p>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">{{ $resource->type ?? 'Resource' }}</span>
                                    @auth
                                        <a href="{{ route('resources.public.show', $resource->slug) }}" 
                                           class="text-green-600 font-semibold hover:text-green-700 transition-colors" target="_blank">
                                            View →
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-sm">Login Required</span>
                                    @endauth
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12">
                        <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">No Resources Available</h3>
                        <p class="text-gray-600 mb-8">Training materials and guides will be available here.</p>
                    </div>
                    @endif
                </div>

                <!-- Blogs Tab -->
                <div id="blogs" class="tab-pane hidden">
                    @if(isset($featured_content) && isset($featured_content['blogs']) && count($featured_content['blogs']) > 0)
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach($featured_content['blogs'] as $blog)
                        <div class="bg-white rounded-3xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group hover:-translate-y-2">
                            <div class="h-48 bg-gradient-to-br from-gray-400 to-gray-600 relative overflow-hidden"
                                 @if($blog->featured_image) style="background-image: url('{{ asset('storage/blogs/' . $blog->featured_image) }}'); background-size: cover; background-position: center;" @endif>
                                <div class="absolute inset-0 bg-black/20"></div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">
                                    {{ $blog->title }}
                                </h3>
                                <p class="text-gray-600 mb-4">{{ Str::limit(strip_tags($blog->content), 120) }}</p>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500">{{ optional($blog->published_at)->format('M d, Y') }}</span>
                                    <a href="{{ route('blog.public.show', $blog->slug) }}" 
                                       class="text-green-600 font-semibold hover:text-green-700 transition-colors">
                                        Read More →
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-12">
                        <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">No Blog Posts Available</h3>
                        <p class="text-gray-600 mb-8">Inspiring stories and insights will be shared here.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions Section for Guests -->
    @guest
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-3xl p-12">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    Ready to Make a <span class="text-green-600">Difference</span>?
                </h2>
                <p class="text-xl text-gray-600 mb-10 max-w-2xl mx-auto">
                    Join thousands of volunteers across Africa who are creating positive change in their communities. Your journey starts here.
                </p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="{{ route('registration.volunteer.start') }}" 
                       class="bg-green-600 text-white px-10 py-4 rounded-2xl font-semibold text-lg hover:bg-green-700 transition-all hover-scale shadow-lg">
                        Join as Volunteer
                    </a>
                    <a href="{{ route('registration.organization.step', 'organization_details') }}" 
                       class="bg-white text-green-600 border-2 border-green-600 px-10 py-4 rounded-2xl font-semibold text-lg hover:bg-green-600 hover:text-white transition-all hover-scale">
                        Register Organization
                    </a>
                </div>
                
                <!-- Login Section -->
                <div class="mt-12 pt-8 border-t border-green-200">
                    <p class="text-gray-600 mb-4">Already have an account?</p>
                    <a href="{{ route('login') }}" 
                       class="inline-block border-2 border-green-600 text-green-600 px-8 py-3 rounded-xl font-semibold hover:bg-green-600 hover:text-white transition-all">
                        Sign In to Your Account
                    </a>
                </div>
            </div>
        </div>
    </section>
    @endguest

    <!-- Newsletter Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Social Media Section -->
                <div>
                    <h3 class="text-3xl font-bold text-gray-900 mb-6">Follow Our Journey</h3>
                    <p class="text-gray-600 mb-8">Stay connected with AU-VLP on social media for real-time updates, success stories, and community highlights.</p>
                    
                    <!-- Social Media Links -->
                    <div class="flex flex-wrap gap-4 mb-8">
                        <a href="https://twitter.com/AfricanUnion" target="_blank" rel="noopener noreferrer" 
                           class="flex items-center space-x-3 bg-white p-4 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-1">
                            <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                                </svg>
                            </div>
                            <span class="font-semibold text-gray-900">Twitter</span>
                        </a>
                        
                        <a href="https://www.facebook.com/AfricanUnionCommission" target="_blank" rel="noopener noreferrer" 
                           class="flex items-center space-x-3 bg-white p-4 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-1">
                            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </div>
                            <span class="font-semibold text-gray-900">Facebook</span>
                        </a>
                        
                        <a href="https://www.linkedin.com/company/african-union" target="_blank" rel="noopener noreferrer" 
                           class="flex items-center space-x-3 bg-white p-4 rounded-2xl shadow-lg hover:shadow-xl transition-all hover:-translate-y-1">
                            <div class="w-10 h-10 bg-blue-700 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </div>
                            <span class="font-semibold text-gray-900">LinkedIn</span>
                        </a>
                    </div>
                </div>

                <!-- Newsletter Subscription Section -->
                <div class="bg-white p-8 rounded-3xl shadow-lg">
                    <h3 class="text-3xl font-bold text-gray-900 mb-6">Stay Updated</h3>
                    <p class="text-gray-600 mb-6">Subscribe to our newsletter for the latest volunteer opportunities, success stories, and AU-VLP updates.</p>
                    
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <input type="email" name="email" placeholder="Enter your email address" required
                                   class="w-full px-6 py-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <input type="text" name="name" placeholder="Your name (optional)"
                                   class="w-full px-6 py-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        </div>
                        
                        <div class="space-y-2">
                            <p class="text-sm font-medium text-gray-700">How often would you like to hear from us?</p>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center">
                                    <input type="radio" name="frequency" value="weekly" checked class="text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-sm text-gray-700">Weekly</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="frequency" value="monthly" class="text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-sm text-gray-700">Monthly</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="frequency" value="quarterly" class="text-green-600 focus:ring-green-500">
                                    <span class="ml-2 text-sm text-gray-700">Quarterly</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <input type="checkbox" name="consent" required class="mt-1 text-green-600 focus:ring-green-500">
                            <label class="ml-2 text-sm text-gray-600">
                                I agree to receive newsletters and understand I can unsubscribe at any time.
                            </label>
                        </div>
                        
                        <button type="submit" class="w-full bg-green-600 text-white px-8 py-4 rounded-2xl font-semibold hover:bg-green-700 transition-colors">
                            Subscribe Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Slider functionality
    let currentSlide = 0;
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dot');
    const totalSlides = slides.length;

    function showSlide(index) {
        slides.forEach((slide, i) => {
            if (i === index) {
                slide.classList.remove('inactive');
                slide.classList.add('active');
            } else {
                slide.classList.remove('active');
                slide.classList.add('inactive');
            }
        });
        
        dots.forEach((dot, i) => {
            if (i === index) {
                dot.classList.add('active', 'bg-white');
                dot.classList.remove('bg-white/50');
            } else {
                dot.classList.remove('active', 'bg-white');
                dot.classList.add('bg-white/50');
            }
        });
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }

    window.goToSlide = function(index) {
        currentSlide = index;
        showSlide(currentSlide);
    }

    // Auto-advance slides every 8 seconds
    if (totalSlides > 1) {
        setInterval(nextSlide, 8000);
    }

    // Tab functionality
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');
            
            // Remove active class from all tabs and panes
            tabBtns.forEach(b => {
                b.classList.remove('active');
            });
            tabPanes.forEach(pane => {
                pane.classList.add('hidden');
                pane.classList.remove('active');
            });
            
            // Add active class to clicked tab and corresponding pane
            btn.classList.add('active');
            const targetPane = document.getElementById(targetTab);
            if (targetPane) {
                targetPane.classList.remove('hidden');
                targetPane.classList.add('active');
            }
        });
    });

    // Initialize first tab as active
    const firstTab = document.querySelector('.tab-btn[data-tab="opportunities"]');
    if (firstTab) {
        firstTab.classList.add('active');
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe sections for animation
    document.querySelectorAll('section').forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(section);
    });
});
</script>
@endpush
@endsection