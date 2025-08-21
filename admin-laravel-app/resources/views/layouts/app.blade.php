<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'AU VLP Admin') }} - @yield('title', 'Dashboard')</title>

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
<body class="font-sans antialiased bg-gray-100" x-data="{ mobileMenuOpen: false }">
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

    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Scripts -->
    @stack('scripts')
</body>
</html>