<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Application de gestion sportive">
        <meta name="theme-color" content="#fbbf24">

        <!-- PWA Meta Tags -->
        <link rel="manifest" href="{{ asset('manifest.json') }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Vespaket') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/icon-192.png') }}">
        <meta name="msapplication-TileColor" content="#fbbf24">
        <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">

        <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">

        <title>{{ config('app.name', 'vespaket') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pwa-register.js', 'resources/js/push.js'])
    </head>
    <body class="bg-yellow-200 text-gray-900 safe-area">
        <div class="min-h-screen">
            <livewire:layout.navigation />

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif
            <!-- Page Content -->
            <main class="p-1 sm:p-6">
                {{ $slot }}
            </main>
        </div>    
    </body>
</html>
