<!-- Footer Component -->
<footer style="background-color: #0F5132;" class="text-white">
    <!-- Main Footer Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            
            <!-- About Section -->
            <div class="col-span-1 lg:col-span-2">
                <div class="flex items-center mb-4">
                    <img src="{{ asset('images/logo-footer.png') }}" alt="AU-VLP Logo" class="h-12 w-auto mr-3">
                    <div>
                        <h3 class="text-xl font-bold">AU Volunteer Platform</h3>
                        <p class="text-gray-300 text-sm">African Union Volunteer Linkage Platform</p>
                    </div>
                </div>
                <p class="text-gray-300 mb-4 max-w-md">
                    Connecting volunteers and organizations across Africa to promote peace, development, and unity through meaningful volunteer opportunities.
                </p>
                <div class="flex space-x-4">
                    <!-- Social Media Links -->
                    <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Facebook">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="Twitter">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="LinkedIn">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors" aria-label="YouTube">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('about') }}" class="text-gray-300 hover:text-white transition-colors">About AU-VLP</a></li>
                    <li><a href="{{ route('volunteer.index') }}" class="text-gray-300 hover:text-white transition-colors">Find Volunteers</a></li>
                    <li><a href="{{ route('events.index') }}" class="text-gray-300 hover:text-white transition-colors">Events</a></li>
                    <li><a href="{{ route('news.index') }}" class="text-gray-300 hover:text-white transition-colors">News & Updates</a></li>
                    <li><a href="{{ route('resources.index') }}" class="text-gray-300 hover:text-white transition-colors">Resources</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gray-300 hover:text-white transition-colors">Contact Us</a></li>
                </ul>
            </div>

            <!-- For Organizations -->
            <div>
                <h4 class="text-lg font-semibold mb-4">For Organizations</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('registration.organization.start') }}" class="text-gray-300 hover:text-white transition-colors">Register Organization</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Post Opportunities</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Manage Events</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Organization Guide</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Success Stories</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Partnership</a></li>
                </ul>
            </div>
        </div>

        <!-- Additional Footer Sections -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-12 pt-8 border-t border-gray-700">
            
            <!-- Resource Types (Dynamic from Database) -->
            @if(isset($resourceTypes) && $resourceTypes->count() > 0)
            <div>
                <h4 class="text-lg font-semibold mb-4">Resource Categories</h4>
                <ul class="space-y-2">
                    @foreach($resourceTypes->take(6) as $resourceType)
                    <li><a href="{{ route('resources.index', ['type' => $resourceType->slug]) }}" class="text-gray-300 hover:text-white transition-colors">{{ $resourceType->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- For Volunteers -->
            <div>
                <h4 class="text-lg font-semibold mb-4">For Volunteers</h4>
                <ul class="space-y-2">
                    <li><a href="{{ route('registration.volunteer.start') }}" class="text-gray-300 hover:text-white transition-colors">Join as Volunteer</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Find Opportunities</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Volunteer Guide</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Training Resources</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Certificates</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Community Forum</a></li>
                </ul>
            </div>

            <!-- Support & Legal -->
            <div>
                <h4 class="text-lg font-semibold mb-4">Support & Legal</h4>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Help Center</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">FAQs</a></li>
                    <li><a href="{{ route('privacy') }}" class="text-gray-300 hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-gray-300 hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="#" class="text-gray-300 hover:text-white transition-colors">Cookie Policy</a></li>
                    <li><a href="mailto:support@au-vlp.org" class="text-gray-300 hover:text-white transition-colors">Support</a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bottom Footer -->
    <div class="border-t border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-6">
                    <p class="text-gray-400 text-sm">
                        © {{ date('Y') }} African Union Volunteer Linkage Platform. All rights reserved.
                    </p>
                    <div class="flex space-x-4 text-sm">
                        <span class="text-gray-400">Powered by</span>
                        <a href="https://au.int" target="_blank" class="text-gray-300 hover:text-white transition-colors">African Union</a>
                    </div>
                </div>
                
                <div class="mt-4 md:mt-0 flex items-center space-x-4">
                    <!-- Language Selector -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-gray-300 hover:text-white transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                            <span class="text-sm">{{ strtoupper(app()->getLocale()) }}</span>
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" 
                             class="absolute bottom-full right-0 mb-2 w-32 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5">
                            <div class="py-1">
                                <a href="{{ route('language.switch', 'en') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">English</a>
                                <a href="{{ route('language.switch', 'fr') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Français</a>
                                <a href="{{ route('language.switch', 'ar') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">العربية</a>
                                <a href="{{ route('language.switch', 'pt') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Português</a>
                            </div>
                        </div>
                    </div>

                    <!-- Back to Top Button -->
                    <button onclick="scrollToTop()" class="text-gray-400 hover:text-white transition-colors" aria-label="Back to top">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/hide back to top button based on scroll position
window.addEventListener('scroll', function() {
    const backToTopButton = document.querySelector('button[onclick="scrollToTop()"]');
    if (window.pageYOffset > 300) {
        backToTopButton.style.opacity = '1';
    } else {
        backToTopButton.style.opacity = '0.5';
    }
});
</script>
