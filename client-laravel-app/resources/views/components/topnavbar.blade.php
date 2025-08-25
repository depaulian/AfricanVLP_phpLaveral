<!-- Top Navigation Bar Component -->
<div class="bg-white border-b border-gray-200 text-sm font-inter">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-12">
            <!-- Language Selection -->
            <div class="flex items-center space-x-4 text-gray-600">
                <button class="hover:text-green-600 transition-colors font-medium">English</button>
                <span class="text-gray-300">|</span>
                <button class="hover:text-green-600 transition-colors">Français</button>
                <span class="text-gray-300">|</span>
                <button class="hover:text-green-600 transition-colors">Português</button>
                <span class="text-gray-300">|</span>
                <button class="hover:text-green-600 transition-colors">العربية</button>
            </div>
            
            <!-- Auth Buttons -->
            <div class="flex items-center space-x-4">
                <a href="{{ route('login') }}" class="text-gray-600 hover:text-green-600 transition-colors font-medium px-4 py-1.5 rounded-lg hover:bg-green-50">
                    LOGIN
                </a>
                <a href="{{ route('registration.index') }}" class="bg-red-600 text-white px-4 py-1.5 rounded-lg font-medium hover:bg-red-700 transition-colors">
                    REGISTER
                </a>
            </div>
        </div>
    </div>
</div>