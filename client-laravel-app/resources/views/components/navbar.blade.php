<!-- Main Navigation Component -->
<nav class="bg-green-700 shadow-lg sticky top-0 z-50 font-inter">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <!-- Logo Section -->
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-3">
                    <!-- AU Logo -->
                    <div class="flex items-center justify-center">
                        <img src="{{ asset('img/au-logo.png') }}" alt="AU Logo" class="h-10">
                    </div>
                    <div class="text-white">
                        <div class="text-sm font-bold tracking-wide">VOLUNTEERING</div>
                        <div class="text-sm opacity-90 tracking-wide">LINKAGE PLATFORM</div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Links - Desktop -->
            <div class="hidden lg:flex items-center space-x-4">
                <a href="{{ route('home') }}" 
                   class="text-white hover:text-green-200 font-medium transition-all duration-300 border-b-2 pb-1 tracking-wide {{ request()->routeIs('home') ? 'border-green-200' : 'border-transparent hover:border-green-200' }}">
                    HOME
                </a>
                <a href="{{ route('about') }}" 
                   class="text-white hover:text-green-200 font-medium transition-all duration-300 border-b-2 pb-1 tracking-wide {{ request()->routeIs('about') ? 'border-green-200' : 'border-transparent hover:border-green-200' }}">
                    ABOUT US
                </a>
                <a href="#" 
                   class="text-white hover:text-green-200 font-medium transition-all duration-300 border-b-2 border-transparent hover:border-green-200 pb-1 tracking-wide">
                    INTERACTIVE MAP
                </a>
                <a href="#" 
                   class="text-white hover:text-green-200 font-medium transition-all duration-300 border-b-2 border-transparent hover:border-green-200 pb-1 tracking-wide">
                    ORGANIZATIONS
                </a>
                <a href="#" 
                   class="text-white hover:text-green-200 font-medium transition-all duration-300 border-b-2 border-transparent hover:border-green-200 pb-1 tracking-wide">
                    NEWS
                </a>
                <a href="#" 
                   class="text-white hover:text-green-200 font-medium transition-all duration-300 border-b-2 border-transparent hover:border-green-200 pb-1 tracking-wide">
                    OPPORTUNITIES
                </a>
                
                <!-- Resources Dropdown -->
                <div class="relative group">
                    <button class="text-white hover:text-green-200 font-medium transition-all duration-300 border-b-2 border-transparent hover:border-green-200 pb-1 flex items-center tracking-wide">
                        RESOURCES
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div class="absolute top-full right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                        <a href="#" class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>
                                    <div class="font-semibold">Volunteering Policies</div>
                                    <div class="text-xs text-gray-500">Guidelines & policies</div>
                                </div>
                            </div>
                        </a>
                        <a href="#" class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>
                                    <div class="font-semibold">Reports</div>
                                    <div class="text-xs text-gray-500">Statistical reports</div>
                                </div>
                            </div>
                        </a>
                        <a href="#" class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                                <div>
                                    <div class="font-semibold">Best Practices</div>
                                    <div class="text-xs text-gray-500">Tips & recommendations</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <a href="#" 
                   class="text-white hover:text-green-200 font-medium transition-all duration-300 border-b-2 border-transparent hover:border-green-200 pb-1 tracking-wide">
                    AU HOME
                </a>
            </div>
            
            <!-- User Menu / Auth for authenticated users -->
            @auth
            <div class="hidden lg:flex items-center space-x-3">
                <!-- Notifications -->
                <div class="relative">
                    <button class="p-2 text-white hover:text-green-200 transition-colors relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.5-3.5a50.002 50.002 0 00-7 0L5 17h5m5 0v1a3 3 0 01-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
                    </button>
                </div>
                
                <!-- Messages -->
                <div class="relative">
                    <button class="p-2 text-white hover:text-green-200 transition-colors relative">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
                    </button>
                </div>
                
                <!-- User Dropdown -->
                <div class="relative group">
                    <button class="flex items-center space-x-3 p-2 rounded-2xl hover:bg-green-600 transition-all">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-md">
                            <span class="text-green-700 font-semibold text-sm">
                                {{ strtoupper(substr(auth()->user()->getFullNameAttribute() ?? 'U', 0, 1)) }}
                            </span>
                        </div>
                        <div class="hidden md:block text-left">
                            <div class="font-semibold text-white text-sm">{{ auth()->user()->getFullNameAttribute() ?? 'User' }}</div>
                            <div class="text-xs text-green-200">{{ ucfirst(auth()->user()->user_type ?? 'Member') }}</div>
                        </div>
                        <svg class="w-4 h-4 text-white group-hover:text-green-200 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    
                    <div class="absolute right-0 top-full w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0 mt-2">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <div class="font-semibold text-gray-900">{{ auth()->user()->getFullNameAttribute() }}</div>
                            <div class="text-sm text-gray-500">{{ auth()->user()->email }}</div>
                        </div>
                        
                        <a href="{{ route('volunteer.dashboard') }}" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                Dashboard
                            </div>
                        </a>
                        
                        <div class="border-t border-gray-100 mt-2 pt-2">
                            <a href="{{ route('logout') }}" 
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                               class="block px-4 py-3 text-red-600 hover:bg-red-50 transition-colors">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Sign Out
                                </div>
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                                @csrf
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endauth
            
            <!-- Mobile menu button -->
            <div class="lg:hidden flex items-center">
                <button type="button" id="mobile-menu-button" class="p-2 text-white hover:text-green-200 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="menu-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg class="h-6 w-6 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" id="close-icon">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="lg:hidden hidden" id="mobile-menu">
        <div class="bg-green-800 border-t border-green-600">
            <div class="px-4 py-3 space-y-1">
                <!-- Mobile Auth Section (for guests) -->
                @guest
                <div class="border-b border-green-600 pb-4 mb-4">
                    <div class="flex space-x-3">
                        <a href="{{ route('login') }}" class="flex-1 text-center py-2 px-4 bg-green-600 text-white rounded-lg font-medium hover:bg-green-500 transition-colors">
                            LOGIN
                        </a>
                        <a href="{{ route('registration.index') }}" class="flex-1 text-center py-2 px-4 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors">
                            REGISTER
                        </a>
                    </div>
                </div>
                @endguest
                
                <!-- Mobile User Info (for authenticated users) -->
                @auth
                <div class="border-b border-green-600 pb-4 mb-4">
                    <div class="flex items-center space-x-3 px-4 py-3 bg-green-600 rounded-xl">
                        <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center">
                            <span class="text-green-700 font-semibold text-sm">
                                {{ strtoupper(substr(auth()->user()->getFullNameAttribute() ?? 'U', 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <div class="font-semibold text-white text-sm">{{ auth()->user()->getFullNameAttribute() ?? 'User' }}</div>
                            <div class="text-xs text-green-200">{{ auth()->user()->email }}</div>
                        </div>
                    </div>
                </div>
                @endauth
                
                <!-- Mobile Navigation Links -->
                <a href="{{ route('home') }}" 
                   class="block px-4 py-3 text-white hover:text-green-200 hover:bg-green-700 transition-colors rounded-lg {{ request()->routeIs('home') ? 'bg-green-700' : '' }}">
                    HOME
                </a>
                <a href="{{ route('about') }}" 
                   class="block px-4 py-3 text-white hover:text-green-200 hover:bg-green-700 transition-colors rounded-lg {{ request()->routeIs('about') ? 'bg-green-700' : '' }}">
                    ABOUT US
                </a>
                <a href="#" class="block px-4 py-3 text-white hover:text-green-200 hover:bg-green-700 transition-colors rounded-lg">
                    INTERACTIVE MAP
                </a>
                <a href="#" class="block px-4 py-3 text-white hover:text-green-200 hover:bg-green-700 transition-colors rounded-lg">
                    ORGANIZATIONS
                </a>
                <a href="#" class="block px-4 py-3 text-white hover:text-green-200 hover:bg-green-700 transition-colors rounded-lg">
                    NEWS
                </a>
                <a href="#" class="block px-4 py-3 text-white hover:text-green-200 hover:bg-green-700 transition-colors rounded-lg">
                    OPPORTUNITIES
                </a>
                
                <!-- Mobile Resources Submenu -->
                <div class="px-4 py-3">
                    <div class="text-white font-medium mb-2">RESOURCES</div>
                    <div class="pl-4 space-y-2">
                        <a href="#" class="block py-2 text-green-200 hover:text-white transition-colors">
                            Volunteering Policies
                        </a>
                        <a href="#" class="block py-2 text-green-200 hover:text-white transition-colors">
                            Reports
                        </a>
                        <a href="#" class="block py-2 text-green-200 hover:text-white transition-colors">
                            Best Practices
                        </a>
                    </div>
                </div>
                
                <a href="#" class="block px-4 py-3 text-white hover:text-green-200 hover:bg-green-700 transition-colors rounded-lg">
                    AU HOME
                </a>
                
                @auth
                <!-- Mobile User Actions -->
                <div class="border-t border-green-600 pt-4 mt-4">
                    <a href="{{ route('volunteer.dashboard') }}" 
                       class="block px-4 py-3 text-white hover:text-green-200 hover:bg-green-700 transition-colors rounded-lg">
                        Dashboard
                    </a>
                    <a href="{{ route('logout') }}" 
                       onclick="event.preventDefault(); document.getElementById('mobile-logout-form').submit();"
                       class="block px-4 py-3 text-red-300 hover:text-red-100 hover:bg-red-700 transition-colors rounded-lg">
                        Sign Out
                    </a>
                    <form id="mobile-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                        @csrf
                    </form>
                </div>
                @endauth
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuIcon = document.getElementById('menu-icon');
    const closeIcon = document.getElementById('close-icon');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            // Toggle mobile menu visibility
            mobileMenu.classList.toggle('hidden');
            
            // Toggle icons
            menuIcon.classList.toggle('hidden');
            closeIcon.classList.toggle('hidden');
            
            // Prevent body scroll when menu is open
            if (!mobileMenu.classList.contains('hidden')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (mobileMenu && !mobileMenu.contains(event.target) && !mobileMenuButton.contains(event.target)) {
            mobileMenu.classList.add('hidden');
            menuIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });

    // Close mobile menu on window resize (desktop)
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            mobileMenu.classList.add('hidden');
            menuIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });
});
</script>