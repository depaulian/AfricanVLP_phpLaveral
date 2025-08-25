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
        .field-error {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
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
                                    <!-- Global Error Display -->
                                    @if($errors->any())
                                        <div class="error-message bg-red-50 border border-red-200 rounded-lg p-4 mb-4 mx-4 mt-4 fade-in">
                                            <div class="flex items-start">
                                                <svg class="w-5 h-5 text-red-600 mr-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <div class="flex-1">
                                                    <h3 class="text-sm font-medium text-red-800 mb-2">Please correct the following errors:</h3>
                                                    <ul class="text-sm text-red-700 space-y-1">
                                                        @foreach($errors->all() as $error)
                                                            <li class="flex items-start">
                                                                <span class="mr-2">•</span>
                                                                <span>{{ $error }}</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if(session('success'))
                                        <div class="success-message bg-green-50 border border-green-200 rounded-lg p-4 mb-4 mx-4 mt-4 fade-in">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    @if(session('error'))
                                        <div class="error-message bg-red-50 border border-red-200 rounded-lg p-4 mb-4 mx-4 mt-4 fade-in">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                                            </div>
                                        </div>
                                    @endif

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
                                                           class="input-focus block w-full px-3 py-3 border {{ $errors->has('first_name') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Enter your first name">
                                                    @error('first_name')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name*</label>
                                                    <input type="text" name="last_name" required value="{{ old('last_name') }}"
                                                           class="input-focus block w-full px-3 py-3 border {{ $errors->has('last_name') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Enter your last name">
                                                    @error('last_name')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address*</label>
                                                <input type="email" name="email" required value="{{ old('email') }}"
                                                       class="input-focus block w-full px-3 py-3 border {{ $errors->has('email') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                       placeholder="Enter your email address">
                                                @error('email')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password*</label>
                                                    <input type="password" name="password" required
                                                           class="input-focus block w-full px-3 py-3 border {{ $errors->has('password') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Create a password">
                                                    <p class="text-xs text-gray-500 mt-1">At least 8 characters with uppercase, lowercase, and numbers</p>
                                                    @error('password')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password*</label>
                                                    <input type="password" name="password_confirmation" required
                                                           class="input-focus block w-full px-3 py-3 border {{ $errors->has('password_confirmation') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Confirm your password">
                                                    @error('password_confirmation')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
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
                                                           class="input-focus block w-full px-3 py-3 border {{ $errors->has('date_of_birth') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                    @error('date_of_birth')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                                                    <select name="gender"
                                                            class="input-focus block w-full px-3 py-3 border {{ $errors->has('gender') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                        <option value="">Select Gender</option>
                                                        <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                                        <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                                                        <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                                                    </select>
                                                    @error('gender')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                                    <input type="tel" name="phone_number" value="{{ old('phone_number') }}"
                                                           class="input-focus block w-full px-3 py-3 border {{ $errors->has('phone_number') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                           placeholder="Enter your phone number">
                                                    @error('phone_number')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Language</label>
                                                    <select name="preferred_language"
                                                            class="input-focus block w-full px-3 py-3 border {{ $errors->has('preferred_language') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                        <option value="">Select Language</option>
                                                        @foreach($languages as $language)
                                                            <option value="{{ $language }}" {{ old('preferred_language') == $language ? 'selected' : '' }}>{{ $language }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('preferred_language')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                                    <select name="country_id" id="country_id"
                                                            class="input-focus block w-full px-3 py-3 border {{ $errors->has('country_id') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                        <option value="">Select Country</option>
                                                        @foreach($countries as $country)
                                                            <option value="{{ $country->id }}" data-code="{{ $country->code }}" {{ old('country_id') == $country->id ? 'selected' : '' }}>{{ $country->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('country_id')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                                    <select name="city_id" id="city_id"
                                                            class="input-focus block w-full px-3 py-3 border {{ $errors->has('city_id') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                        <option value="">Select City</option>
                                                        <!-- Cities will be populated via JavaScript based on country selection -->
                                                    </select>
                                                    @error('city_id')
                                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                                <textarea name="address" rows="2"
                                                          class="input-focus block w-full px-3 py-3 border {{ $errors->has('address') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                                          placeholder="Enter your full address">{{ old('address') }}</textarea>
                                                @error('address')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <!-- CV Upload Section -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">CV/Resume (Optional)</label>
                                                <input type="file" name="cv" accept=".pdf,.doc,.docx"
                                                       class="input-focus block w-full px-3 py-3 border {{ $errors->has('cv') ? 'border-red-500' : 'border-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                                <p class="text-xs text-gray-500 mt-1">PDF, DOC, or DOCX files only. Max size: 5MB</p>
                                                @error('cv')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
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
                                                    <label class="flex items-start p-2 border {{ $errors->has('volunteering_interests') ? 'border-red-500' : 'border-gray-200' }} rounded-lg hover:border-green-500 transition-colors cursor-pointer">
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
                                                @error('volunteering_interests')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>
                                            
                                            <!-- Volunteer Mode -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Mode of Volunteering</label>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                                    @foreach($volunteer_modes as $mode)
                                                    <label class="flex items-center p-2 border {{ $errors->has('volunteer_mode') ? 'border-red-500' : 'border-gray-200' }} rounded-lg hover:border-green-500 transition-colors cursor-pointer">
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
                                                @error('volunteer_mode')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <!-- Organization Category Preferences -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Organization Preferences (Optional)</label>
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 max-h-40 overflow-y-auto">
                                                    @foreach($organisation_categories as $category)
                                                    <label class="flex items-start p-2 border {{ $errors->has('organization_interests') ? 'border-red-500' : 'border-gray-200' }} rounded-lg hover:border-green-500 transition-colors cursor-pointer">
                                                        <input type="checkbox" name="organization_interests[]" value="{{ $category->id }}" 
                                                               {{ in_array($category->id, old('organization_interests', [])) ? 'checked' : '' }}
                                                               class="rounded border-gray-300 text-green-600 focus:ring-green-500 mr-3 mt-1 flex-shrink-0">
                                                        <div>
                                                            <span class="text-sm font-medium text-gray-900">{{ $category->name }}</span>
                                                        </div>
                                                    </label>
                                                    @endforeach
                                                </div>
                                                @error('organization_interests')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <!-- Time Commitment -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Time Commitment</label>
                                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                                    @foreach($time_commitments as $commitment)
                                                    <label class="flex items-center p-2 border {{ $errors->has('time_commitment') ? 'border-red-500' : 'border-gray-200' }} rounded-lg hover:border-green-500 transition-colors cursor-pointer">
                                                        <input type="radio" name="time_commitment" value="{{ $commitment }}" 
                                                               {{ old('time_commitment') == $commitment ? 'checked' : '' }}
                                                               class="text-green-600 focus:ring-green-500 mr-2">
                                                        <span class="text-sm text-gray-700">{{ $commitment }}</span>
                                                    </label>
                                                    @endforeach
                                                </div>
                                                @error('time_commitment')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <!-- Terms -->
                                            <div class="pt-4 border-t border-gray-200">
                                                <label class="flex items-start">
                                                    <input type="checkbox" name="terms_accepted" required 
                                                           class="mt-1 rounded border-gray-300 text-green-600 focus:ring-green-500 {{ $errors->has('terms_accepted') ? 'border-red-500' : '' }}">
                                                    <span class="ml-3 text-sm text-gray-600">
                                                        I agree to the <a href="#" class="text-green-600 hover:text-green-700 underline">Terms of Service</a> 
                                                        and <a href="#" class="text-green-600 hover:text-green-700 underline">Privacy Policy</a>
                                                    </span>
                                                </label>
                                                @error('terms_accepted')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
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

    // Define constants from backend (you may need to expose these via Blade or API)
    const LANGUAGES = {!! json_encode(\App\Models\User::LANGUAGES) !!}; // Expose via Blade
    const VOLUNTEER_MODES = {!! json_encode(\App\Models\User::VOLUNTEER_MODES) !!};
    const TIME_COMMITMENTS = {!! json_encode(\App\Models\User::TIME_COMMITMENTS) !!};
    const COMMON_PASSWORDS = ['password', '12345678', 'qwerty123', 'abc123456', 'password123', '123456789', 'welcome123'];

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

    // Country/City functionality
    const countrySelect = document.getElementById('country_id');
    const citySelect = document.getElementById('city_id');

    if (countrySelect && citySelect) {
        countrySelect.addEventListener('change', function() {
            const countryId = this.value;
            citySelect.innerHTML = '<option value="">Select City</option>';
            
            if (countryId) {
                citySelect.innerHTML = '<option value="">Loading cities...</option>';
                
                fetch(`/api/countries/${countryId}/cities`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(cities => {
                        citySelect.innerHTML = '<option value="">Select City</option>';
                        cities.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.name;
                            option.setAttribute('data-country-id', city.country_id);
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
        formSteps.forEach((step, index) => {
            const stepNum = index + 1;
            step.classList.remove('active');
            if (stepNum === currentStep) {
                setTimeout(() => {
                    step.classList.add('active');
                }, 10);
            }
        });

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

        prevBtn.style.display = currentStep > 1 ? 'block' : 'none';
        nextBtn.style.display = currentStep < totalSteps ? 'block' : 'none';
        submitBtn.style.display = currentStep === totalSteps ? 'block' : 'none';
    }

    // Validate current step
    function validateCurrentStep() {
        const currentStepEl = document.getElementById(`step-${currentStep}`);
        const requiredInputs = currentStepEl.querySelectorAll('[required]');
        let errors = [];

        // Clear existing field errors
        clearFieldErrors(currentStepEl);

        // Step 1: Basic Information
        if (currentStep === 1) {
            const firstName = currentStepEl.querySelector('[name="first_name"]');
            const lastName = currentStepEl.querySelector('[name="last_name"]');
            const email = currentStepEl.querySelector('[name="email"]');
            const password = currentStepEl.querySelector('[name="password"]');
            const passwordConfirmation = currentStepEl.querySelector('[name="password_confirmation"]');

            // First Name
            if (!firstName.value.trim()) {
                errors.push({ field: firstName, message: 'First name is required.' });
            } else if (firstName.value.length > 45) {
                errors.push({ field: firstName, message: 'First name must not exceed 45 characters.' });
            }

            // Last Name
            if (!lastName.value.trim()) {
                errors.push({ field: lastName, message: 'Last name is required.' });
            } else if (lastName.value.length > 45) {
                errors.push({ field: lastName, message: 'Last name must not exceed 45 characters.' });
            }

            // Email
            if (!email.value.trim()) {
                errors.push({ field: email, message: 'Email address is required.' });
            } else if (!isValidEmail(email.value)) {
                errors.push({ field: email, message: 'Please enter a valid email address.' });
            } else if (email.value.length > 100) {
                errors.push({ field: email, message: 'Email address must not exceed 100 characters.' });
            }

            // Password
            if (!password.value) {
                errors.push({ field: password, message: 'Password is required.' });
            } else {
                if (password.value.length < 8) {
                    errors.push({ field: password, message: 'Password must be at least 8 characters long.' });
                }
                if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/.test(password.value)) {
                    errors.push({ field: password, message: 'Password must contain at least one uppercase letter, one lowercase letter, and one number.' });
                }
                if (COMMON_PASSWORDS.includes(password.value.toLowerCase())) {
                    errors.push({ field: password, message: 'This password is too common. Please choose a more secure password.' });
                }
                if (firstName.value && password.value.toLowerCase().includes(firstName.value.toLowerCase())) {
                    errors.push({ field: password, message: 'Password should not contain your first name.' });
                }
                if (lastName.value && password.value.toLowerCase().includes(lastName.value.toLowerCase())) {
                    errors.push({ field: password, message: 'Password should not contain your last name.' });
                }
                if (email.value && password.value.toLowerCase().includes(email.value.split('@')[0].toLowerCase())) {
                    errors.push({ field: password, message: 'Password should not contain parts of your email address.' });
                }
            }

            // Password Confirmation
            if (!passwordConfirmation.value) {
                errors.push({ field: passwordConfirmation, message: 'Password confirmation is required.' });
            } else if (password.value !== passwordConfirmation.value) {
                errors.push({ field: passwordConfirmation, message: 'Password confirmation does not match.' });
            }
        }

        // Step 2: Profile Details
        if (currentStep === 2) {
            const phoneNumber = currentStepEl.querySelector('[name="phone_number"]');
            const address = currentStepEl.querySelector('[name="address"]');
            const cityId = currentStepEl.querySelector('[name="city_id"]');
            const countryId = currentStepEl.querySelector('[name="country_id"]');
            const dateOfBirth = currentStepEl.querySelector('[name="date_of_birth"]');
            const gender = currentStepEl.querySelector('[name="gender"]');
            const preferredLanguage = currentStepEl.querySelector('[name="preferred_language"]');
            const cv = currentStepEl.querySelector('[name="cv"]');

            // Phone Number
            if (phoneNumber.value) {
                if (!/^[\+]?[0-9\s\-\(\)]+$/.test(phoneNumber.value)) {
                    errors.push({ field: phoneNumber, message: 'Please enter a valid phone number.' });
                }
                if (phoneNumber.value.length > 20) {
                    errors.push({ field: phoneNumber, message: 'Phone number must not exceed 20 characters.' });
                }
            }

            // Address
            if (address.value && address.value.length > 500) {
                errors.push({ field: address, message: 'Address must not exceed 500 characters.' });
            }

            // City and Country
            if (countryId.value && cityId.value) {
                const selectedCityOption = cityId.options[cityId.selectedIndex];
                if (selectedCityOption && selectedCityOption.getAttribute('data-country-id') != countryId.value) {
                    errors.push({ field: cityId, message: 'Selected city does not belong to the selected country.' });
                }
            }

            // Date of Birth
            if (dateOfBirth.value) {
                const dob = new Date(dateOfBirth.value);
                const today = new Date();
                const minDate = new Date('1900-01-01');
                if (isNaN(dob.getTime())) {
                    errors.push({ field: dateOfBirth, message: 'Please enter a valid date of birth.' });
                } else {
                    if (dob >= today) {
                        errors.push({ field: dateOfBirth, message: 'Date of birth must be before today.' });
                    }
                    if (dob < minDate) {
                        errors.push({ field: dateOfBirth, message: 'Please enter a valid date of birth.' });
                    }
                    const age = calculateAge(dob);
                    if (age < 16) {
                        errors.push({ field: dateOfBirth, message: 'You must be at least 16 years old to register.' });
                    } else if (age > 100) {
                        errors.push({ field: dateOfBirth, message: 'Please enter a valid date of birth.' });
                    }
                }
            }

            // Gender
            if (gender.value && !['male', 'female', 'other'].includes(gender.value)) {
                errors.push({ field: gender, message: 'Please select a valid gender option.' });
            }

            // Preferred Language
            if (preferredLanguage.value && !LANGUAGES.includes(preferredLanguage.value)) {
                errors.push({ field: preferredLanguage, message: 'Please select a valid language option.' });
            }

            // CV
            if (cv.files.length > 0) {
                const file = cv.files[0];
                const allowedMimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedMimes.includes(file.type)) {
                    errors.push({ field: cv, message: 'CV must be a PDF, DOC, or DOCX file.' });
                }
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    errors.push({ field: cv, message: 'CV file size must not exceed 5MB.' });
                }
            }
        }

        // Step 3: Interests & Preferences
        if (currentStep === 3) {
            const volunteerMode = currentStepEl.querySelector('[name="volunteer_mode"]:checked');
            const timeCommitment = currentStepEl.querySelector('[name="time_commitment"]:checked');
            const volunteeringInterests = currentStepEl.querySelectorAll('[name="volunteering_interests[]"]:checked');
            const termsAccepted = currentStepEl.querySelector('[name="terms_accepted"]:checked');

            // Volunteer Mode
            if (!volunteerMode) {
                errors.push({ field: currentStepEl.querySelector('[name="volunteer_mode"]'), message: 'Please select your preferred volunteer mode.' });
            } else if (!VOLUNTEER_MODES.includes(volunteerMode.value)) {
                errors.push({ field: volunteerMode, message: 'Please select a valid volunteer mode.' });
            }

            // Time Commitment
            if (!timeCommitment) {
                errors.push({ field: currentStepEl.querySelector('[name="time_commitment"]'), message: 'Please select your time commitment preference.' });
            } else if (!TIME_COMMITMENTS.includes(timeCommitment.value)) {
                errors.push({ field: timeCommitment, message: 'Please select a valid time commitment option.' });
            }

            // Volunteering Interests
            if (volunteeringInterests.length === 0) {
                errors.push({ field: currentStepEl.querySelector('[name="volunteering_interests[]"]'), message: 'Please select at least one area of interest.' });
            }

            // Terms Accepted
            if (!termsAccepted) {
                errors.push({ field: currentStepEl.querySelector('[name="terms_accepted"]'), message: 'You must accept the terms and conditions to proceed.' });
            }
        }

        // Display errors
        if (errors.length > 0) {
            displayFieldErrors(errors);
            showError('Please correct the errors below.');
            return false;
        }

        return true;
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function calculateAge(dateOfBirth) {
        const today = new Date();
        let age = today.getFullYear() - dateOfBirth.getFullYear();
        const monthDiff = today.getMonth() - dateOfBirth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dateOfBirth.getDate())) {
            age--;
        }
        return age;
    }

    function clearFieldErrors(stepElement) {
        stepElement.querySelectorAll('.field-error').forEach(el => el.remove());
        stepElement.querySelectorAll('.border-red-500').forEach(el => {
            el.classList.remove('border-red-500');
            el.classList.add('border-gray-300');
        });
    }

    function displayFieldErrors(errors) {
        errors.forEach(error => {
            const field = error.field;
            if (field) {
                field.classList.remove('border-gray-300');
                field.classList.add('border-red-500');
                
                const errorEl = document.createElement('p');
                errorEl.className = 'field-error text-red-500 text-xs mt-1';
                errorEl.textContent = error.message;
                
                if (field.nextSibling) {
                    field.parentNode.insertBefore(errorEl, field.nextSibling);
                } else {
                    field.parentNode.appendChild(errorEl);
                }
            }
        });

        // Focus and scroll to the first error
        if (errors.length > 0 && errors[0].field) {
            errors[0].field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            errors[0].field.focus();
        }
    }

    function showError(message) {
        const existingError = document.querySelector('.error-message:not([class*="mx-4"])');
        if (existingError) {
            existingError.remove();
        }

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

    backBtns.forEach((btn) => {
        btn.addEventListener('click', function() {
            if (currentStep > 1) {
                currentStep--;
                updateUI();
            }
        });
    });

    // Form submission handlers
    submitBtn.addEventListener('click', function(e) {
        e.preventDefault();
        handleFormSubmission();
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        handleFormSubmission();
    });

    function handleFormSubmission() {
        if (!validateCurrentStep()) {
            return;
        }

        submitBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Creating Account...
        `;
        submitBtn.disabled = true;

        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw { isHttpError: true, status: response.status, data: errorData };
                }).catch(() => {
                    throw { isHttpError: true, status: response.status, data: null };
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                try {
                    sessionStorage.removeItem('registration_progress');
                } catch (e) {
                    console.warn('Could not clear saved progress:', e);
                }
                showSuccess(data.message || 'Registration successful!');
                
                setTimeout(() => {
                    window.location.href = data.redirect_url || '/dashboard';
                }, 2000);
            } else {
                if (data.errors) {
                    displayServerErrors(data.errors);
                    let errorMessages = [];
                    Object.values(data.errors).forEach(errorArray => {
                        if (Array.isArray(errorArray)) {
                            errorMessages = errorMessages.concat(errorArray);
                        } else {
                            errorMessages.push(errorArray);
                        }
                    });
                    showError(errorMessages[0] || 'Please correct the errors below.');
                } else {
                    showError(data.message || 'Registration failed. Please try again.');
                }
                resetSubmitButton();
            }
        })
        .catch(error => {
            console.error('Registration error:', error);
            
            if (error.isHttpError && error.data) {
                if (error.data.errors) {
                    displayServerErrors(error.data.errors);
                    let errorMessages = [];
                    Object.values(error.data.errors).forEach(errorArray => {
                        if (Array.isArray(errorArray)) {
                            errorMessages = errorMessages.concat(errorArray);
                        } else {
                            errorMessages.push(errorArray);
                        }
                    });
                    showError(errorMessages[0] || 'Please correct the errors below.');
                } else {
                    showError(error.data.message || `Registration failed (${error.status}). Please try again.`);
                }
            } else if (error.isHttpError) {
                showError(`Registration failed (${error.status}). Please try again.`);
            } else {
                showError('Network error. Please check your connection and try again.');
            }
            
            resetSubmitButton();
        });
    }

    function resetSubmitButton() {
        submitBtn.innerHTML = 'Complete Registration';
        submitBtn.disabled = false;
    }

    function displayServerErrors(errors) {
        const formattedErrors = Object.keys(errors).map(fieldName => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            const errorMessages = Array.isArray(errors[fieldName]) ? errors[fieldName] : [errors[fieldName]];
            return { field, message: errorMessages[0] };
        }).filter(error => error.field);

        displayFieldErrors(formattedErrors);

        // Navigate to the step containing the first error
        const firstErrorField = form.querySelector('.border-red-500');
        if (firstErrorField) {
            for (let step = 1; step <= totalSteps; step++) {
                const stepElement = document.getElementById(`step-${step}`);
                if (stepElement && stepElement.contains(firstErrorField)) {
                    currentStep = step;
                    updateUI();
                    setTimeout(() => {
                        firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        try {
                            firstErrorField.focus();
                        } catch (e) {
                            console.log('Could not focus on field:', e);
                        }
                    }, 300);
                    break;
                }
            }
        }
    }

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
            sessionStorage.setItem('registration_progress', JSON.stringify({
                step: currentStep,
                data: data,
                timestamp: new Date().toISOString()
            }));
        } catch (e) {
            console.warn('Could not save progress:', e);
        }
    }

    function loadProgress() {
        try {
            const saved = sessionStorage.getItem('registration_progress');
            if (saved) {
                const progress = JSON.parse(saved);
                
                Object.entries(progress.data).forEach(([name, value]) => {
                    if (name.includes('password')) return;
                    
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
                
                if (progress.step > 1) {
                    currentStep = progress.step;
                    updateUI();
                }
            }
        } catch (e) {
            console.warn('Could not load progress:', e);
        }
    }

    function handleServerSideErrors() {
        const errorElements = document.querySelectorAll('.text-red-500, .border-red-500');
        
        if (errorElements.length > 0) {
            let errorStep = 1;
            
            for (let step = 1; step <= totalSteps; step++) {
                const stepElement = document.getElementById(`step-${step}`);
                const stepErrors = stepElement.querySelectorAll('.text-red-500, .border-red-500');
                
                if (stepErrors.length > 0) {
                    errorStep = step;
                    break;
                }
            }
            
            currentStep = errorStep;
            updateUI();
            
            setTimeout(() => {
                const firstError = document.querySelector('.text-red-500');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        }
    }

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
            
            const existingFeedback = parent.querySelector('.email-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }
            
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

    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('change', () => {
            clearTimeout(window.autoSaveTimeout);
            window.autoSaveTimeout = setTimeout(saveProgress, 1000);
        });
    });

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
    handleServerSideErrors();
});
    </script>
</body>
</html>