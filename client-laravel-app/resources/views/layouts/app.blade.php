<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'AU VLP') }} - @yield('title', 'Home')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- AfricaVLP Custom Theme -->
    <link href="{{ asset('css/auvlp-theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/auvlp-overrides.css') }}" rel="stylesheet">

    <!-- Custom Styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50" x-data="{ mobileMenuOpen: false }">
    <div class="min-h-screen">
        <!-- Navigation -->
        <x-navbar />

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                <x-alert />

                @yield('content')
            </div>
        </main>
    </div>

    <!-- Footer -->
    <x-footer />

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
                    if (data.count > 0) {
                        notificationCount.textContent = data.count > 99 ? '99+' : data.count;
                        notificationCount.classList.remove('hidden');
                    } else {
                        notificationCount.classList.add('hidden');
                    }
                })
                .catch(error => console.error('Error fetching notification count:', error));

            // Update message count
            fetch('{{ route("api.messages.unread-count") }}')
                .then(response => response.json())
                .then(data => {
                    const messageCount = document.getElementById('message-count');
                    if (data.count > 0) {
                        messageCount.textContent = data.count > 99 ? '99+' : data.count;
                        messageCount.classList.remove('hidden');
                    } else {
                        messageCount.classList.add('hidden');
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
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>