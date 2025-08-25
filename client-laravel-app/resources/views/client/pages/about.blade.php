@extends('layouts.app')

@section('title', 'About AU-VLP - African Union Volunteering Platform')

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
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-15px); }
    }
    .animate-float { animation: float 8s ease-in-out infinite; }
    .hover-scale { transition: transform 0.3s ease; }
    .hover-scale:hover { transform: scale(1.05); }
    
    @keyframes slideInFromLeft {
        0% { opacity: 0; transform: translateX(-30px); }
        100% { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes slideInFromRight {
        0% { opacity: 0; transform: translateX(30px); }
        100% { opacity: 1; transform: translateX(0); }
    }
    
    .animate-slide-left { animation: slideInFromLeft 0.8s ease-out; }
    .animate-slide-right { animation: slideInFromRight 0.8s ease-out; }
</style>
@endpush

@section('content')
<div class="font-inter">
    <!-- Hero Banner Section -->
    <section class="relative h-[60vh] min-h-[300px] max-h-[300px] overflow-hidden gradient-bg">
        <div class="absolute inset-0 bg-black/20"></div>
        
        <!-- Floating Elements -->
        <div class="absolute top-16 left-8 w-24 h-24 bg-yellow-400/20 rounded-full animate-float"></div>
        <div class="absolute bottom-20 right-12 w-20 h-20 bg-white/10 rounded-full animate-float" style="animation-delay: 3s;"></div>
        <div class="absolute top-1/3 right-8 w-16 h-16 bg-green-400/30 rounded-full animate-float" style="animation-delay: 1.5s;"></div>
        <div class="absolute bottom-1/3 left-16 w-12 h-12 bg-yellow-300/25 rounded-full animate-float" style="animation-delay: 4s;"></div>
        
        <div class="relative z-10 h-full flex items-center justify-center">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="animate-slide-up">
                    <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                        About 
                        <span class="bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">
                            AU-VLP
                        </span>
                    </h1>
                    <p class="text-xl md:text-2xl text-gray-200 mb-8 max-w-3xl mx-auto leading-relaxed">
                        Empowering Africa through volunteerism, one community at a time
                    </p>
                    <div class="w-24 h-1 bg-yellow-400 mx-auto rounded-full"></div>
                </div>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <div class="absolute bottom-6 left-6 text-white/80 text-sm">
            <div class="flex items-center space-x-2">
                <a href="{{ route('home') }}" class="hover:text-yellow-400 transition-colors">Home</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-yellow-400">About Us</span>
            </div>
        </div>
    </section>

    <!-- Mission & Vision Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Content -->
                <div class="animate-slide-left">
                    <div class="mb-8">
                        <span class="text-green-600 font-semibold text-sm uppercase tracking-wide">Our Purpose</span>
                        <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mt-3 mb-6">
                            Transforming 
                            <span class="text-green-600">Africa</span>
                            Through Unity
                        </h2>
                        <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-yellow-400 rounded-full"></div>
                    </div>
                    
                    <p class="text-lg text-gray-600 leading-relaxed mb-6">
                        The Heads of State and Government of the African Union, in decision Assembly / AU / Dec.274 (XVII), recognized youth volunteering as a tool for youth empowerment and a catalyst for the development of youth in the continent. The decision further mandates the African Union Commission (AUC) to promote youth volunteering in Africa.
                    </p>
                    
                    <p class="text-gray-600 leading-relaxed mb-8">
                        The African Union Commission (AUC) strongly believes that volunteering does not happen in isolation and therefore requires an enabling environment and strategic collaboration among stakeholders to ensure that volunteering receives greater attention and actively contributes to economic development and social cohesion in Africa.
                    </p>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('registration.index') }}" 
                           class="bg-green-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-green-700 transition-all hover-scale text-center">
                            Join Our Mission
                        </a>
                        <a href="{{ route('contact') }}" 
                           class="border-2 border-green-600 text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-600 hover:text-white transition-all text-center">
                            Get in Touch
                        </a>
                    </div>
                </div>
                
                <!-- Right Visual -->
                <div class="animate-slide-right">
                    <div class="relative">
                        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-3xl p-8 shadow-2xl">
                            <div class="grid grid-cols-2 gap-6 text-white">
                                <div class="text-center">
                                    <div class="text-3xl font-bold mb-2">55</div>
                                    <div class="text-sm opacity-90">Member States</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold mb-2">1.3B+</div>
                                    <div class="text-sm opacity-90">People Served</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold mb-2">2.5K+</div>
                                    <div class="text-sm opacity-90">Active Volunteers</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold mb-2">500+</div>
                                    <div class="text-sm opacity-90">Projects Completed</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Floating accent -->
                        <div class="absolute -top-6 -right-6 w-32 h-32 bg-yellow-400/20 rounded-full animate-float"></div>
                        <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-green-400/20 rounded-full animate-float" style="animation-delay: 2s;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- VLP Functions Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-green-600 font-semibold text-sm uppercase tracking-wide">Platform Functions</span>
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mt-3 mb-6">
                    What AU-VLP Does
                </h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-yellow-400 rounded-full mx-auto"></div>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto mt-6">
                    In this context, the AUC developed in close consultation with the main stakeholders an online volunteering linkage platform (VLP) to provide an overview of volunteering and exchange programs.
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8 mb-12">
                <!-- Knowledge -->
                <div class="group bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <span class="text-red-600">Knowledge:</span> Hub
                    </h3>
                    <p class="text-gray-600 leading-relaxed">It serves as a knowledge hub providing comprehensive information on volunteering policies, programs, and best practices across Africa.</p>
                </div>
                
                <!-- Information -->
                <div class="group bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <span class="text-blue-600">Information:</span> Gateway
                    </h3>
                    <p class="text-gray-600 leading-relaxed">It serves as an information gateway on volunteerism, connecting stakeholders with relevant data and opportunities.</p>
                </div>
                
                <!-- Connection -->
                <div class="group bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-2 border border-gray-100">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <span class="text-green-600">Connection:</span> Network
                    </h3>
                    <p class="text-gray-600 leading-relaxed">Connects volunteers and their organizations, fostering collaboration and community building across the continent.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Need for VLP Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Content -->
                <div>
                    <span class="text-green-600 font-semibold text-sm uppercase tracking-wide">Platform Need</span>
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mt-3 mb-6">
                        The Need for the Volunteer
                        <span class="text-green-600">Linkage Platform</span>
                    </h2>
                    <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-yellow-400 rounded-full mb-8"></div>
                    
                    <p class="text-lg text-gray-600 leading-relaxed mb-6">
                        The African landscape of volunteers and exchanges includes wide range of programs, policies, and stakeholders working at different levels (national, regional, continental and international) with little or no coordination.
                    </p>
                    
                    <p class="text-gray-600 leading-relaxed mb-8">
                        The Volunteer Linkage Platform (VLP) comes as an instrument that brings together all stakeholders and creates a space for information exchange and data generation that can be leveraged to improve volunteerism in Africa.
                    </p>

                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 bg-green-600 rounded-full mt-2"></div>
                            <p class="text-gray-600">Provide an overview of volunteering and youth exchange policies, programmes and statistics searchable by region, country, thematic area and target group</p>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 bg-green-600 rounded-full mt-2"></div>
                            <p class="text-gray-600">Provide volunteer practitioners and volunteers the opportunity to learn from each other and exchange in the frame of communities of practice</p>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 bg-green-600 rounded-full mt-2"></div>
                            <p class="text-gray-600">Provide a space for the provision of tools and good practices</p>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 bg-green-600 rounded-full mt-2"></div>
                            <p class="text-gray-600">Provide volunteer organizations with possibilities to showcase their programs and volunteering efforts</p>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 bg-green-600 rounded-full mt-2"></div>
                            <p class="text-gray-600">Provide young people on the African continent with the possibility to learn more about the benefits of volunteering and to search for volunteering and exchange opportunities</p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Image Section -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-gray-100 to-gray-200 rounded-3xl p-2 shadow-xl">
                        <div class="bg-white rounded-2xl p-6 text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0H8m8 0v2a2 2 0 01-2 2H10a2 2 0 01-2-2V6"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Marketplace for Volunteer Initiatives</h3>
                            <p class="text-gray-600 leading-relaxed">
                                The VLP is conceived as a marketplace for volunteer initiatives and actors on the African continent, providing information and connecting stakeholders across the ecosystem.
                            </p>
                        </div>
                    </div>
                    <div class="absolute -top-4 -left-4 w-16 h-16 bg-yellow-400/30 rounded-full animate-float"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Continental Platform Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Image/Visual -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-blue-100 to-blue-200 rounded-3xl p-2 shadow-xl">
                        <div class="bg-white rounded-2xl p-6 text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 mb-4">Continental Integration</h3>
                            <p class="text-gray-600 leading-relaxed">
                                Embedded into the Continental Volunteer Linkage Platform, creating unified standards across Africa.
                            </p>
                        </div>
                    </div>
                    <div class="absolute -bottom-4 -right-4 w-16 h-16 bg-green-400/30 rounded-full animate-float" style="animation-delay: 2s;"></div>
                </div>

                <!-- Right Content -->
                <div>
                    <span class="text-blue-600 font-semibold text-sm uppercase tracking-wide">Continental Vision</span>
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mt-3 mb-6">
                        The Continental Volunteer
                        <span class="text-blue-600">Linkage Platform</span>
                    </h2>
                    <div class="w-20 h-1 bg-gradient-to-r from-blue-500 to-green-400 rounded-full mb-8"></div>
                    
                    <p class="text-lg text-gray-600 leading-relaxed mb-6">
                        The Volunteer Linkage Platform is embedded into the Continental Volunteer Linkage Platform, a platform aiming at:
                    </p>

                    <div class="space-y-6">
                        <div class="flex items-start space-x-4">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white text-sm font-bold">1</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Continental Volunteerism Standards</h4>
                                <p class="text-gray-600">Developing policies on volunteerism through a Continental Volunteerism Standards and guiding principles – to set minimum standards and common understanding for regional, national and community volunteerism in Africa.</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="w-8 h-8 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white text-sm font-bold">2</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Online Volunteer Linkage Platform</h4>
                                <p class="text-gray-600">Creating an online Volunteer linkage platform, a volunteerism mapping and knowledge management online platform as a single access point for volunteerism in Africa.</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-4">
                            <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-white text-sm font-bold">3</span>
                            </div>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2">Technical Support & Tools</h4>
                                <p class="text-gray-600">Facilitating technical support from a pool of tools on volunteerism including the Volunteer Management System (VMS) to regional, national and community-based volunteer initiatives in Africa.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="py-20 gradient-bg relative overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 w-32 h-32 border-2 border-white rounded-full"></div>
            <div class="absolute bottom-20 right-16 w-24 h-24 border-2 border-yellow-400 rounded-full"></div>
            <div class="absolute top-1/2 right-1/4 w-16 h-16 border border-white rounded-full"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-16">
                <span class="text-green-200 font-semibold text-sm uppercase tracking-wide">Our Impact</span>
                <h2 class="text-4xl md:text-5xl font-bold text-white mt-3 mb-6">
                    Creating Lasting Change Across Africa
                </h2>
                <div class="w-20 h-1 bg-yellow-400 rounded-full mx-auto"></div>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center group">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-white mb-2">50K+</div>
                    <div class="text-green-200 text-sm">People Educated</div>
                </div>
                
                <div class="text-center group">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-white mb-2">25K+</div>
                    <div class="text-green-200 text-sm">Lives Improved</div>
                </div>
                
                <div class="text-center group">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-white mb-2">15K+</div>
                    <div class="text-green-200 text-sm">Trees Planted</div>
                </div>
                
                <div class="text-center group">
                    <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-white mb-2">200+</div>
                    <div class="text-green-200 text-sm">Communities Reached</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-3xl p-12 border border-green-200">
                <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </div>
                
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">
                    Ready to Be Part of 
                    <span class="text-green-600">Africa's Future</span>?
                </h2>
                
                <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto leading-relaxed">
                    Whether you're an individual looking to volunteer or an organization seeking passionate contributors, AU-VLP is your gateway to meaningful impact across the continent.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                    <a href="{{ route('registration.volunteer.start') }}" 
                       class="bg-green-600 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-green-700 transition-all hover-scale shadow-lg">
                        Start Volunteering
                    </a>
                    <a href="{{ route('events.index') }}" 
                       class="bg-white text-green-600 border-2 border-green-600 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-green-600 hover:text-white transition-all hover-scale">
                        Browse Opportunities
                    </a>
                </div>
                
                <!-- Contact Info -->
                <div class="pt-8 border-t border-green-200">
                    <p class="text-gray-600 mb-4">Have questions? We're here to help.</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center text-sm">
                        <a href="mailto:info@au-vlp.org" class="flex items-center justify-center text-green-600 hover:text-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            info@au-vlp.org
                        </a>
                        <a href="tel:+251115530300" class="flex items-center justify-center text-green-600 hover:text-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            +251 11 553 0300
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Partnership Section -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="text-green-600 font-semibold text-sm uppercase tracking-wide">Our Partners</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-3 mb-6">
                    Collaborating for Greater Impact
                </h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-yellow-400 rounded-full mx-auto"></div>
            </div>
            
            <!-- Partner Categories -->
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center group">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Government Agencies</h3>
                    <p class="text-gray-600 text-sm">Partnering with AU member state governments to align volunteer efforts with national development goals.</p>
                </div>
                
                <div class="text-center group">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">NGOs & Civil Society</h3>
                    <p class="text-gray-600 text-sm">Collaborating with grassroots organizations to ensure volunteer efforts reach communities that need them most.</p>
                </div>
                
                <div class="text-center group">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0H8m8 0v2a2 2 0 01-2 2H10a2 2 0 01-2-2V6"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Private Sector</h3>
                    <p class="text-gray-600 text-sm">Working with businesses to create corporate volunteering programs that benefit both companies and communities.</p>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
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
    document.querySelectorAll('section').forEach((section, index) => {
        if (index > 0) { // Skip hero section
            section.style.opacity = '0';
            section.style.transform = 'translateY(30px)';
            section.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            observer.observe(section);
        }
    });

    // Staggered animation for cards
    const cards = document.querySelectorAll('.group');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        
        const cardObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });
        
        cardObserver.observe(card);
    });

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
});
</script>
@endpush
@endsection