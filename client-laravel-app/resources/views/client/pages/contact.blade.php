@extends('layouts.app')

@section('title', 'Contact Us - AU-VLP')

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
</style>
@endpush

@section('content')
<div class="font-inter">
    <!-- Hero Banner Section -->
    <section class="relative h-[50vh] min-h-[300px] max-h-[300px] overflow-hidden gradient-bg">
        <div class="absolute inset-0 bg-black/20"></div>
        
        <!-- Floating Elements -->
        <div class="absolute top-12 left-6 w-20 h-20 bg-yellow-400/20 rounded-full animate-float"></div>
        <div class="absolute bottom-16 right-8 w-16 h-16 bg-white/10 rounded-full animate-float" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/3 right-6 w-12 h-12 bg-green-400/30 rounded-full animate-float" style="animation-delay: 3s;"></div>
        
        <div class="relative z-10 h-full flex items-center justify-center">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="animate-slide-up">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-4 leading-tight">
                        Contact 
                        <span class="bg-gradient-to-r from-yellow-400 to-yellow-300 bg-clip-text text-transparent">
                            AU-VLP
                        </span>
                    </h1>
                    <p class="text-lg md:text-xl text-gray-200 mb-6 max-w-2xl mx-auto">
                        Get in touch with our team - we're here to help you make a difference
                    </p>
                    <div class="w-20 h-1 bg-yellow-400 mx-auto rounded-full"></div>
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
                <span class="text-yellow-400">Contact</span>
            </div>
        </div>
    </section>

    <!-- Contact Form & Info Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-3 gap-12">
                <!-- Contact Information -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg p-8 h-fit sticky top-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-6">Get in Touch</h3>
                        
                        <!-- Phone -->
                        <div class="flex items-start space-x-4 mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Phone</h4>
                                <p class="text-gray-600">+251 11 553 0300</p>
                                <p class="text-gray-500 text-sm">Mon - Fri, 9AM - 5PM EAT</p>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div class="flex items-start space-x-4 mb-6">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Email</h4>
                                <p class="text-gray-600">info@au-vlp.org</p>
                                <p class="text-gray-500 text-sm">We'll respond within 24 hours</p>
                            </div>
                        </div>
                        
                        <!-- Address -->
                        <div class="flex items-start space-x-4 mb-8">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 mb-1">Address</h4>
                                <p class="text-gray-600">African Union Commission</p>
                                <p class="text-gray-600">P.O. Box 3243</p>
                                <p class="text-gray-600">Addis Ababa, Ethiopia</p>
                            </div>
                        </div>
                        
                        <!-- Social Media -->
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="font-semibold text-gray-900 mb-4">Follow Us</h4>
                            <div class="flex space-x-3">
                                <a href="https://twitter.com/AfricanUnion" target="_blank" 
                                   class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white hover:bg-blue-600 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                                    </svg>
                                </a>
                                <a href="https://www.facebook.com/AfricanUnionCommission" target="_blank" 
                                   class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white hover:bg-blue-700 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                                <a href="https://www.linkedin.com/company/african-union" target="_blank" 
                                   class="w-10 h-10 bg-blue-700 rounded-lg flex items-center justify-center text-white hover:bg-blue-800 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <div class="mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">Send us a Message</h2>
                            <p class="text-gray-600">Have questions about volunteering, partnerships, or our platform? We'd love to hear from you.</p>
                        </div>
                        
                        @if(session('success'))
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ session('success') }}
                            </div>
                        </div>
                        @endif

                        @if($errors->any())
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="font-medium">Please correct the following errors:</p>
                                    <ul class="mt-1 text-sm list-disc list-inside">
                                        @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <form action="{{ route('contact.submit') }}" method="POST" class="space-y-6">
                            @csrf
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <!-- First Name -->
                                <div>
                                    <label for="first_name" class="block text-sm font-semibold text-gray-900 mb-2">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="first_name" 
                                           id="first_name"
                                           value="{{ old('first_name') }}"
                                           required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all @error('first_name') border-red-300 @enderror">
                                </div>
                                
                                <!-- Last Name -->
                                <div>
                                    <label for="last_name" class="block text-sm font-semibold text-gray-900 mb-2">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="last_name" 
                                           id="last_name"
                                           value="{{ old('last_name') }}"
                                           required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all @error('last_name') border-red-300 @enderror">
                                </div>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-gray-900 mb-2">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" 
                                           name="email" 
                                           id="email"
                                           value="{{ old('email') }}"
                                           required
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all @error('email') border-red-300 @enderror">
                                </div>
                                
                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-sm font-semibold text-gray-900 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="tel" 
                                           name="phone" 
                                           id="phone"
                                           value="{{ old('phone') }}"
                                           class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all @error('phone') border-red-300 @enderror">
                                </div>
                            </div>
                            
                            <!-- Subject -->
                            <div>
                                <label for="subject" class="block text-sm font-semibold text-gray-900 mb-2">
                                    Subject <span class="text-red-500">*</span>
                                </label>
                                <select name="subject" 
                                        id="subject"
                                        required
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all @error('subject') border-red-300 @enderror">
                                    <option value="">Select a subject</option>
                                    <option value="general_inquiry" {{ old('subject') == 'general_inquiry' ? 'selected' : '' }}>General Inquiry</option>
                                    <option value="volunteer_opportunity" {{ old('subject') == 'volunteer_opportunity' ? 'selected' : '' }}>Volunteer Opportunities</option>
                                    <option value="organization_partnership" {{ old('subject') == 'organization_partnership' ? 'selected' : '' }}>Organization Partnership</option>
                                    <option value="technical_support" {{ old('subject') == 'technical_support' ? 'selected' : '' }}>Technical Support</option>
                                    <option value="media_press" {{ old('subject') == 'media_press' ? 'selected' : '' }}>Media & Press</option>
                                    <option value="feedback" {{ old('subject') == 'feedback' ? 'selected' : '' }}>Feedback & Suggestions</option>
                                </select>
                            </div>
                            
                            <!-- Message -->
                            <div>
                                <label for="message" class="block text-sm font-semibold text-gray-900 mb-2">
                                    Message <span class="text-red-500">*</span>
                                </label>
                                <textarea name="message" 
                                          id="message"
                                          rows="6"
                                          required
                                          placeholder="Tell us more about your inquiry..."
                                          class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all resize-none @error('message') border-red-300 @enderror">{{ old('message') }}</textarea>
                            </div>
                            
                            <!-- Privacy Consent -->
                            <div class="flex items-start space-x-3">
                                <input type="checkbox" 
                                       name="privacy_consent" 
                                       id="privacy_consent"
                                       required
                                       class="mt-1 text-green-600 focus:ring-green-500 @error('privacy_consent') border-red-300 @enderror">
                                <label for="privacy_consent" class="text-sm text-gray-600">
                                    I agree to the processing of my personal data in accordance with AU-VLP's privacy policy. <span class="text-red-500">*</span>
                                </label>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button type="submit" 
                                        class="w-full bg-green-600 text-white px-8 py-4 rounded-xl font-semibold text-lg hover:bg-green-700 transition-all hover-scale shadow-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-green-600 font-semibold text-sm uppercase tracking-wide">Support</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-3 mb-6">
                    Frequently Asked Questions
                </h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-yellow-400 rounded-full mx-auto"></div>
            </div>
            
            <div class="space-y-6">
                <!-- FAQ Item 1 -->
                <div class="bg-gray-50 rounded-2xl border border-gray-200">
                    <button class="faq-toggle w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-100 transition-colors rounded-2xl">
                        <h3 class="text-lg font-semibold text-gray-900">How do I register as a volunteer on AU-VLP?</h3>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-4">
                        <p class="text-gray-600">Click on "Join" or "Register" at the top of any page, then select "Volunteer Registration." Fill out your profile with your skills, interests, and availability. Once verified, you'll have access to volunteer opportunities across Africa.</p>
                    </div>
                </div>
                
                <!-- FAQ Item 2 -->
                <div class="bg-gray-50 rounded-2xl border border-gray-200">
                    <button class="faq-toggle w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-100 transition-colors rounded-2xl">
                        <h3 class="text-lg font-semibold text-gray-900">Can organizations from outside Africa join AU-VLP?</h3>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-4">
                        <p class="text-gray-600">Yes, international organizations can partner with AU-VLP if they support African development goals and work in collaboration with local African partners. All partnerships must align with AU values and benefit African communities.</p>
                    </div>
                </div>
                
                <!-- FAQ Item 3 -->
                <div class="bg-gray-50 rounded-2xl border border-gray-200">
                    <button class="faq-toggle w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-100 transition-colors rounded-2xl">
                        <h3 class="text-lg font-semibold text-gray-900">What types of volunteer opportunities are available?</h3>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-4">
                        <p class="text-gray-600">AU-VLP offers opportunities in education, healthcare, environmental conservation, technology, youth development, women's empowerment, agriculture, and many other sectors. Opportunities range from short-term projects to long-term commitments.</p>
                    </div>
                </div>
                
                <!-- FAQ Item 4 -->
                <div class="bg-gray-50 rounded-2xl border border-gray-200">
                    <button class="faq-toggle w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-100 transition-colors rounded-2xl">
                        <h3 class="text-lg font-semibold text-gray-900">Is there any cost to join AU-VLP?</h3>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-4">
                        <p class="text-gray-600">No, registration and platform access are completely free for both volunteers and organizations. AU-VLP is funded by the African Union Commission to support continental development through volunteerism.</p>
                    </div>
                </div>
                
                <!-- FAQ Item 5 -->
                <div class="bg-gray-50 rounded-2xl border border-gray-200">
                    <button class="faq-toggle w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-100 transition-colors rounded-2xl">
                        <h3 class="text-lg font-semibold text-gray-900">How can I track my volunteer impact?</h3>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="faq-content hidden px-6 pb-4">
                        <p class="text-gray-600">Your volunteer dashboard provides detailed tracking of your activities, hours contributed, skills developed, and impact metrics. You can also download certificates and reports for your volunteer service.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Office Locations Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-green-600 font-semibold text-sm uppercase tracking-wide">Our Presence</span>
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mt-3 mb-6">
                    Connect Across Africa
                </h2>
                <div class="w-20 h-1 bg-gradient-to-r from-green-500 to-yellow-400 rounded-full mx-auto"></div>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Headquarters -->
                <div class="bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-green-600 transition-colors">Headquarters</h3>
                    <div class="space-y-2 text-gray-600">
                        <p class="font-medium">African Union Commission</p>
                        <p>Roosevelt Street</p>
                        <p>P.O. Box 3243</p>
                        <p>Addis Ababa, Ethiopia</p>
                    </div>
                </div>
                
                <!-- Regional Office East -->
                <div class="bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-blue-600 transition-colors">East Africa Hub</h3>
                    <div class="space-y-2 text-gray-600">
                        <p class="font-medium">Regional Coordination</p>
                        <p>Nairobi, Kenya</p>
                        <p>Kampala, Uganda</p>
                        <p>Kigali, Rwanda</p>
                    </div>
                </div>
                
                <!-- Regional Office West -->
                <div class="bg-white p-8 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 group">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-yellow-600 transition-colors">West Africa Hub</h3>
                    <div class="space-y-2 text-gray-600">
                        <p class="font-medium">Regional Coordination</p>
                        <p>Lagos, Nigeria</p>
                        <p>Accra, Ghana</p>
                        <p>Dakar, Senegal</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Emergency Contact Section -->
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-gradient-to-r from-red-50 to-red-100 rounded-3xl p-8 border border-red-200">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Emergency Support</h3>
                    <p class="text-gray-600 mb-6">For urgent volunteer safety issues or emergency situations in the field</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="tel:+251911234567" 
                           class="bg-red-600 text-white px-6 py-3 rounded-xl font-semibold hover:bg-red-700 transition-colors">
                            Emergency Hotline: +251 91 123 4567
                        </a>
                        <a href="mailto:emergency@au-vlp.org" 
                           class="border-2 border-red-600 text-red-600 px-6 py-3 rounded-xl font-semibold hover:bg-red-600 hover:text-white transition-colors">
                            emergency@au-vlp.org
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // FAQ Toggle functionality
    const faqToggles = document.querySelectorAll('.faq-toggle');
    
    faqToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const icon = this.querySelector('svg');
            
            // Close all other FAQ items
            faqToggles.forEach(otherToggle => {
                if (otherToggle !== this) {
                    const otherContent = otherToggle.nextElementSibling;
                    const otherIcon = otherToggle.querySelector('svg');
                    
                    otherContent.classList.add('hidden');
                    otherIcon.style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle current item
            content.classList.toggle('hidden');
            
            if (content.classList.contains('hidden')) {
                icon.style.transform = 'rotate(0deg)';
            } else {
                icon.style.transform = 'rotate(180deg)';
            }
        });
    });

    // Form validation and enhancement
    const form = document.querySelector('form');
    const submitButton = form?.querySelector('button[type="submit"]');
    
    if (form && submitButton) {
        form.addEventListener('submit', function(e) {
            // Add loading state
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sending...
            `;
            
            // Re-enable after 5 seconds to prevent permanent disable on validation errors
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = `
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Send Message
                `;
            }, 5000);
        });
    }

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

    // Form field focus effects
    const formInputs = document.querySelectorAll('input, select, textarea');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.querySelector('label')?.classList.add('text-green-600');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.querySelector('label')?.classList.remove('text-green-600');
            }
        });
    });
});
</script>
@endpush
@endsection