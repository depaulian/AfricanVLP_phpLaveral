@extends('layouts.app')

@section('title', 'Welcome to AU-VLP')

@push('styles')
<style>
    .carousel-item {
        height: 500px;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }
    .about-boxes .card {
        height: 100%;
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }
    .about-boxes .card:hover {
        transform: translateY(-5px);
    }
    .about-boxes .icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 50%;
    }
    .updates-tab .nav-tabs {
        border-bottom: 2px solid #e9ecef;
    }
    .updates-tab .nav-link {
        border: none;
        color: #6c757d;
        font-weight: 600;
        padding: 15px 30px;
    }
    .updates-tab .nav-link.active {
        color: #2C5535;
        border-bottom: 3px solid #2C5535;
        background: none;
    }
    .card-img {
        min-height: 150px;
        background-size: cover;
        background-position: center;
    }
    .social-section {
        background: #f8f9fa;
        padding: 60px 0;
    }
</style>
@endpush

@section('content')
<div class="main">
    <!-- Hero Carousel Section -->
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="10000">
        @if(isset($sliders) && $sliders->count() > 0)
            <!-- Carousel Indicators -->
            <div class="carousel-indicators">
                @foreach($sliders as $index => $slider)
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $index }}" 
                        class="{{ $index === 0 ? 'active' : '' }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}" 
                        aria-label="Slide {{ $index + 1 }}"></button>
                @endforeach
            </div>

            <!-- Carousel Inner -->
            <div class="carousel-inner">
                @foreach($sliders as $index => $slider)
                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}" 
                     @if($slider->image) style="background-image: url('{{ asset('storage/sliders/' . $slider->image) }}')" @endif>
                    <div class="carousel-caption d-flex align-items-center justify-content-start h-100">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-8">
                                    <h1 class="display-4 fw-bold text-white mb-4 animate__animated animate__slideInRight">
                                        {{ $slider->title }}
                                    </h1>
                                    @if($slider->description)
                                    <p class="lead text-white mb-4 animate__animated animate__slideInRight animate__delay-1s">
                                        {{ $slider->description }}
                                    </p>
                                    @endif
                                    @if($slider->button_text && $slider->button_url)
                                    <a href="{{ $slider->button_url }}" 
                                       class="btn btn-light btn-lg animate__animated animate__slideInRight animate__delay-2s">
                                        {{ $slider->button_text }}
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50"></div>
                </div>
                @endforeach
            </div>
        @else
            <!-- Default Hero Content -->
            <div class="carousel-inner">
                <div class="carousel-item active" style="background: linear-gradient(135deg, #2C5535 0%, #4a7c59 100%);">
                    <div class="carousel-caption d-flex align-items-center justify-content-start h-100">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-8">
                                    <h1 class="display-4 fw-bold text-white mb-4">Welcome to AU-VLP</h1>
                                    <p class="lead text-white mb-4">
                                        African Union Volunteer Leadership Platform - Empowering Communities Through Volunteerism
                                    </p>
                                    @guest
                                        <div class="d-flex gap-3">
                                            <a href="{{ route('registration.index') }}" 
                                               class="btn btn-light btn-lg">Join AU-VLP</a>
                                            <a href="{{ route('login') }}" 
                                               class="btn btn-outline-light btn-lg">Sign In</a>
                                        </div>
                                    @else
                                        <a href="{{ route('volunteer.dashboard') }}" 
                                           class="btn btn-light btn-lg">Go to Dashboard</a>
                                    @endguest
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- About Boxes Section -->
    <section class="about-boxes py-5" id="about">
        <div class="container">
            <div class="row d-flex align-items-stretch">
                @if(isset($page_sections) && count($page_sections) > 0)
                    @foreach($page_sections->take(4) as $section)
                    <div class="col-md-3 mb-4">
                        <div class="card d-flex flex-column h-100 text-center p-4">
                            <div class="icon">
                                @if($section->image)
                                    <img src="{{ asset('storage/sections/' . $section->image) }}" alt="{{ $section->title }}" class="img-fluid">
                                @else
                                    <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                @endif
                            </div>
                            @if($section->settings && isset($section->settings['link']) && $section->settings['link'])
                                <a href="{{ $section->settings['link'] }}" class="text-decoration-none">
                            @endif
                            <h4 class="mt-3 mb-3">{{ $section->title }}</h4>
                            <p class="text-muted">{{ $section->content }}</p>
                            @if($section->settings && isset($section->settings['link']) && $section->settings['link'])
                                </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @else
                    <!-- Default About Blocks -->
                    <div class="col-md-3 mb-4">
                        <div class="card d-flex flex-column h-100 text-center p-4">
                            <div class="icon">
                                <svg class="w-16 h-16 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <a href="{{ route('events.index') }}" class="text-decoration-none">
                                <h4 class="mt-3 mb-3">Volunteer Opportunities</h4>
                                <p class="text-muted">Discover meaningful volunteer opportunities across Africa and make a lasting impact in communities.</p>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card d-flex flex-column h-100 text-center p-4">
                            <div class="icon">
                                <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <a href="{{ route('about') }}" class="text-decoration-none">
                                <h4 class="mt-3 mb-3">Impact of Volunteerism</h4>
                                <p class="text-muted">Learn about the transformative power of volunteerism and its impact on African communities.</p>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card d-flex flex-column h-100 text-center p-4">
                            <div class="icon">
                                <svg class="w-16 h-16 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <a href="{{ route('resources.index') }}" class="text-decoration-none">
                                <h4 class="mt-3 mb-3">Policy & Resources</h4>
                                <p class="text-muted">Access comprehensive policies, guidelines, and resources for effective volunteer management.</p>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card d-flex flex-column h-100 text-center p-4">
                            <div class="icon">
                                <svg class="w-16 h-16 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <a href="{{ route('pages.interactive-map') }}" class="text-decoration-none">
                                <h4 class="mt-3 mb-3">Country Profiles</h4>
                                <p class="text-muted">Explore detailed country profiles and discover volunteer opportunities by region.</p>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Latest Updates Section -->
    <section class="updates py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Latest Updates</h2>
            <div class="updates-tab">
                <div class="container">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs d-flex justify-content-center mb-4" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#opportunities-tab" role="tab">Opportunities</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#news-tab" role="tab">News</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#resources-tab" role="tab">Resources</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#blogs-tab" role="tab">Blogs</a>
                        </li>
                    </ul>

                    <!-- Tab content -->
                    <div class="tab-content">
                        <!-- Opportunities Tab -->
                        <div id="opportunities-tab" class="tab-pane fade show active" role="tabpanel">
                            @if(isset($upcoming_events) && $upcoming_events->count() > 0)
                                @foreach($upcoming_events->take(4) as $event)
                                <div class="card mb-4">
                                    <div class="row g-0 align-items-stretch">
                                        <div class="col-lg-3">
                                            <div class="card-img h-100" 
                                                 style="background-image: url('{{ $event->image ? asset('storage/events/' . $event->image) : asset('images/default-event.jpg') }}');">
                                            </div>
                                        </div>
                                        <div class="col-lg-9">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-lg-9 d-flex flex-column">
                                                        <h4 class="card-title">{{ $event->title }}</h4>
                                                        <p class="card-text">{{ Str::limit($event->description, 150) }}</p>
                                                        <div class="row list-tag align-items-end mt-auto">
                                                            <div class="col-md-4">
                                                                <p class="text-muted mb-0">Date: 
                                                                    <span>{{ optional($event->start_date)->format('M d, Y') }}</span>
                                                                </p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <p class="text-muted mb-0">Organizer: 
                                                                    <span>{{ $event->organization->name ?? '—' }}</span>
                                                                </p>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <a href="{{ route('events.public.show', $event->slug) }}" 
                                                                   class="text-decoration-none fw-bold" style="color: #2C5535;">
                                                                    Apply Now
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3 d-flex align-items-center justify-content-end">
                                                        <a href="{{ route('events.public.show', $event->slug) }}" 
                                                           class="btn btn-outline-primary">View</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between">
                                        <div class="location">
                                            <p class="mb-0">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                {{ $event->location }}
                                            </p>
                                        </div>
                                        <div class="sector d-flex align-items-center">
                                            <!-- Categories can be added here -->
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                @if($upcoming_events->count() >= 4)
                                <div class="text-center">
                                    <a href="{{ route('events.public') }}" class="btn btn-link text-decoration-none">
                                        SEE MORE <i class="fas fa-caret-right"></i>
                                    </a>
                                </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <p class="text-muted">No opportunities available at the moment.</p>
                                    <a href="{{ route('events.public') }}" class="btn btn-primary">Browse All Opportunities</a>
                                </div>
                            @endif
                        </div>

                        <!-- News Tab -->
                        <div id="news-tab" class="tab-pane fade" role="tabpanel">
                            @if(isset($recent_news) && $recent_news->count() > 0)
                                @foreach($recent_news->take(4) as $news)
                                <div class="card mb-4">
                                    <div class="row g-0 align-items-stretch">
                                        <div class="col-lg-3">
                                            <div class="card-img h-100" 
                                                 style="background-image: url('{{ $news->featured_image ? asset('storage/news/' . $news->featured_image) : asset('images/default-news.jpg') }}');">
                                            </div>
                                        </div>
                                        <div class="col-lg-9">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-lg-9 d-flex flex-column">
                                                        <h4 class="card-title">{{ $news->title }}</h4>
                                                        <p class="card-text">{{ Str::limit(strip_tags($news->content), 150) }}</p>
                                                        <div class="row list-tag align-items-end mt-auto">
                                                            <div class="col-md-6">
                                                                <p class="text-muted mb-0">Date: 
                                                                    <span>{{ optional($news->published_at)->format('M d, Y') }}</span>
                                                                </p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p class="text-muted mb-0">Source: 
                                                                    <span>{{ $news->organization ? $news->organization->name : 'AU-VLP' }}</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3 d-flex align-items-center justify-content-end">
                                                        <a href="{{ route('news.public.show', $news->slug) }}" 
                                                           class="btn btn-outline-primary">Read More</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                @if($recent_news->count() >= 4)
                                <div class="text-center">
                                    <a href="{{ route('news.public') }}" class="btn btn-link text-decoration-none">
                                        SEE MORE <i class="fas fa-caret-right"></i>
                                    </a>
                                </div>
                                @endif
                            @else
                                <div class="text-center py-5">
                                    <p class="text-muted">No news available at the moment.</p>
                                    <a href="{{ route('news.public') }}" class="btn btn-primary">Browse All News</a>
                                </div>
                            @endif
                        </div>

                        <!-- Resources Tab -->
                        <div id="resources-tab" class="tab-pane fade" role="tabpanel">
                            @if(isset($featured_content) && isset($featured_content['resources']) && count($featured_content['resources']) > 0)
                                @foreach($featured_content['resources'] as $resource)
                                <div class="card mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-9 d-flex flex-column">
                                                <h4 class="card-title">{{ $resource->title }}</h4>
                                                <p class="card-text">{{ Str::limit($resource->description, 150) }}</p>
                                                <div class="row list-tag align-items-end mt-auto">
                                                    <div class="col-md-6">
                                                        <p class="text-muted mb-0">Date: 
                                                            <span>{{ optional($resource->created_at)->format('M d, Y') }}</span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="text-muted mb-0">Type: 
                                                            <span>{{ $resource->type ?? 'Resource' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 d-flex align-items-center justify-content-end">
                                                @auth
                                                    <a href="{{ route('resources.public.show', $resource->slug) }}" 
                                                       class="btn btn-outline-primary" target="_blank">View</a>
                                                @else
                                                    <div class="alert alert-info mb-0" role="alert">
                                                        <small>Please login to view resources</small>
                                                    </div>
                                                @endauth
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                <div class="text-center">
                                    <a href="{{ route('resources.public') }}" class="btn btn-link text-decoration-none">
                                        SEE MORE <i class="fas fa-caret-right"></i>
                                    </a>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <p class="text-muted">No resources available at the moment.</p>
                                    <a href="{{ route('resources.public') }}" class="btn btn-primary">Browse All Resources</a>
                                </div>
                            @endif
                        </div>

                        <!-- Blogs Tab -->
                        <div id="blogs-tab" class="tab-pane fade" role="tabpanel">
                            @if(isset($featured_content) && isset($featured_content['blogs']) && count($featured_content['blogs']) > 0)
                                @foreach($featured_content['blogs'] as $blog)
                                <div class="card mb-4">
                                    <div class="row g-0 align-items-stretch">
                                        <div class="col-lg-3">
                                            <div class="card-img h-100" 
                                                 style="background-image: url('{{ $blog->featured_image ? asset('storage/blogs/' . $blog->featured_image) : asset('images/default-blog.jpg') }}');">
                                            </div>
                                        </div>
                                        <div class="col-lg-9">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-lg-9 d-flex flex-column">
                                                        <h4 class="card-title">{{ $blog->title }}</h4>
                                                        <p class="card-text">{{ Str::limit(strip_tags($blog->content), 150) }}</p>
                                                        <div class="row list-tag align-items-end mt-auto">
                                                            <div class="col-md-6">
                                                                <p class="text-muted mb-0">Date: 
                                                                    <span>{{ optional($blog->published_at)->format('M d, Y') }}</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3 d-flex align-items-center justify-content-end">
                                                        <a href="{{ route('blog.public.show', $blog->slug) }}" 
                                                           class="btn btn-outline-primary">Read More</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                                <div class="text-center">
                                    <a href="{{ route('blog.public') }}" class="btn btn-link text-decoration-none">
                                        SEE MORE <i class="fas fa-caret-right"></i>
                                    </a>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <p class="text-muted">No blog posts available at the moment.</p>
                                    <a href="{{ route('blog.public') }}" class="btn btn-primary">Browse All Blogs</a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions Section for Guests -->
    @guest
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Get Started with AU-VLP</h2>
                <p class="text-xl text-gray-600">Join thousands of volunteers making a difference across Africa</p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Individual Registration -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Individual Volunteer</h3>
                    <p class="text-gray-600 mb-6">Join as an individual volunteer and discover opportunities to make a meaningful impact in your community and across Africa.</p>
                    <a href="{{ route('registration.volunteer') }}" 
                       class="inline-block w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-300">
                        Register as Volunteer
                    </a>
                </div>

                <!-- Organization Registration -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Organization</h3>
                    <p class="text-gray-600 mb-6">Register your organization to connect with volunteers, post opportunities, and expand your impact across African communities.</p>
                    <a href="{{ route('registration.organization.step', 'organization_details') }}" 
                       class="inline-block w-full bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition duration-300">
                        Register Organization
                    </a>
                </div>
            </div>

            <!-- Login Section -->
            <div class="text-center mt-12">
                <p class="text-gray-600 mb-4">Already have an account?</p>
                <a href="{{ route('login') }}" 
                   class="inline-block border-2 border-blue-600 text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 hover:text-white transition duration-300">
                    Sign In to Your Account
                </a>
            </div>
        </div>
    </section>
    @endguest

    <!-- Social Media and Newsletter Section -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row">
                <!-- Social Media Section -->
                <div class="col-lg-6 mb-5">
                    <h3 class="mb-4">Follow Us</h3>
                    <div class="social-media-section">
                        <!-- Twitter Timeline -->
                        <div class="twitter-timeline mb-4">
                            <a class="twitter-timeline" 
                               data-height="400" 
                               data-theme="light" 
                               href="https://twitter.com/AfricanUnion?ref_src=twsrc%5Etfw">
                               Tweets by AfricanUnion
                            </a>
                            <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
                        </div>
                        
                        <!-- Social Media Links -->
                        <div class="social-links d-flex flex-wrap gap-3">
                            <a href="https://twitter.com/AfricanUnion" target="_blank" rel="noopener noreferrer" 
                               class="btn btn-outline-primary btn-sm d-flex align-items-center">
                                <i class="fab fa-twitter me-2"></i> Twitter
                            </a>
                            <a href="https://www.facebook.com/AfricanUnionCommission" target="_blank" rel="noopener noreferrer" 
                               class="btn btn-outline-primary btn-sm d-flex align-items-center">
                                <i class="fab fa-facebook me-2"></i> Facebook
                            </a>
                            <a href="https://www.linkedin.com/company/african-union" target="_blank" rel="noopener noreferrer" 
                               class="btn btn-outline-primary btn-sm d-flex align-items-center">
                                <i class="fab fa-linkedin me-2"></i> LinkedIn
                            </a>
                            <a href="https://www.youtube.com/user/AfricanUnionCommission" target="_blank" rel="noopener noreferrer" 
                               class="btn btn-outline-primary btn-sm d-flex align-items-center">
                                <i class="fab fa-youtube me-2"></i> YouTube
                            </a>
                            <a href="https://www.instagram.com/africanunion_official" target="_blank" rel="noopener noreferrer" 
                               class="btn btn-outline-primary btn-sm d-flex align-items-center">
                                <i class="fab fa-instagram me-2"></i> Instagram
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Newsletter Subscription Section -->
                <div class="col-lg-6 mb-5">
                    <h3 class="mb-4">Stay Updated</h3>
                    <div class="newsletter-section">
                        <p class="text-muted mb-4">Subscribe to our newsletter to receive the latest updates on volunteer opportunities, news, and events across Africa.</p>
                        
                        <form action="{{ route('newsletter.subscribe') }}" method="POST" class="newsletter-form">
                            @csrf
                            <div class="mb-3">
                                <label for="newsletter_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="newsletter_email" name="email" 
                                       placeholder="Enter your email address" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="newsletter_name" class="form-label">Name (Optional)</label>
                                <input type="text" class="form-control" id="newsletter_name" name="name" 
                                       placeholder="Enter your name">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Subscription Frequency</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="frequency" id="weekly" value="weekly" checked>
                                    <label class="form-check-label" for="weekly">
                                        Weekly Updates
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="frequency" id="monthly" value="monthly">
                                    <label class="form-check-label" for="monthly">
                                        Monthly Digest
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="frequency" id="quarterly" value="quarterly">
                                    <label class="form-check-label" for="quarterly">
                                        Quarterly Newsletter
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="newsletter_consent" name="consent" required>
                                <label class="form-check-label" for="newsletter_consent">
                                    I agree to receive newsletters and understand I can unsubscribe at any time.
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-envelope me-2"></i>Subscribe Now
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="row">
                <div class="col-md-3 text-center mb-8">
                    <div class="text-4xl font-bold text-blue-600 mb-2">
                        {{ isset($statistics['volunteers']) ? number_format($statistics['volunteers']) : '2,500+' }}
                    </div>
                    <div class="text-gray-600">Active Volunteers</div>
                </div>
                <div class="col-md-3 text-center mb-8">
                    <div class="text-4xl font-bold text-green-600 mb-2">
                        {{ isset($statistics['organizations']) ? number_format($statistics['organizations']) : '150+' }}
                    </div>
                    <div class="text-gray-600">Partner Organizations</div>
                </div>
                <div class="col-md-3 text-center mb-8">
                    <div class="text-4xl font-bold text-purple-600 mb-2">
                        {{ isset($statistics['countries']) ? number_format($statistics['countries']) : '54' }}
                    </div>
                    <div class="text-gray-600">African Countries</div>
                </div>
                <div class="col-md-3 text-center mb-8">
                    <div class="text-4xl font-bold text-orange-600 mb-2">
                        {{ isset($statistics['projects']) ? number_format($statistics['projects']) : '500+' }}
                    </div>
                    <div class="text-gray-600">Projects Completed</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Content Section -->
    @if(isset($featured_content) && (count($featured_content['news']) > 0 || count($featured_content['events']) > 0 || count($featured_content['organizations']) > 0))
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Featured Content</h2>
            
            <div class="row">
                <!-- Featured News -->
                @if(count($featured_content['news']) > 0)
                <div class="col-lg-4 mb-8">
                    <h3 class="text-xl font-semibold mb-6">Latest News</h3>
                    @foreach($featured_content['news'] as $news)
                    <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                        @if($news->featured_image)
                        <img src="{{ asset('storage/news/' . $news->featured_image) }}" alt="{{ $news->title }}" class="w-full h-48 object-cover rounded-lg mb-4">
                        @endif
                        <h4 class="font-semibold mb-2">{{ $news->title }}</h4>
                        <p class="text-gray-600 text-sm mb-3">{{ Str::limit(strip_tags($news->content), 100) }}</p>
                        <a href="{{ route('news.show', $news->slug) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Read More →</a>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Featured Events -->
                @if(count($featured_content['events']) > 0)
                <div class="col-lg-4 mb-8">
                    <h3 class="text-xl font-semibold mb-6">Upcoming Events</h3>
                    @foreach($featured_content['events'] as $event)
                    <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                        @if($event->image)
                        <img src="{{ asset('storage/events/' . $event->image) }}" alt="{{ $event->title }}" class="w-full h-48 object-cover rounded-lg mb-4">
                        @endif
                        <h4 class="font-semibold mb-2">{{ $event->title }}</h4>
                        <p class="text-gray-600 text-sm mb-2">{{ Str::limit($event->description, 100) }}</p>
                        <p class="text-blue-600 text-sm mb-3">{{ $event->start_date->format('M d, Y') }}</p>
                        <a href="{{ route('events.show', $event->slug) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Learn More →</a>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Featured Organizations -->
                @if(count($featured_content['organizations']) > 0)
                <div class="col-lg-4 mb-8">
                    <h3 class="text-xl font-semibold mb-6">Featured Organizations</h3>
                    @foreach($featured_content['organizations'] as $org)
                    <div class="bg-white rounded-lg shadow-md p-6 mb-4">
                        @if($org->logo)
                        <img src="{{ asset('storage/organizations/' . $org->logo) }}" alt="{{ $org->name }}" class="w-full h-32 object-contain rounded-lg mb-4">
                        @endif
                        <h4 class="font-semibold mb-2">{{ $org->name }}</h4>
                        <p class="text-gray-600 text-sm mb-3">{{ Str::limit($org->description, 100) }}</p>
                        <a href="{{ route('organizations.show', $org->slug) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View Profile →</a>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </section>
    @endif

    <!-- Call to Action Section -->
    @guest
    <section class="py-16 bg-blue-600 text-white">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold mb-4">Ready to Make a Difference?</h2>
            <p class="text-xl mb-8 text-blue-100">
                Join the African Union Volunteer Leadership Platform and connect with opportunities to create positive change across Africa.
            </p>
            <div class="space-x-4">
                <a href="{{ route('registration.index') }}" 
                   class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition duration-300">
                    Get Started Today
                </a>
                <a href="{{ route('about') }}" 
                   class="inline-block border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition duration-300">
                    Learn More
                </a>
            </div>
        </div>
    </section>
    @endguest
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Slider functionality
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dot');
    let currentSlide = 0;

    if (slides.length > 1) {
        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('opacity-100', i === index);
                slide.classList.toggle('opacity-0', i !== index);
            });
            
            dots.forEach((dot, i) => {
                dot.classList.toggle('bg-white', i === index);
                dot.classList.toggle('bg-white', i !== index);
                dot.classList.toggle('bg-opacity-50', i !== index);
            });
        }

        // Auto-advance slides
        setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }, 5000);

        // Dot navigation
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });
    }
});
</script>
@endpush
@endsection
