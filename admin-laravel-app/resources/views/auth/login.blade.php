<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'AU VLP Admin') }} - Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
     
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-green-50 to-blue-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white/85 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl p-8">
            <!-- Logo Icon -->
            <div class="flex justify-center mb-6">
                <div class="w-16 h-16 bg-white/90 border border-gray-200/20 rounded-2xl flex items-center justify-center shadow-sm">
                    <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
            </div>

            <!-- Title -->
            <div class="text-center mb-6">
                <h1 class="text-2xl font-semibold text-gray-900 mb-2">AU VLP Admin</h1>
                <p class="text-gray-600 text-sm">
                    Secure admin access for authorized users only
                </p>
            </div>

            <!-- Session Status -->
            @if (session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-2xl">
                    <p class="text-sm text-green-800 text-center">{{ session('success') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl">
                    <p class="text-sm text-red-800 text-center">{{ session('error') }}</p>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email Field -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                    </div>
                    <input id="email" 
                           class="w-full pl-12 pr-4 py-4 bg-gray-50/80 border border-gray-300 rounded-2xl focus:bg-white focus:border-green-500 focus:ring-4 focus:ring-green-500/10 focus:outline-none transition-all duration-200 text-gray-900 placeholder-gray-500 @error('email') border-red-300 focus:border-red-500 focus:ring-red-500/10 @enderror" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           placeholder="Email"
                           required 
                           autofocus 
                           autocomplete="username" />
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <input id="password" 
                           class="w-full pl-12 pr-12 py-4 bg-gray-50/80 border border-gray-300 rounded-2xl focus:bg-white focus:border-green-500 focus:ring-4 focus:ring-green-500/10 focus:outline-none transition-all duration-200 text-gray-900 placeholder-gray-500 @error('password') border-red-300 focus:border-red-500 focus:ring-red-500/10 @enderror"
                           type="password"
                           name="password"
                           placeholder="Password"
                           required 
                           autocomplete="current-password" />
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Forgot Password -->
                <div class="text-right">
                    <a href="#" class="text-sm text-gray-600 hover:text-gray-900 transition-colors duration-200">
                        Forgot password?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-4 bg-green-800 hover:bg-green-900 rounded-2xl text-white font-medium text-lg transition-all duration-200 hover:-translate-y-0.5 shadow-lg hover:shadow-xl">
                    Get Started
                </button>

                <!-- Remember Me -->
                <div class="flex items-center justify-center pt-4">
                    <label for="remember_me" class="flex items-center text-sm text-gray-600">
                        <input id="remember_me" 
                               type="checkbox" 
                               class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500 focus:ring-2" 
                               name="remember">
                        <span class="ml-2">Remember me</span>
                    </label>
                </div>
            </form>

            <!-- Footer -->
            <div class="text-center mt-8 pt-6 border-t border-gray-200/50">
                <p class="text-xs text-gray-500">
                    Contact your system administrator for access
                </p>
            </div>
        </div>
    </div>
</body>
</html>