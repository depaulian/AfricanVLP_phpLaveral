<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-optimized" content="true">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#3B82F6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
    
    <!-- Prevent zoom on input focus -->
    <meta name="format-detection" content="telephone=no">
    
    <title>@yield('title') - {{ config('app.name') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/css/mobile.css'])
    
    <!-- Additional mobile-specific styles -->
    <style>
        /* Mobile-specific optimizations */
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        
        body {
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: none;
            user-select: none;
            -webkit-user-select: none;
        }
        
        input, textarea, select {
            user-select: text;
            -webkit-user-select: text;
            font-size: 16px; /* Prevent zoom on iOS */
        }
        
        /* Custom scrollbar for mobile */
        ::-webkit-scrollbar {
            width: 3px;
        }
        
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        
        ::-webkit-scrollbar-thumb {
            background: rgba(0,0,0,0.2);
            border-radius: 3px;
        }
        
        /* Loading animation */
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Touch feedback */
        .touch-feedback:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
        
        /* Safe area handling for notched devices */
        .safe-area-top {
            padding-top: env(safe-area-inset-top);
        }
        
        .safe-area-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
        
        /* Pull to refresh indicator */
        .pull-to-refresh {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(to bottom, #3B82F6, #1D4ED8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        
        .pull-to-refresh.active {
            transform: translateY(0);
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased">
    <!-- Pull to refresh indicator -->
    <div id="pullToRefresh" class="pull-to-refresh">
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <span>Pull to refresh</span>
        </div>
    </div>

    <!-- Main Content -->
    <div id="app" class="min-h-screen">
        @yield('content')
    </div>

    <!-- Bottom Navigation (if needed) -->
    @if(isset($showBottomNav) && $showBottomNav)
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 safe-area-bottom z-50">
        <div class="flex justify-around py-2">
            <a href="{{ route('profile.mobile.dashboard') }}" 
               class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('profile.mobile.dashboard') ? 'text-blue-500' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v3H8V5z"></path>
                </svg>
                <span class="text-xs mt-1">Profile</span>
            </a>
            
            <a href="{{ route('volunteering.index') }}" 
               class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('volunteering.*') ? 'text-blue-500' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 00-2 2H8a2 2 0 00-2-2V6m8 0h2a2 2 0 012 2v6.5"></path>
                </svg>
                <span class="text-xs mt-1">Volunteer</span>
            </a>
            
            <a href="{{ route('profile.mobile.documents') }}" 
               class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('profile.mobile.documents*') ? 'text-blue-500' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="text-xs mt-1">Documents</span>
            </a>
            
            <a href="{{ route('profile.mobile.notifications') }}" 
               class="flex flex-col items-center py-2 px-3 {{ request()->routeIs('profile.mobile.notifications*') ? 'text-blue-500' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.868 19.462A17.013 17.013 0 003 12c0-9.75 4.5-15 9-15s9 5.25 9 15a17.013 17.013 0 00-1.868 7.462"></path>
                </svg>
                <span class="text-xs mt-1">Alerts</span>
            </a>
        </div>
    </nav>
    @endif

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex flex-col items-center">
            <div class="loading-spinner w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full mb-3"></div>
            <span class="text-gray-700">Loading...</span>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toastContainer" class="fixed top-4 left-4 right-4 z-50 space-y-2"></div>

    <!-- Scripts -->
    @vite(['resources/js/app.js', 'resources/js/mobile.js'])
    
    <!-- Mobile-specific JavaScript -->
    <script>
        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);

        // Handle pull to refresh
        let startY = 0;
        let currentY = 0;
        let pullDistance = 0;
        let isPulling = false;
        const pullThreshold = 80;
        const pullToRefreshElement = document.getElementById('pullToRefresh');

        document.addEventListener('touchstart', function(e) {
            if (window.scrollY === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        });

        document.addEventListener('touchmove', function(e) {
            if (!isPulling) return;
            
            currentY = e.touches[0].clientY;
            pullDistance = currentY - startY;
            
            if (pullDistance > 0 && window.scrollY === 0) {
                e.preventDefault();
                
                if (pullDistance > pullThreshold) {
                    pullToRefreshElement.classList.add('active');
                } else {
                    pullToRefreshElement.classList.remove('active');
                }
            }
        });

        document.addEventListener('touchend', function(e) {
            if (!isPulling) return;
            
            isPulling = false;
            
            if (pullDistance > pullThreshold) {
                // Trigger refresh
                setTimeout(() => {
                    window.location.reload();
                }, 300);
            } else {
                pullToRefreshElement.classList.remove('active');
            }
            
            pullDistance = 0;
        });

        // Service Worker registration for offline support
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered: ', registration);
                    })
                    .catch(function(registrationError) {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }

        // Global loading functions
        window.showLoading = function() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        };

        window.hideLoading = function() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        };

        // Global toast function
        window.showToast = function(message, type = 'info', duration = 3000) {
            const toast = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-500' : 
                           type === 'error' ? 'bg-red-500' : 
                           type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
            
            toast.className = `${bgColor} text-white px-4 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
            toast.textContent = message;
            
            document.getElementById('toastContainer').appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full');
            }, 100);
            
            // Animate out and remove
            setTimeout(() => {
                toast.classList.add('translate-x-full');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, duration);
        };

        // Handle network status
        window.addEventListener('online', function() {
            showToast('Connection restored', 'success');
        });

        window.addEventListener('offline', function() {
            showToast('You are offline', 'warning');
        });

        // Haptic feedback for supported devices
        window.hapticFeedback = function(type = 'light') {
            if (navigator.vibrate) {
                const patterns = {
                    light: [10],
                    medium: [20],
                    heavy: [30],
                    success: [10, 50, 10],
                    error: [50, 50, 50]
                };
                navigator.vibrate(patterns[type] || patterns.light);
            }
        };

        // Add touch feedback to buttons
        document.addEventListener('DOMContentLoaded', function() {
            const buttons = document.querySelectorAll('button, .touch-feedback');
            buttons.forEach(button => {
                button.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                button.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
    
    @stack('scripts')
</body>
</html>