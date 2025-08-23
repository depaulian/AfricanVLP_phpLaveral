<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'African Union Volunteer Leadership Platform - Connecting volunteers across Africa for meaningful impact.')">
    <meta name="keywords" content="@yield('meta_keywords', 'African Union, volunteers, volunteer opportunities, Africa, development, community service')">
    <meta name="author" content="African Union">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ config('app.name', 'AU VLP') }} - @yield('title', 'Home')">
    <meta property="og:description" content="@yield('meta_description', 'African Union Volunteer Leadership Platform - Connecting volunteers across Africa for meaningful impact.')">
    <meta property="og:image" content="{{ asset('images/og-image.jpg') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ config('app.name', 'AU VLP') }} - @yield('title', 'Home')">
    <meta property="twitter:description" content="@yield('meta_description', 'African Union Volunteer Leadership Platform - Connecting volunteers across Africa for meaningful impact.')">
    <meta property="twitter:image" content="{{ asset('images/og-image.jpg') }}">

    <title>{{ config('app.name', 'AU VLP') }} - @yield('title', 'Home')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'au-green': {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                            950: '#052e16'
                        },
                        'au-forest': '#0F5132',
                        'au-gold': '#FFB800'
                    },
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif']
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'scale-in': 'scaleIn 0.4s ease-out',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite'
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideUp: {
                            '0%': { 
                                opacity: '0',
                                transform: 'translateY(30px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            }
                        },
                        scaleIn: {
                            '0%': {
                                opacity: '0',
                                transform: 'scale(0.9)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'scale(1)'
                            }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' }
                        }
                    }
                }
            }
        }
    </script>

    <!-- Custom Styles -->
    <style>
        .font-inter {
            font-family: 'Inter', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #0F5132 0%, #16a34a 100%);
        }
        
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .hover-scale {
            transition: transform 0.3s ease;
        }
        
        .hover-scale:hover {
            transform: scale(1.05);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #16a34a;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #15803d;
        }
        
        /* Loading animation */
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #16a34a;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Custom focus styles */
        .focus-ring:focus {
            outline: none;
            box-shadow: 0 0 0 2px #16a34a;
        }
        
        /* Smooth transitions for page changes */
        .page-transition {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        
        .page-transition.loaded {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
        
        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        
        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* Image lazy loading placeholder */
        .img-placeholder {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }
    </style>

    @stack('styles')
</head>
<body class="font-inter bg-gray-50 antialiased" x-data="{ 
    loading: false, 
    mobileMenuOpen: false,
    showBackToTop: false 
}" x-init="
    window.addEventListener('scroll', () => {
        showBackToTop = window.pageYOffset > 300;
    });
">
    <!-- Loading Indicator -->
    <div x-show="loading" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50" 
         style="display: none;">
        <div class="text-center">
            <div class="loading-spinner mx-auto mb-4"></div>
            <p class="text-gray-600 font-medium">Loading...</p>
        </div>
    </div>

    <div class="min-h-screen flex flex-col">
        <!-- Skip to main content (accessibility) -->
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 bg-green-600 text-white px-4 py-2 z-50">
            Skip to main content
        </a>

        <!-- Navigation -->
        @include('components.navbar')

        <!-- Flash Messages -->
        <div class="flash-messages">
            @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-400 p-4 m-4 rounded-lg animate-slide-up shadow-md" 
                 x-data="{ show: true }" 
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-90"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-90">
                <div class="flex justify-between items-start">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-800 font-medium">{{ session('success') }}</p>
                        </div>
                    </div>
                    <button @click="show = false" class="text-green-400 hover:text-green-600 transition-colors">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-50 border-l-4 border-red-400 p-4 m-4 rounded-lg animate-slide-up shadow-md" 
                 x-data="{ show: true }" 
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-90"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-90">
                <div class="flex justify-between items-start">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-800 font-medium">{{ session('error') }}</p>
                        </div>
                    </div>
                    <button @click="show = false" class="text-red-400 hover:text-red-600 transition-colors">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
            @endif

            @if(session('warning'))
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 m-4 rounded-lg animate-slide-up shadow-md" 
                 x-data="{ show: true }" 
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-90"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-90">
                <div class="flex justify-between items-start">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-800 font-medium">{{ session('warning') }}</p>
                        </div>
                    </div>
                    <button @click="show = false" class="text-yellow-400 hover:text-yellow-600 transition-colors">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
            @endif

            @if(session('info'))
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 m-4 rounded-lg animate-slide-up shadow-md" 
                 x-data="{ show: true }" 
                 x-show="show"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-90"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-90">
                <div class="flex justify-between items-start">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-800 font-medium">{{ session('info') }}</p>
                        </div>
                    </div>
                    <button @click="show = false" class="text-blue-400 hover:text-blue-600 transition-colors">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
            @endif
        </div>

        <!-- Main Content -->
        <main class="flex-1 page-transition" id="main-content">
            @yield('content')
        </main>

        <!-- Footer -->
        @include('components.footer')

        <!-- Back to Top Button -->
        <button x-show="showBackToTop" 
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90"
                @click="window.scrollTo({top: 0, behavior: 'smooth'})"
                class="fixed bottom-6 right-6 bg-green-600 text-white p-3 rounded-full shadow-lg hover:bg-green-700 hover:shadow-xl transition-all transform hover:-translate-y-1 z-40"
                style="display: none;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
            </svg>
        </button>
    </div>

    <!-- Scripts -->
    @auth
    <script>
        // Update notification and message counts
        function updateCounts() {
            // Update notification count
            fetch('{{ route("api.notifications.unread-count") }}')
                .then(response => response.json())
                .then(data => {
                    const notificationCount = document.getElementById('notification-count');
                    if (notificationCount) {
                        if (data.count > 0) {
                            notificationCount.textContent = data.count > 99 ? '99+' : data.count;
                            notificationCount.classList.remove('hidden');
                        } else {
                            notificationCount.classList.add('hidden');
                        }
                    }
                })
                .catch(error => console.error('Error fetching notification count:', error));

            // Update message count
            fetch('{{ route("api.messages.unread-count") }}')
                .then(response => response.json())
                .then(data => {
                    const messageCount = document.getElementById('message-count');
                    if (messageCount) {
                        if (data.count > 0) {
                            messageCount.textContent = data.count > 99 ? '99+' : data.count;
                            messageCount.classList.remove('hidden');
                        } else {
                            messageCount.classList.add('hidden');
                        }
                    }
                })
                .catch(error => console.error('Error fetching message count:', error));
        }

        // Update counts on page load
        document.addEventListener('DOMContentLoaded', updateCounts);

        // Update counts every 30 seconds
        setInterval(updateCounts, 30000);
    </script>
    @endauth
    
    <!-- Global Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Page transition animation
            const pageContent = document.querySelector('.page-transition');
            if (pageContent) {
                setTimeout(() => {
                    pageContent.classList.add('loaded');
                }, 100);
            }

            // Auto-hide flash messages after 5 seconds
            const flashMessages = document.querySelectorAll('[x-data*="show"]');
            flashMessages.forEach(message => {
                setTimeout(() => {
                    if (message.__x && message.__x.$data && message.__x.$data.show) {
                        message.__x.$data.show = false;
                    }
                }, 5000);
            });

            // Enhanced form handling with loading states
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitButton = this.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitButton && !submitButton.disabled) {
                        const originalContent = submitButton.innerHTML;
                        const loadingContent = `
                            <div class="flex items-center justify-center">
                                <div class="loading-spinner mr-2"></div>
                                <span>Processing...</span>
                            </div>
                        `;
                        
                        submitButton.innerHTML = loadingContent;
                        submitButton.disabled = true;
                        
                        // Re-enable button after 10 seconds as fallback
                        setTimeout(() => {
                            if (submitButton.disabled) {
                                submitButton.innerHTML = originalContent;
                                submitButton.disabled = false;
                            }
                        }, 10000);
                    }
                });
            });

            // Enhanced smooth scrolling for anchor links
            const anchorLinks = document.querySelectorAll('a[href^="#"]');
            anchorLinks.forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href === '#' || href === '#top') {
                        e.preventDefault();
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                        return;
                    }
                    
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        const headerOffset = 100;
                        const elementPosition = target.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                        
                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Lazy loading for images
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.remove('img-placeholder');
                                img.classList.add('opacity-100');
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });

                const images = document.querySelectorAll('img[data-src]');
                images.forEach(img => {
                    img.classList.add('opacity-0', 'transition-opacity', 'duration-300', 'img-placeholder');
                    imageObserver.observe(img);
                });
            }

            // Enhanced keyboard navigation
            document.addEventListener('keydown', function(e) {
                // ESC key functionality
                if (e.key === 'Escape') {
                    // Close dropdowns
                    const openDropdowns = document.querySelectorAll('[x-show="true"]');
                    openDropdowns.forEach(dropdown => {
                        if (dropdown.__x && dropdown.__x.$data) {
                            Object.keys(dropdown.__x.$data).forEach(key => {
                                if (typeof dropdown.__x.$data[key] === 'boolean') {
                                    dropdown.__x.$data[key] = false;
                                }
                            });
                        }
                    });
                }
                
                // Keyboard shortcuts
                if (e.altKey) {
                    switch(e.key) {
                        case 'h':
                            e.preventDefault();
                            window.location.href = '{{ route("home") }}';
                            break;
                        @auth
                        case 'd':
                            e.preventDefault();
                            window.location.href = '{{ route("volunteer.dashboard") }}';
                            break;
                        @endauth
                        case 'c':
                            e.preventDefault();
                            window.location.href = '{{ route("contact") }}';
                            break;
                    }
                }
            });

            // Performance monitoring
            if ('performance' in window) {
                window.addEventListener('load', function() {
                    setTimeout(function() {
                        const perfData = performance.getEntriesByType('navigation')[0];
                        const loadTime = perfData.loadEventEnd - perfData.navigationStart;
                        
                        // Log performance data (could be sent to analytics)
                        if (loadTime > 3000) {
                            console.warn('Page load time exceeded 3 seconds:', loadTime + 'ms');
                        }
                        
                        // You can send this data to your analytics service
                        // analytics.track('page_load_time', { duration: loadTime });
                    }, 0);
                });
            }

            // Service Worker registration (optional)
            if ('serviceWorker' in navigator && '{{ config("app.env") }}' === 'production') {
                navigator.serviceWorker.register('/sw.js')
                    .then(registration => {
                        console.log('SW registered successfully');
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed');
                    });
            }
        });

        // Global utility functions
        window.AU_VLP = {
            // Show notification utility
            showNotification: function(message, type = 'info') {
                const notification = document.createElement('div');
                const bgColors = {
                    'success': 'bg-green-50 border-green-400 text-green-800',
                    'error': 'bg-red-50 border-red-400 text-red-800',
                    'warning': 'bg-yellow-50 border-yellow-400 text-yellow-800',
                    'info': 'bg-blue-50 border-blue-400 text-blue-800'
                };
                
                notification.className = `fixed top-4 right-4 ${bgColors[type]} border-l-4 p-4 rounded-lg shadow-lg z-50 animate-slide-up max-w-sm`;
                notification.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium">${message}</p>
                            </div>
                        </div>
                        <div class="ml-auto pl-3">
                            <button onclick="this.parentElement.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Auto remove after 4 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.style.opacity = '0';
                        notification.style.transform = 'translateX(100%)';
                        setTimeout(() => {
                            notification.remove();
                        }, 300);
                    }
                }, 4000);
            },
            
            // Format date utility
            formatDate: function(dateString, options = {}) {
                const defaultOptions = { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                };
                const finalOptions = { ...defaultOptions, ...options };
                return new Date(dateString).toLocaleDateString('en-US', finalOptions);
            },
            
            // Truncate text utility
            truncateText: function(text, maxLength) {
                if (text.length <= maxLength) return text;
                return text.substring(0, maxLength) + '...';
            },
            
            // Copy to clipboard utility
            copyToClipboard: function(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    return navigator.clipboard.writeText(text).then(() => {
                        this.showNotification('Copied to clipboard!', 'success');
                    }).catch(err => {
                        this.showNotification('Failed to copy to clipboard', 'error');
                    });
                } else {
                    // Fallback for older browsers
                    const textArea = document.createElement('textarea');
                    textArea.value = text;
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand('copy');
                        this.showNotification('Copied to clipboard!', 'success');
                    } catch (err) {
                        this.showNotification('Failed to copy to clipboard', 'error');
                    }
                    document.body.removeChild(textArea);
                }
            },
            
            // Loading state utility
            setLoadingState: function(element, loading = true) {
                if (loading) {
                    element.disabled = true;
                    element.dataset.originalContent = element.innerHTML;
                    element.innerHTML = `
                        <div class="flex items-center justify-center">
                            <div class="loading-spinner mr-2"></div>
                            <span>Loading...</span>
                        </div>
                    `;
                } else {
                    element.disabled = false;
                    element.innerHTML = element.dataset.originalContent || element.innerHTML;
                }
            }
        };
    </script>
    
    @stack('scripts')

    <!-- Analytics (Production only) -->
    @if(config('app.env') === 'production')
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'GA_MEASUREMENT_ID');
    </script>
    
    <!-- You can add other analytics scripts here -->
    @endif

    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "African Union Volunteer Leadership Platform",
        "alternateName": "AU-VLP",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('images/logo.png') }}",
        "description": "Connecting volunteers and organizations across Africa to promote peace, development, and unity through meaningful volunteer opportunities.",
        "sameAs": [
            "https://twitter.com/AfricanUnion",
            "https://www.facebook.com/AfricanUnionCommission",
            "https://www.linkedin.com/company/african-union",
            "https://www.youtube.com/user/AfricanUnionCommission",
            "https://www.instagram.com/africanunion_official"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+251-11-551-7700",
            "contactType": "customer service",
            "email": "info@au-vlp.org",
            "areaServed": "Africa"
        },
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "P.O. Box 3243, Roosevelt Street",
            "addressLocality": "Addis Ababa",
            "addressCountry": "Ethiopia"
        }
    }
    </script>
</body>
</html>