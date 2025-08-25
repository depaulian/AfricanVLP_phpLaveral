<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'AU VLP') }} - Login</title>

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
        .info-card {
            background: linear-gradient(135deg, rgba(15, 81, 50, 0.9) 0%, rgba(22, 163, 74, 0.9) 100%);
            backdrop-filter: blur(10px);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .hover-scale { transition: transform 0.3s ease; }
        .hover-scale:hover { transform: scale(1.02); }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(34, 197, 94, 0.15);
        }
    </style>
</head>
<body class="font-inter antialiased gradient-bg min-h-screen">
    <!-- Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-32 h-32 bg-yellow-400/10 rounded-full animate-float"></div>
        <div class="absolute bottom-32 right-16 w-24 h-24 bg-white/5 rounded-full animate-float" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 right-8 w-20 h-20 bg-green-400/10 rounded-full animate-float" style="animation-delay: 4s;"></div>
        <div class="absolute bottom-1/4 left-1/4 w-16 h-16 bg-yellow-400/5 rounded-full animate-float" style="animation-delay: 6s;"></div>
    </div>

    <div class="min-h-screen flex relative z-10">
        <!-- Left Side - Information Panel -->
        <div class="hidden lg:flex lg:flex-1 relative">
            <!-- Background Image with Overlay -->
            <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('/img/volunteer.jpg');">
                <div class="absolute inset-0 bg-gradient-to-br from-green-900/40 to-green-700/60"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-center px-12 py-16">
                <div class="info-card rounded-3xl p-8 shadow-2xl">
                    <div class="mb-8">
                        <div class="w-16 h-16 bg-yellow-400 rounded-2xl flex items-center justify-center mb-6">
                            <svg class="w-8 h-8 text-green-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        
                        <h1 class="text-3xl font-bold text-white mb-4 leading-tight">
                            Organizations and volunteers working together for peace and development
                        </h1>
                        
                        <p class="text-green-100 text-lg leading-relaxed mb-8">
                            The African Union-VLP contributes to peace and development through volunteerism across the Africa continent.
                        </p>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 mb-8">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-400 mb-1">2.5K+</div>
                            <div class="text-xs text-green-100">Volunteers</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-400 mb-1">55</div>
                            <div class="text-xs text-green-100">Countries</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-400 mb-1">500+</div>
                            <div class="text-xs text-green-100">Projects</div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="space-y-4">
                        <div class="flex items-center text-green-100">
                            <svg class="w-5 h-5 text-yellow-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm">Connect across 55 African Union member states</span>
                        </div>
                        <div class="flex items-center text-green-100">
                            <svg class="w-5 h-5 text-yellow-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm">Develop skills through certified programs</span>
                        </div>
                        <div class="flex items-center text-green-100">
                            <svg class="w-5 h-5 text-yellow-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm">Track impact with detailed analytics</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 lg:flex-initial lg:w-1/2">
            <!-- Header with Logo -->

            <!-- Login Form -->
            <div class="w-full max-w-md">
                <div class="glass-effect rounded-3xl p-8 shadow-2xl hover-scale">
                    <!-- Welcome Text -->
                    <div class="text-center mb-8">
                        <div class="flex justify-center mb-4">
                            <img src="{{ asset('img/au-logo.svg') }}" alt="AU Logo" class="h-10">
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back</h2>
                        <p class="text-gray-600">Sign in to your AU-VLP account</p>
                    </div>

                    <!-- Session Status -->
                    @if (session('success'))
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

                        <!-- Email Address -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                    </svg>
                                </div>
                                <input id="email" 
                                       class="input-focus block w-full pl-12 pr-4 py-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('email') border-red-300 focus:ring-red-500 @enderror" 
                                       type="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       placeholder="Enter your email"
                                       required 
                                       autofocus 
                                       autocomplete="username" />
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input id="password" 
                                       class="input-focus block w-full pl-12 pr-4 py-4 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('password') border-red-300 focus:ring-red-500 @enderror"
                                       type="password"
                                       name="password"
                                       placeholder="Enter your password"
                                       required 
                                       autocomplete="current-password" />
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="flex items-center">
                                <input id="remember_me" 
                                       type="checkbox" 
                                       class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500 focus:border-green-300" 
                                       name="remember">
                                <span class="ml-2 text-sm text-gray-600">Remember me</span>
                            </label>

                            <a href="{{ route('password.request') }}" 
                               class="text-sm text-green-600 hover:text-green-700 font-medium transition-colors">
                                Forgot password?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <div>
                            <button type="submit" 
                                    class="w-full bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white font-semibold py-4 px-6 rounded-2xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                                <div class="flex items-center justify-center">
                                    <span>Sign In</span>
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                </div>
                            </button>
                        </div>

                        <!-- Divider -->
                        <div class="relative my-6">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-4 bg-white text-gray-500">New to AU-VLP?</span>
                            </div>
                        </div>

                        <!-- Register Link -->
                        <div class="text-center">
                            <a href="{{ route('registration.index') }}" 
                               class="w-full inline-flex items-center justify-center px-6 py-4 border-2 border-green-600 text-green-600 font-semibold rounded-2xl hover:bg-green-600 hover:text-white transition-all duration-300 transform hover:scale-105">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                                Create New Account
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Footer Links -->
                <div class="mt-8 text-center text-sm text-white/70">
                    <p class="mb-4">
                        By signing in, you agree to our 
                        <a href="#" class="text-yellow-400 hover:text-yellow-300 underline">Terms of Service</a> 
                        and 
                        <a href="#" class="text-yellow-400 hover:text-yellow-300 underline">Privacy Policy</a>
                    </p>
                    
                    <!-- Back to Home -->
                    <a href="{{ route('home') }}" 
                       class="inline-flex items-center text-white/80 hover:text-white transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add some interactive feedback
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('transform', 'scale-105');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('transform', 'scale-105');
                });
            });

            // Form submission feedback
            const form = document.querySelector('form');
            const submitBtn = form.querySelector('button[type="submit"]');
            
            form.addEventListener('submit', function() {
                submitBtn.innerHTML = `
                    <div class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Signing In...
                    </div>
                `;
                submitBtn.disabled = true;
            });
        });
    </script>
</body>
</html>