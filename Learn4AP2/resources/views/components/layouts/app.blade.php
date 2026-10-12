<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <title>{{ isset($title) ? "{$title} | " : '' }}{{ config('app.name', 'AP2-Karteikarten') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body>
        <div class="app-shell">
            <header class="site-header">
                <a class="site-brand" href="/" aria-label="{{ config('app.name', 'AP2-Karteikarten') }} – Dashboard">
                    {{ config('app.name', 'AP2-Karteikarten') }}
                </a>
                <nav class="site-nav" aria-label="Hauptnavigation">
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <a href="{{ route('study.start') }}">Lernen</a>
                    <a href="/decks">Decks</a>
                    <a href="/cards">Karten</a>
                    <a href="/import">Import</a>
                </nav>
            </header>
            <main id="main-content" class="site-main">
                {{ $slot }}
            </main>
        </div>
        @livewireScripts
    </body>
</html>
