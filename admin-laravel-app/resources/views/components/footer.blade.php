<!-- Admin Footer Component -->
<footer style="background-color: #0F5132;" class="border-t border-gray-200 mt-auto text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <!-- Copyright Section -->
            <div class="flex flex-col md:flex-row md:items-center space-y-2 md:space-y-0 md:space-x-6">
                <p class="text-sm text-gray-200">
                    © {{ date('Y') }} 
                    <a href="#" class="text-white hover:text-gray-200 font-medium">
                        AU Volunteering Linkage Platform
                    </a>
                    - Admin Dashboard
                </p>
                <div class="flex items-center space-x-1 text-sm text-gray-300">
                    <span>Version</span>
                    <span class="font-medium">{{ config('app.version', '1.0.0') }}</span>
                </div>
            </div>

            <!-- Footer Menu -->
            <div class="mt-4 md:mt-0">
                <nav class="flex space-x-6">
                    <a href="#" 
                       class="text-sm text-gray-200 hover:text-white transition-colors">
                        About
                    </a>
                    <a href="#" 
                       class="text-sm text-gray-200 hover:text-white transition-colors">
                        Help
                    </a>
                    <a href="#" 
                       class="text-sm text-gray-200 hover:text-white transition-colors">
                        FAQs
                    </a>
                    <a href="#" 
                       class="text-sm text-gray-200 hover:text-white transition-colors">
                        Support
                    </a>
                    <a href="#" 
                       class="text-sm text-gray-200 hover:text-white transition-colors">
                        Privacy
                    </a>
                    <a href="#" 
                       class="text-sm text-gray-200 hover:text-white transition-colors">
                        Terms
                    </a>
                </nav>
            </div>
        </div>

        <!-- Additional Admin Footer Info -->
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between text-xs text-gray-300">
                <div class="flex items-center space-x-4">
                    <span>Server: {{ php_uname('n') }}</span>
                    <span>PHP: {{ PHP_VERSION }}</span>
                    <span>Laravel: {{ app()->version() }}</span>
                </div>
                
                <div class="mt-2 md:mt-0 flex items-center space-x-4">
                    @auth
                        <span>Logged in as: {{ auth()->user()->full_name }}</span>
                        <span>Last login: {{ auth()->user()->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</span>
                    @endauth
                    <span>{{ now()->format('M j, Y g:i A T') }}</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Admin Footer Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update current time every minute
    function updateTime() {
        const timeElements = document.querySelectorAll('[data-time="current"]');
        const now = new Date();
        const timeString = now.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            timeZoneName: 'short'
        });
        
        timeElements.forEach(element => {
            element.textContent = timeString;
        });
    }
    
    // Update time immediately and then every minute
    updateTime();
    setInterval(updateTime, 60000);
});
</script>
