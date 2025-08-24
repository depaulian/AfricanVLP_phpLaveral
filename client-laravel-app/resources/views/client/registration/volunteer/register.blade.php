<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'AU VLP') }} - Join AU-VLP</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .font-inter { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #0F5132 0%, #16a34a 100%); }
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .input-focus {
            @apply transition-all duration-300;
        }
        .input-focus:focus {
            @apply transform -translate-y-0.5 shadow-md shadow-green-500/15;
        }
        .step-content {
            transition: all 0.5s ease-in-out;
            opacity: 0;
            transform: translateX(30px);
            height: 0;
            overflow: hidden;
        }
        .step-content.active {
            opacity: 1;
            transform: translateX(0);
            height: auto;
        }
        .progress-line {
            transition: all 0.5s ease-in-out;
        }
        .step-indicator {
            transition: all 0.3s ease;
        }
        .step-indicator.active {
            @apply bg-green-600 transform scale-110;
        }
        .step-indicator.completed {
            @apply bg-green-600;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        #form-container {
            position: relative;
            min-height: 800px;
        }
        .form-step {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            visibility: hidden;
            opacity: 0;
            transform: translateX(30px);
            transition: all 0.5s ease-in-out;
        }
        .form-step.active {
            visibility: visible;
            opacity: 1;
            transform: translateX(0);
        }
    </style>
</head>
<body class="font-inter antialiased bg-gray-50 min-h-screen">
    <!-- Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-32 h-32 bg-green-100 rounded-full animate-float opacity-50"></div>
        <div class="absolute bottom-32 right-16 w-24 h-24 bg-yellow-100 rounded-full animate-float opacity-50" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 right-8 w-20 h-20 bg-green-200 rounded-full animate-float opacity-50" style="animation-delay: 4s;"></div>
    </div>

    <div class="min-h-screen relative z-10">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="max-w-6xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h1 class="text-2xl font-bold">
                        <img src="{{ asset('img/au-logo.svg') }}" alt="AU Logo" class="h-10">
                    </h1>
                    <span class="text-gray-500">|</span>
                    <span class="text-gray-700 font-medium">Volunteer Registration</span>
                </div>
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="max-w-4xl mx-auto">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <div class="step-indicator active w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-white text-sm font-semibold mr-2">
                            1
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Basic Information</p>
                            <p class="text-xs text-gray-500">Personal details and account setup</p>
                        </div>
                    </div>

                    <div class="flex-1 mx-4">
                        <div class="progress-line w-full bg-gray-200 rounded-full h-1">
                            <div class="bg-green-600 h-1 rounded-full transition-all duration-500" style="width: 33%"></div>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="step-indicator w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-white text-sm font-semibold mr-2">
                            2
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-600">Profile Details</p>
                            <p class="text-xs text-gray-500">Additional information about you</p>
                        </div>
                    </div>

                    <div class="flex-1 mx-4">
                        <div class="progress-line w-full bg-gray-200 rounded-full h-1"></div>
                    </div>

                    <div class="flex items-center">
                        <div class="step-indicator w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-white text-sm font-semibold mr-2">
                            3
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-600">Interests & Preferences</p>
                            <p class="text-xs text-gray-500">Choose your volunteer areas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 px-6 py-4">
            <div class="max-w-6xl mx-auto">
                <div class="grid lg:grid-cols-3 gap-4">
                    <!-- Left Sidebar - Summary/Info -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-sm p-4 sticky top-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Join Africa Union Volunteering Program</h3>
                            <div class="space-y-2">
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Connect with 2,500+ volunteers
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Access opportunities in 55 countries
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Earn certificates and recognition
                                </div>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Build professional skills
                                </div>
                            </div>

                            <!-- Progress Summary -->
                            <div class="mt-4 pt-2 border-t border-gray-200">
                                <h4 class="text-sm font-semibold text-gray-900 mb-1">Registration Progress</h4>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">Progress</span>
                                        <span class="text-gray-900 font-medium" id="progress-text">33%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-green-600 h-1.5 rounded-full transition-all duration-500" id="sidebar-progress" style="width: 33%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Content - Form -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-sm">
                            <!-- Form -->
                            <form id="registration-form" method="POST" action="{{ route('registration.volunteer.register') ?? '/register' }}" enctype="multipart/form-data">
                                @csrf
                                
                                <div id="form-container">
                                    <!-- Step 1: Basic Information -->
                                    <div class="form-step p-4 active" id="step-1">
                                        <div class="flex items-center mb-2">
                                            <button type="button" id="back-btn" class="mr-2 p-1 text-gray-400 hover:text-gray-600 transition-colors" style="display: none;">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                            </button>
                                            <h2 class="text-2xl font-bold text-gray-900">Basic Information</h2>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name*</label>
                                                    <input type="text" name="first_name" required value="{{ old('first_name') }}"
                                                           class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Enter your first name">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name*</label>
                                                    <input type="text" name="last_name" required value="{{ old('last_name') }}"
                                                           class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Enter your last name">
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address*</label>
                                                <input type="email" name="email" required value="{{ old('email') }}"
                                                       class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                       placeholder="Enter your email address">
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password*</label>
                                                    <input type="password" name="password" required
                                                           class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Create a password">
                                                    <p class="text-xs text-gray-500 mt-1">At least 8 characters</p>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password*</label>
                                                    <input type="password" name="password_confirmation" required
                                                           class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Confirm your password">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 2: Profile Details -->
                                    <div class="form-step p-4" id="step-2">
                                        <div class="flex items-center mb-2">
                                            <button type="button" id="back-btn-2" class="mr-2 p-1 text-gray-400 hover:text-gray-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                            </button>
                                            <h2 class="text-2xl font-bold text-gray-900">Profile Details</h2>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                                                           class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                                    <select name="gender"
                                                            class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                        <option value="">Select Gender</option>
                                                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                                    <input type="tel" name="phone_number" value="{{ old('phone_number') }}"
                                                           class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Enter your phone number">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Language</label>
                                                    <select name="preferred_language"
                                                            class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                        <option value="">Select Language</option>
                                                        @foreach($languages as $language)
                                                            <option value="{{ $language }}" {{ old('preferred_language') == $language ? 'selected' : '' }}>{{ $language }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                                    <select name="country_id" id="country_id"
                                                            class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                        <option value="">Select Country</option>
                                                        @foreach($countries as $country)
                                                            <option value="{{ $country->id }}" data-code="{{ $country->code }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                                    <select name="city_id" id="city_id"
                                                            class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                        <option value="">Select City</option>
                                                        <!-- Cities will be populated via JavaScript based on country selection -->
                                                    </select>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                                <textarea name="address" rows="2"
                                                          class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                          placeholder="Enter your full address">{{ old('address') }}</textarea>
                                            </div>

                                            <!-- CV Upload Section -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">CV/Resume (Optional)</label>
                                                <input type="file" name="cv" accept=".pdf,.doc,.docx"
                                                       class="input-focus block w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                <p class="text-xs text-gray-500 mt-1">PDF, DOC, or DOCX files only. Max size: 5MB</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Step 3: Interests & Preferences -->
                                    <div class="form-step p-4" id="step-3">
                                        <div class="flex items-center mb-2">
                                            <button type="button" id="back-btn-3" class="mr-2 p-1 text-gray-400 hover:text-gray-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                                </svg>
                                            </button>
                                            <h2 class="text-2xl font-bold text-gray-900">Interests & Preferences</h2>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Volunteering Interests -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">What interests you? (Select all that apply)</label>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 max-h-60 overflow-y-auto">
                                                    @foreach($volunteeringCategories as $category)
                                                    <label class="flex items-start p-2 border border-gray-200 rounded-lg hover:border-green-500 transition-colors cursor-pointer">
                                                        <input type="checkbox" name="volunteering_interests[]" value="{{ $category->id }}" 
                                                               {{ in_array($category->id, old('volunteering_interests', [])) ? 'checked' : '' }}
                                                               class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-3 mt-1 flex-shrink-0">
                                                        <div>
                                                            <span class="text-sm font-medium text-gray-900">{{ $category->name }}</span>
                                                            @if($category->description)
                                                                <p class="text-xs text-gray-500 mt-1">{{ Str::limit($category->description, 100) }}</p>
                                                            @endif
                                                        </div>
                                                    </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                            
                                            <!-- Volunteer Mode -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Mode of Volunteering</label>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                    @foreach($volunteer_modes as $mode)
                                                    <label class="flex items-center p-2 border border-gray-200 rounded-lg hover:border-green-500 transition-colors cursor-pointer">
                                                        <input type="radio" name="volunteer_mode" value="{{ $mode }}" 
                                                               {{ old('volunteer_mode') == $mode ? 'checked' : '' }}
                                                               class="text-green-600 focus:ring-green-500 mr-3">
                                                        <span class="text-sm text-gray-700">
                                                            @if($mode === 'Virtual')
                                                                Virtual (Online)
                                                            @elseif($mode === 'Physical')
                                                                In-Person
                                                            @else
                                                                {{ $mode }}
                                                            @endif
                                                        </span>
                                                    </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <!-- Organization Category Preferences -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Organization Preferences (Optional)</label>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 max-h-40 overflow-y-auto">
                                                    @foreach($organisation_categories as $category)
                                                    <label class="flex items-start p-2 border border-gray-200 rounded-lg hover:border-green-500 transition-colors cursor-pointer">
                                                        <input type="checkbox" name="organization_interests[]" value="{{ $category->id }}" 
                                                               {{ in_array($category->id, old('organization_interests', [])) ? 'checked' : '' }}
                                                               class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-3 mt-1 flex-shrink-0">
                                                        <div>
                                                            <span class="text-sm font-medium text-gray-900">{{ $category->name }}</span>
                                                        </div>
                                                    </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <!-- Time Commitment -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Time Commitment</label>
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                                    @foreach($time_commitments as $commitment)
                                                    <label class="flex items-center p-2 border border-gray-200 rounded-lg hover:border-green-500 transition-colors cursor-pointer">
                                                        <input type="radio" name="time_commitment" value="{{ $commitment }}" 
                                                               {{ old('time_commitment') == $commitment ? 'checked' : '' }}
                                                               class="text-green-600 focus:ring-green-500 mr-2">
                                                        <span class="text-sm text-gray-700">{{ $commitment }}</span>
                                                    </label>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <!-- Terms -->
                                            <div class="pt-4 border-t border-gray-200">
                                                <label class="flex items-start">
                                                    <input type="checkbox" name="terms_accepted" required class="mt-1 rounded border-gray-300 text-green-600 focus:ring-green-500">
                                                    <span class="ml-3 text-sm text-gray-600">
                                                        I agree to the <a href="#" class="text-green-600 hover:text-green-700 underline">Terms of Service</a> 
                                                        and <a href="#" class="text-green-600 hover:text-green-700 underline">Privacy Policy</a>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Footer Actions -->
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 rounded-b-xl flex justify-between items-center">
                                <div class="text-sm text-gray-500">
                                    <span id="step-info">Step 1 of 3</span>
                                </div>
                                
                                <div class="flex space-x-3">
                                    <button type="button" id="prev-btn" 
                                            class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium transition-colors" 
                                            style="display: none;">
                                        Previous
                                    </button>
                                    
                                    <button type="button" id="next-btn" 
                                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                                        Continue
                                    </button>
                                    
                                    <button type="submit" id="submit-btn" 
                                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors"
                                            style="display: none;">
                                        Complete Registration
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Login Link -->
                        <div class="mt-6 text-center text-sm text-gray-600">
                            Already have an account? 
                            <a href="{{ route('login') }}" class="text-green-600 hover:text-green-700 font-medium">
                                Sign in here
                            </a>
        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let currentStep = 1;
            const totalSteps = 3;

            // Get DOM elements
            const formSteps = document.querySelectorAll('.form-step');
            const stepIndicators = document.querySelectorAll('.step-indicator');
            const progressLines = document.querySelectorAll('.progress-line div');
            const sidebarProgress = document.getElementById('sidebar-progress');
            const progressText = document.getElementById('progress-text');
            const stepInfo = document.getElementById('step-info');
            
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const submitBtn = document.getElementById('submit-btn');
            const backBtns = document.querySelectorAll('[id^="back-btn"]');
            const form = document.getElementById('registration-form');
            const formContainer = document.getElementById('form-container');

            // Country/City functionality
            const countrySelect = document.getElementById('country_id');
            const citySelect = document.getElementById('city_id');

            if (countrySelect && citySelect) {
                countrySelect.addEventListener('change', function() {
                    const countryId = this.value;
                    citySelect.innerHTML = '<option value="">Select City</option>';
                    
                    if (countryId) {
                        // Show loading
                        citySelect.innerHTML = '<option value="">Loading cities...</option>';
                        
                        // Fetch cities based on country
                        fetch(`/api/countries/${countryId}/cities`)
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(cities => {
                                citySelect.innerHTML = '<option value="">Select City</option>';
                                cities.forEach(city => {
                                    const option = document.createElement('option');
                                    option.value = city.id;
                                    option.textContent = city.name;
                                    citySelect.appendChild(option);
                                });
                            })
                            .catch(error => {
                                console.error('Error fetching cities:', error);
                                citySelect.innerHTML = '<option value="">Select City</option><option value="">Error loading cities</option>';
                            });
                    }
                });
            }

            // Update UI based on current step
            function updateUI() {
                // Update form steps
                formSteps.forEach((step, index) => {
                    const stepNum = index + 1;
                    step.classList.remove('active');
                    if (stepNum === currentStep) {
                        setTimeout(() => {
                            step.classList.add('active');
                        }, 10);
                    }
                });

                // Update step indicators and progress
                stepIndicators.forEach((indicator, index) => {
                    const stepNum = index + 1;
                    indicator.classList.remove('active', 'completed');
                    
                    if (stepNum === currentStep) {
                        indicator.classList.add('active');
                    } else if (stepNum < currentStep) {
                        indicator.classList.add('completed');
                        indicator.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                    } else {
                        indicator.textContent = stepNum;
                    }
                });

                // Update progress bars
                const progress = (currentStep / totalSteps) * 100;
                progressLines.forEach((line, index) => {
                    if (index === 0) {
                        line.style.width = Math.min(progress, 50) + '%';
                    } else {
                        line.style.width = Math.max(0, progress - 50) + '%';
                    }
                });
                
                sidebarProgress.style.width = progress + '%';
                progressText.textContent = Math.round(progress) + '%';
                stepInfo.textContent = `Step ${currentStep} of ${totalSteps}`;

                // Update navigation buttons
                prevBtn.style.display = currentStep > 1 ? 'block' : 'none';
                nextBtn.style.display = currentStep < totalSteps ? 'block' : 'none';
                submitBtn.style.display = currentStep === totalSteps ? 'block' : 'none';
            }

            // Validate current step
            function validateCurrentStep() {
                const currentStepEl = document.getElementById(`step-${currentStep}`);
                const requiredInputs = currentStepEl.querySelectorAll('[required]');
                
                for (let input of requiredInputs) {
                    if (!input.value.trim()) {
                        input.focus();
                        showError('Please fill in all required fields');
                        return false;
                    }
                }

                // Additional validations for step 1
                if (currentStep === 1) {
                    const email = currentStepEl.querySelector('[name="email"]').value;
                    const password = currentStepEl.querySelector('[name="password"]').value;
                    const confirmPassword = currentStepEl.querySelector('[name="password_confirmation"]').value;
                    
                    if (!isValidEmail(email)) {
                        showError('Please enter a valid email address');
                        return false;
                    }
                    
                    if (password !== confirmPassword) {
                        showError('Passwords do not match');
                        return false;
                    }
                    
                    if (password.length < 8) {
                        showError('Password must be at least 8 characters long');
                        return false;
                    }
                }

                // Validation for step 3
                if (currentStep === 3) {
                    const volunteerMode = currentStepEl.querySelector('[name="volunteer_mode"]:checked');
                    const timeCommitment = currentStepEl.querySelector('[name="time_commitment"]:checked');
                    
                    if (!volunteerMode) {
                        showError('Please select your preferred volunteer mode');
                        return false;
                    }
                    
                    if (!timeCommitment) {
                        showError('Please select your time commitment preference');
                        return false;
                    }
                }

                return true;
            }

            // Email validation
            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            // Show error message
            function showError(message) {
                // Remove existing error
                const existingError = document.querySelector('.error-message');
                if (existingError) {
                    existingError.remove();
                }

                // Create new error message
                const errorEl = document.createElement('div');
                errorEl.className = 'error-message bg-red-50 border border-red-200 rounded-lg p-3 mb-4 fade-in';
                errorEl.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-red-800">${message}</p>
                    </div>
                `;
                
                const currentStepEl = document.getElementById(`step-${currentStep}`);
                currentStepEl.insertBefore(errorEl, currentStepEl.firstChild.nextSibling);
                
                setTimeout(() => {
                    if (errorEl && errorEl.parentNode) {
                        errorEl.remove();
                    }
                }, 5000);
            }

            // Show success message
            function showSuccess(message) {
                const successEl = document.createElement('div');
                successEl.className = 'success-message bg-green-50 border border-green-200 rounded-lg p-3 mb-4 fade-in';
                successEl.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-medium text-green-800">${message}</p>
                    </div>
                `;
                
                const currentStepEl = document.getElementById(`step-${currentStep}`);
                currentStepEl.insertBefore(successEl, currentStepEl.firstChild.nextSibling);
            }

            // Navigation event listeners
            nextBtn.addEventListener('click', function() {
                if (validateCurrentStep()) {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        updateUI();
                        saveProgress();
                    }
                }
            });

            prevBtn.addEventListener('click', function() {
                if (currentStep > 1) {
                    currentStep--;
                    updateUI();
                }
            });

            // Back button event listeners
            backBtns.forEach((btn, index) => {
                btn.addEventListener('click', function() {
                    if (currentStep > 1) {
                        currentStep--;
                        updateUI();
                    }
                });
            });

            // Auto-save functionality (basic client-side storage)
            function saveProgress() {
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    if (data[key]) {
                        if (Array.isArray(data[key])) {
                            data[key].push(value);
                        } else {
                            data[key] = [data[key], value];
                        }
                    } else {
                        data[key] = value;
                    }
                }
                
                try {
                    // Store in sessionStorage for the session
                    sessionStorage.setItem('registration_progress', JSON.stringify({
                        step: currentStep,
                        data: data,
                        timestamp: new Date().toISOString()
                    }));
                } catch (e) {
                    console.warn('Could not save progress:', e);
                }
            }

            // Load saved progress
            function loadProgress() {
                try {
                    const saved = sessionStorage.getItem('registration_progress');
                    if (saved) {
                        const progress = JSON.parse(saved);
                        
                        // Fill form with saved data (excluding passwords for security)
                        Object.entries(progress.data).forEach(([name, value]) => {
                            if (name.includes('password')) return; // Skip passwords
                            
                            const input = form.querySelector(`[name="${name}"]`);
                            if (input) {
                                if (input.type === 'checkbox' || input.type === 'radio') {
                                    if (Array.isArray(value)) {
                                        value.forEach(v => {
                                            const specificInput = form.querySelector(`[name="${name}"][value="${v}"]`);
                                            if (specificInput) specificInput.checked = true;
                                        });
                                    } else {
                                        const specificInput = form.querySelector(`[name="${name}"][value="${value}"]`);
                                        if (specificInput) specificInput.checked = true;
                                    }
                                } else {
                                    input.value = value;
                                }
                            }
                        });
                        
                        // Go to saved step
                        if (progress.step > 1) {
                            currentStep = progress.step;
                            updateUI();
                        }
                    }
                } catch (e) {
                    console.warn('Could not load progress:', e);
                }
            }

            // Submit button click handler
            submitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                handleFormSubmission();
            });

            // Form submission handler
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                handleFormSubmission();
            });

            // Unified form submission function
            function handleFormSubmission() {
                console.log('Form submission started');
                
                if (!validateCurrentStep()) {
                    console.log('Validation failed');
                    return;
                }

                // Show loading state
                submitBtn.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating Account...
                `;
                submitBtn.disabled = true;

                console.log('Preparing form data...');
                
                // Prepare form data
                const formData = new FormData(form);
                
                // Log form data for debugging
                console.log('Form data prepared:');
                for (let [key, value] of formData.entries()) {
                    console.log(key, value);
                }
                
                console.log('Submitting to:', form.action);
                
                // Submit to backend
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(response => {
                    console.log('Response received:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    
                    if (data.success) {
                        try {
                            sessionStorage.removeItem('registration_progress');
                        } catch (e) {
                            console.warn('Could not clear saved progress:', e);
                        }
                        showSuccess(data.message || 'Registration successful!');
                        
                        setTimeout(() => {
                            const redirectUrl = data.redirect_url || '/dashboard';
                            console.log('Redirecting to:', redirectUrl);
                            window.location.href = redirectUrl;
                        }, 2000);
                    } else {
                        console.log('Registration failed:', data);
                        
                        if (data.errors) {
                            let errorMessages = [];
                            Object.values(data.errors).forEach(errorArray => {
                                if (Array.isArray(errorArray)) {
                                    errorMessages = errorMessages.concat(errorArray);
                                } else {
                                    errorMessages.push(errorArray);
                                }
                            });
                            showError(errorMessages[0] || 'Registration failed. Please check your input.');
                        } else {
                            showError(data.message || 'Registration failed. Please try again.');
                        }
                        
                        resetSubmitButton();
                    }
                })
                .catch(error => {
                    console.error('Registration error:', error);
                    showError('Network error. Please check your connection and try again.');
                    resetSubmitButton();
                });
            }

            // Reset submit button to original state
            function resetSubmitButton() {
                submitBtn.innerHTML = 'Complete Registration';
                submitBtn.disabled = false;
            }

            // Real-time email validation
            const emailInput = form.querySelector('[name="email"]');
            if (emailInput) {
                let emailCheckTimeout;
                emailInput.addEventListener('input', function() {
                    clearTimeout(emailCheckTimeout);
                    const email = this.value.trim();
                    
                    if (email && isValidEmail(email)) {
                        emailCheckTimeout = setTimeout(() => {
                            checkEmailAvailability(email);
                        }, 500);
                    }
                });
            }

            // Check email availability
            function checkEmailAvailability(email) {
                fetch('/register/check-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email: email })
                })
                .then(response => response.json())
                .then(data => {
                    const emailInput = form.querySelector('[name="email"]');
                    const parent = emailInput.parentElement;
                    
                    // Remove existing feedback
                    const existingFeedback = parent.querySelector('.email-feedback');
                    if (existingFeedback) {
                        existingFeedback.remove();
                    }
                    
                    // Add new feedback
                    const feedback = document.createElement('p');
                    feedback.className = 'email-feedback text-xs mt-1';
                    
                    if (data.available) {
                        feedback.className += ' text-green-600';
                        feedback.innerHTML = '✓ Email is available';
                    } else {
                        feedback.className += ' text-red-600';
                        feedback.innerHTML = '✗ This email is already registered';
                    }
                    
                    parent.appendChild(feedback);
                })
                .catch(error => {
                    console.error('Email check error:', error);
                });
            }

            // Auto-save on input change
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('change', () => {
                    clearTimeout(window.autoSaveTimeout);
                    window.autoSaveTimeout = setTimeout(saveProgress, 1000);
                });
            });

            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && e.ctrlKey) {
                    e.preventDefault();
                    if (currentStep < totalSteps) {
                        nextBtn.click();
                    } else {
                        submitBtn.click();
                    }
                }
            });

            // Initialize
            loadProgress();
            updateUI();
            
            // Debug information
            console.log('Registration form initialized');
            console.log('Form action:', form.action);
            console.log('CSRF token:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'));
            console.log('Submit button:', submitBtn);
            console.log('Current step:', currentStep);
        });
    </script>
</body>
</html>