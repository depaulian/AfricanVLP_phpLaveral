<nav class="bg-green-800 border-b border-green-700 mb-6">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16">
      <!-- Left side - Brand and Navigation -->
      <div class="flex">
        <!-- Brand -->
        <div class="flex-shrink-0 flex items-center">
          <a href="{{ url('/admin') }}" class="text-white text-xl font-bold hover:text-gray-200 transition-colors">
            <svg class="w-6 h-6 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
            </svg>
            AU VLP Admin
          </a>
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden md:ml-6 md:flex md:space-x-8">
          <a href="{{ route('admin.dashboard') }}" 
             class="inline-flex items-center px-1 pt-1 text-sm font-medium transition-colors {{ request()->routeIs('admin.dashboard*') ? 'text-white border-b-2 border-green-500' : 'text-gray-300 hover:text-white hover:border-b-2 hover:border-gray-300' }}">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
            </svg>
            Dashboard
          </a>

          <a href="{{ route('admin.users.index') }}" 
             class="inline-flex items-center px-1 pt-1 text-sm font-medium transition-colors {{ request()->routeIs('admin.users*') ? 'text-white border-b-2 border-green-500' : 'text-gray-300 hover:text-white hover:border-b-2 hover:border-gray-300' }}">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
            </svg>
            Users
          </a>

          <a href="{{ route('admin.organizations.index') }}" 
             class="inline-flex items-center px-1 pt-1 text-sm font-medium transition-colors {{ request()->routeIs('admin.organizations*') ? 'text-white border-b-2 border-green-500' : 'text-gray-300 hover:text-white hover:border-b-2 hover:border-gray-300' }}">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
            </svg>
            Organizations
          </a>

          <!-- Content Dropdown -->
          <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
            <a  href="#"
              class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-300 hover:text-white transition-colors focus:outline-none h-full"
              :class="{ 'text-white border-b-2 border-blue-500': {{ request()->routeIs('admin.news*', 'admin.events*', 'admin.blogs*', 'admin.resources*') ? 'true' : 'false' }} }"
              @click="open = !open">
              <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd"></path>
              </svg>
              Content
              <svg class="w-4 h-4 ml-1 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
              </svg>
            </a>
            <div 
              x-show="open"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 scale-95"
              x-transition:enter-end="opacity-100 scale-100"
              x-transition:leave="transition ease-in duration-150"
              x-transition:leave-start="opacity-100 scale-100"
              x-transition:leave-end="opacity-0 scale-95"
              class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 ring-1 ring-black ring-opacity-5">
              <a href="{{ route('admin.news.index') }}" 
                 class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors {{ request()->routeIs('admin.news*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd"></path>
                </svg>
                News
              </a>
              <a href="{{ route('admin.events.index') }}" 
                 class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors {{ request()->routeIs('admin.events*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                </svg>
                Events
              </a>
              <a href="{{ route('admin.blogs.index') }}" 
                 class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors {{ request()->routeIs('admin.blogs*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
                Blogs
              </a>
              <a href="{{ route('admin.resources.index') }}" 
                 class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors {{ request()->routeIs('admin.resources*') ? 'bg-gray-100 text-gray-900 font-medium' : '' }}">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
                Resources
              </a>
            </div>
          </div>

          <a href="{{ route('admin.forums.management.index') }}" 
             class="inline-flex items-center px-1 pt-1 text-sm font-medium transition-colors {{ request()->routeIs('admin.forums*') ? 'text-white border-b-2 border-blue-500' : 'text-gray-300 hover:text-white hover:border-b-2 hover:border-gray-300' }}">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
            </svg>
            Forums
          </a>

          <a href="{{ route('admin.support.index') }}" 
             class="inline-flex items-center px-1 pt-1 text-sm font-medium transition-colors {{ request()->routeIs('admin.support*') ? 'text-white border-b-2 border-blue-500' : 'text-gray-300 hover:text-white hover:border-b-2 hover:border-gray-300' }}">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-1.106-1.106A6.003 6.003 0 004 10c0 .639.1 1.255.283 1.832l1.875-1.875zm3.493-4.676a4.002 4.002 0 012.263.094l1.107-1.107A6.003 6.003 0 0010 4.5a5.98 5.98 0 00-2.415.493l1.58 1.58z" clip-rule="evenodd"></path>
            </svg>
            Support
          </a>
        </div>
      </div>

      <!-- Right side - User Menu -->
      <div class="flex items-center">
        @auth
          @if(auth()->user()->isAdmin())
            <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
              <a href="#" 
                class="flex items-center text-sm text-gray-300 hover:text-white focus:outline-none transition-colors"
                @click="open = !open">
                <div class="flex items-center">
                  <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mr-2">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                  </div>
                  <span class="hidden md:block font-medium">{{ auth()->user()->full_name ?? auth()->user()->first_name ?? 'Admin' }}</span>
                  <svg class="w-4 h-4 ml-2 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                  </svg>
                </div>
              </a>
              
              <div 
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 ring-1 ring-black ring-opacity-5">
                <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                  </svg>
                  Profile
                </a>
                <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors">
                  <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                  </svg>
                  Settings
                </a>
                <div class="border-t border-gray-100 my-1"></div>
                  <form id="logout-form" method="POST" action="{{ route('logout') }}" class="block">
                    @csrf
                    <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left transition-colors">
                      <svg class="w-4 h-4 mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"></path>
                      </svg>
                      Logout
                    </a>
              </form>
            </div>
          @endif
        @else
          <a href="{{ route('login') }}" class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
            Login
          </a>
        @endauth
      </div>

      <!-- Mobile menu button -->
      <div class="md:hidden flex items-center">
        <button type="button" class="text-gray-300 hover:text-white focus:outline-none focus:text-white transition-colors" onclick="toggleMobileMenu()">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile menu -->
  <div class="md:hidden hidden" id="mobile-menu">
    <div class="px-2 pt-2 pb-3 space-y-1 bg-gray-700 border-t border-gray-600">
      <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('admin.dashboard*') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-600' }}">
        <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
        </svg>
        Dashboard
      </a>
      <a href="{{ route('admin.users.index') }}" class="block px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('admin.users*') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-600' }}">
        <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
        </svg>
        Users
      </a>
      <a href="{{ route('admin.organizations.index') }}" class="block px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('admin.organizations*') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-600' }}">
        <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
        </svg>
        Organizations
      </a>
      
      <!-- Mobile Content Submenu -->
      <div x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-600 transition-colors">
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 002 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd"></path>
            </svg>
            Content
          </div>
          <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
          </svg>
        </button>
        <div x-show="open" x-transition class="pl-6 space-y-1">
          <a href="{{ route('admin.news.index') }}" class="block px-3 py-2 rounded-md text-sm text-gray-400 hover:text-white hover:bg-gray-600 transition-colors {{ request()->routeIs('admin.news*') ? 'text-white bg-gray-800' : '' }}">News</a>
          <a href="{{ route('admin.events.index') }}" class="block px-3 py-2 rounded-md text-sm text-gray-400 hover:text-white hover:bg-gray-600 transition-colors {{ request()->routeIs('admin.events*') ? 'text-white bg-gray-800' : '' }}">Events</a>
          <a href="{{ route('admin.blogs.index') }}" class="block px-3 py-2 rounded-md text-sm text-gray-400 hover:text-white hover:bg-gray-600 transition-colors {{ request()->routeIs('admin.blogs*') ? 'text-white bg-gray-800' : '' }}">Blogs</a>
          <a href="{{ route('admin.resources.index') }}" class="block px-3 py-2 rounded-md text-sm text-gray-400 hover:text-white hover:bg-gray-600 transition-colors {{ request()->routeIs('admin.resources*') ? 'text-white bg-gray-800' : '' }}">Resources</a>
        </div>
      </div>

      <a href="{{ route('admin.forums.management.index') }}" class="block px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('admin.forums*') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-600' }}">
        <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"></path>
        </svg>
        Forums
      </a>
      <a href="{{ route('admin.support.index') }}" class="block px-3 py-2 rounded-md text-base font-medium transition-colors {{ request()->routeIs('admin.support*') ? 'text-white bg-gray-900' : 'text-gray-300 hover:text-white hover:bg-gray-600' }}">
        <svg class="w-4 h-4 inline-block mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.078-2.183l1.562-1.562C15.802 8.249 16 9.1 16 10zm-5.165 3.913l1.58 1.58A5.98 5.98 0 0110 16a5.976 5.976 0 01-2.516-.552l1.562-1.562a4.006 4.006 0 001.789.027zm-4.677-2.796a4.002 4.002 0 01-.041-2.08l-1.106-1.106A6.003 6.003 0 004 10c0 .639.1 1.255.283 1.832l1.875-1.875zm3.493-4.676a4.002 4.002 0 012.263.094l1.107-1.107A6.003 6.003 0 0010 4.5a5.98 5.98 0 00-2.415.493l1.58 1.58z" clip-rule="evenodd"></path>
        </svg>
        Support
      </a>
    </div>
  </div>
</nav>

<script>
function toggleMobileMenu() {
  const menu = document.getElementById('mobile-menu');
  menu.classList.toggle('hidden');
}
</script>