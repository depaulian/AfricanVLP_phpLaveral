<nav class="bg-white shadow-lg sticky top-0 z-50 font-inter">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-20">
          <!-- Logo -->
          <div class="flex items-center space-x-4">
            <div class="">
              <img src="{{ asset('img/au-logo.svg') }}" alt="AU Logo" class="h-20">
           </div>
              <div>
                  <p class="text-sm text-gray-500">Volunteer Leadership Platform</p>
              </div>
          </div>
          
          <!-- Navigation Links - Desktop -->
          <div class="hidden lg:flex items-center space-x-4">
              <!-- Home -->
              <a href="{{ route('home') }}" 
                 class="relative px-4 py-2 text-gray-700 hover:text-green-600 font-medium transition-all duration-300 group {{ request()->routeIs('home') ? 'text-green-600' : '' }}">
                  <span class="flex items-center">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                      </svg>
                      Home
                  </span>
                  @if(request()->routeIs('home'))
                  <div class="absolute bottom-0 left-0 w-full h-0.5 bg-green-600"></div>
                  @else
                  <div class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-600 group-hover:w-full transition-all duration-300"></div>
                  @endif
              </a>
              
              <!-- Opportunities Dropdown -->
              <div class="relative group">
                  <button class="px-4 py-2 text-gray-700 hover:text-green-600 font-medium transition-colors flex items-center">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0H8m8 0v2a2 2 0 01-2 2H10a2 2 0 01-2-2V6"/>
                      </svg>
                      Opportunities
                      <svg class="w-4 h-4 ml-1 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                      </svg>
                  </button>
                  <div class="absolute top-full left-0 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                      <a href="#" class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                          <div class="flex items-center">
                              <svg class="w-5 h-5 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0H8m8 0v2a2 2 0 01-2 2H10a2 2 0 01-2-2V6"/>
                              </svg>
                              <div>
                                  <div class="font-semibold">Browse Opportunities</div>
                                  <div class="text-xs text-gray-500">Find volunteer positions</div>
                              </div>
                          </div>
                      </a>
                      <a href="{{ route('events.index') }}" class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                          <div class="flex items-center">
                              <svg class="w-5 h-5 mr-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                              </svg>
                              <div>
                                  <div class="font-semibold">Events</div>
                                  <div class="text-xs text-gray-500">Upcoming events & workshops</div>
                              </div>
                          </div>
                      </a>
                      @auth
                      <a href="{{ route('volunteer.dashboard') }}" class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                          <div class="flex items-center">
                              <svg class="w-5 h-5 mr-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2m0 0h2m-2 0v6a2 2 0 002 2h2a2 2 0 002-2v-6m0 0h2a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012 2v6a2 2 0 01-2 2H7"/>
                              </svg>
                              <div>
                                  <div class="font-semibold">My Opportunities</div>
                                  <div class="text-xs text-gray-500">Manage applications</div>
                              </div>
                          </div>
                      </a>
                      @endauth
                  </div>
              </div>
              
              <!-- About -->
              <a href="{{ route('about') }}" 
                 class="relative px-4 py-2 text-gray-700 hover:text-green-600 font-medium transition-all duration-300 group {{ request()->routeIs('about') ? 'text-green-600' : '' }}">
                  <span class="flex items-center">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                      About
                  </span>
                  @if(request()->routeIs('about'))
                  <div class="absolute bottom-0 left-0 w-full h-0.5 bg-green-600"></div>
                  @else
                  <div class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-600 group-hover:w-full transition-all duration-300"></div>
                  @endif
              </a>
              
              <!-- Resources Dropdown -->
              <div class="relative group">
                  <button class="px-4 py-2 text-gray-700 hover:text-green-600 font-medium transition-colors flex items-center">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                      </svg>
                      Resources
                      <svg class="w-4 h-4 ml-1 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                      </svg>
                  </button>
                  <div class="absolute top-full left-0 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
                      <a href="#" class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                          <div class="flex items-center">
                              <svg class="w-5 h-5 mr-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                              </svg>
                              <div>
                                  <div class="font-semibold">Training Materials</div>
                                  <div class="text-xs text-gray-500">Guides & resources</div>
                              </div>
                          </div>
                      </a>
                      <a href="#" class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                          <div class="flex items-center">
                              <svg class="w-5 h-5 mr-3 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m0 0V6a2 2 0 012-2h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                              </svg>
                              <div>
                                  <div class="font-semibold">News & Updates</div>
                                  <div class="text-xs text-gray-500">Latest announcements</div>
                              </div>
                          </div>
                      </a>
                      <a href="{{ route('blog.public') }}" class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors">
                          <div class="flex items-center">
                              <svg class="w-5 h-5 mr-3 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                              </svg>
                              <div>
                                  <div class="font-semibold">Blog</div>
                                  <div class="text-xs text-gray-500">Stories & insights</div>
                              </div>
                          </div>
                      </a>
                  </div>
              </div>
              
              <!-- Contact -->
              <a href="{{ route('contact') }}" 
                 class="relative px-4 py-2 text-gray-700 hover:text-green-600 font-medium transition-all duration-300 group {{ request()->routeIs('contact') ? 'text-green-600' : '' }}">
                  <span class="flex items-center">
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      Contact
                  </span>
                  @if(request()->routeIs('contact'))
                  <div class="absolute bottom-0 left-0 w-full h-0.5 bg-green-600"></div>
                  @else
                  <div class="absolute bottom-0 left-0 w-0 h-0.5 bg-green-600 group-hover:w-full transition-all duration-300"></div>
                  @endif
              </a>
          </div>
          
          <!-- User Menu / Auth Buttons -->
          <div class="flex items-center space-x-3">
              @auth
                  <!-- Notifications -->
                  <div class="relative">
                      <button class="p-2 text-gray-500 hover:text-green-600 transition-colors relative">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-3.5-3.5a50.002 50.002 0 00-7 0L5 17h5m5 0v1a3 3 0 01-6 0v-1m6 0H9"/>
                          </svg>
                          <span id="notification-count" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
                      </button>
                  </div>
                  
                  <!-- Messages -->
                  <div class="relative">
                      <button class="p-2 text-gray-500 hover:text-green-600 transition-colors relative">
                          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                          </svg>
                          <span id="message-count" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
                      </button>
                  </div>
                  
                  <!-- User Dropdown -->
                  <div class="relative group">
                      <button class="flex items-center space-x-3 p-2 rounded-2xl hover:bg-gray-50 transition-all">
                          <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-md">
                              <span class="text-white font-semibold text-sm">
                                  {{ strtoupper(substr(auth()->user()->getFullNameAttribute() ?? 'U', 0, 1)) }}
                              </span>
                          </div>
                          <div class="hidden md:block text-left">
                              <div class="font-semibold text-gray-900 text-sm">{{ auth()->user()->getFullNameAttribute() ?? 'User' }}</div>
                              <div class="text-xs text-gray-500">{{ ucfirst(auth()->user()->user_type ?? 'Member') }}</div>
                          </div>
                          <svg class="w-4 h-4 text-gray-500 group-hover:text-gray-700 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                          </svg>
                      </button>
                      
                      <div class="absolute right-0 top-full w-64 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform translate-y-2 group-hover:translate-y-0">
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
                          
                          <a href="#" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors">
                              <div class="flex items-center">
                                  <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                  </svg>
                                  Profile Settings
                              </div>
                          </a>
                          
                          <a href="#" class="block px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors">
                              <div class="flex items-center">
                                  <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                  </svg>
                                  Preferences
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
              @else
                  <!-- Sign In Button - Enhanced -->
                  <div class="flex space-x-4">
                    <div class="inline-flex space-x-4">
                      <a href="{{ route('login') }}" 
                         class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-green-600 to-green-700 text-white px-8 py-2.5 font-semibold hover:from-green-700 hover:to-green-800 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 whitespace-nowrap">
                          Sign In
                      </a>
                      <a href="{{ route('registration.index') }}" 
                         class="inline-flex items-center justify-center rounded-2xl bg-yellow-400 text-black px-8 py-4 font-semibold text-lg hover:bg-yellow-300 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 whitespace-nowrap">
                          Join
                      </a>
                  </div>
                </div>
              @endauth
          </div>
          
          <!-- Mobile menu button -->
          <div class="lg:hidden flex items-center ml-4">
              <button type="button" id="mobile-menu-button" class="p-2 text-gray-600 hover:text-green-600 transition-colors rounded-lg hover:bg-gray-100">
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
      <div class="bg-white border-t border-gray-200">
          <div class="px-4 py-3 space-y-1">
              <!-- Mobile Navigation Links -->
              <a href="{{ route('home') }}" 
                 class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors rounded-xl {{ request()->routeIs('home') ? 'bg-green-50 text-green-600' : '' }}">
                  <div class="flex items-center">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                      </svg>
                      Home
                  </div>
              </a>
              
              <a href="#" 
                 class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors rounded-xl">
                  <div class="flex items-center">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0H8m8 0v2a2 2 0 01-2 2H10a2 2 0 01-2-2V6"/>
                      </svg>
                      Opportunities
                  </div>
              </a>
              
              <a href="{{ route('about') }}" 
                 class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors rounded-xl {{ request()->routeIs('about') ? 'bg-green-50 text-green-600' : '' }}">
                  <div class="flex items-center">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                      About
                  </div>
              </a>
              
              <a href="#" 
                 class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors rounded-xl">
                  <div class="flex items-center">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                      </svg>
                      Resources
                  </div>
              </a>
              
              <a href="{{ route('contact') }}" 
                 class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors rounded-xl {{ request()->routeIs('contact') ? 'bg-green-50 text-green-600' : '' }}">
                  <div class="flex items-center">
                      <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      Contact
                  </div>
              </a>
              
              <!-- Mobile Auth Section -->
              @guest
              <div class="border-t border-gray-200 mt-4 pt-4 space-y-3">
                  <!-- Mobile Sign In - Enhanced -->
                  <a href="{{ route('login') }}" 
                     class="flex items-center justify-center w-full px-4 py-3 text-gray-700 hover:text-green-600 font-medium transition-all duration-300 rounded-xl border-2 border-gray-200 hover:border-green-200 hover:bg-green-50">
                      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                      </svg>
                      Sign In
                  </a>
                  
                  <!-- Mobile Join Button - Enhanced -->
                  <a href="{{ route('registration.index') }}" 
                     class="flex items-center justify-center w-full bg-gradient-to-r from-green-600 to-green-700 text-white px-4 py-3 rounded-xl font-semibold text-center transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 hover:from-green-700 hover:to-green-800">
                      <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                      </svg>
                      Join Now
                  </a>
              </div>
              @else
              <div class="border-t border-gray-200 mt-4 pt-4">
                  <!-- User Info Mobile -->
                  <div class="px-4 py-3 bg-gray-50 rounded-xl mb-3">
                      <div class="flex items-center space-x-3">
                          <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                              <span class="text-white font-semibold text-sm">
                                  {{ strtoupper(substr(auth()->user()->getFullNameAttribute() ?? 'U', 0, 1)) }}
                              </span>
                          </div>
                          <div>
                              <div class="font-semibold text-gray-900 text-sm">{{ auth()->user()->getFullNameAttribute() ?? 'User' }}</div>
                              <div class="text-xs text-gray-500">{{ auth()->user()->email }}</div>
                          </div>
                      </div>
                  </div>
                  
                  <a href="{{ route('volunteer.dashboard') }}" 
                     class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors rounded-xl">
                      <div class="flex items-center">
                          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                          </svg>
                          Dashboard
                      </div>
                  </a>
                  
                  <a href="#" 
                     class="block px-4 py-3 text-gray-700 hover:bg-green-50 hover:text-green-600 transition-colors rounded-xl">
                      <div class="flex items-center">
                          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                          </svg>
                          Profile Settings
                      </div>
                  </a>
                  
                  <a href="{{ route('logout') }}" 
                     onclick="event.preventDefault(); document.getElementById('mobile-logout-form').submit();"
                     class="block px-4 py-3 text-red-600 hover:bg-red-50 transition-colors rounded-xl">
                      <div class="flex items-center">
                          <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                          </svg>
                          Sign Out
                      </div>
                  </a>
                  <form id="mobile-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                      @csrf
                  </form>
              </div>
              @endguest
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

  // Close mobile menu when clicking on links
  const mobileLinks = mobileMenu.querySelectorAll('a');
  mobileLinks.forEach(link => {
      link.addEventListener('click', function() {
          mobileMenu.classList.add('hidden');
          menuIcon.classList.remove('hidden');
          closeIcon.classList.add('hidden');
          document.body.style.overflow = '';
      });
  });

  // Smooth scroll for navigation links
  const navLinks = document.querySelectorAll('nav a[href^="#"]');
  navLinks.forEach(link => {
      link.addEventListener('click', function(e) {
          e.preventDefault();
          const targetId = this.getAttribute('href');
          const targetSection = document.querySelector(targetId);
          
          if (targetSection) {
              const headerOffset = 80;
              const elementPosition = targetSection.offsetTop;
              const offsetPosition = elementPosition - headerOffset;

              window.scrollTo({
                  top: offsetPosition,
                  behavior: 'smooth'
              });
          }
      });
  });

  // Active navigation highlight on scroll
  const sections = document.querySelectorAll('section[id]');
  const navItems = document.querySelectorAll('nav a[href^="#"]');

  function highlightActiveNavItem() {
      let current = '';
      sections.forEach(section => {
          const sectionTop = section.offsetTop - 100;
          if (pageYOffset >= sectionTop) {
              current = section.getAttribute('id');
          }
      });

      navItems.forEach(item => {
          item.classList.remove('text-green-600');
          if (item.getAttribute('href') === '#' + current) {
              item.classList.add('text-green-600');
          }
      });
  }

  window.addEventListener('scroll', highlightActiveNavItem);
});
</script>