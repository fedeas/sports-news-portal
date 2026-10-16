@props([
    'title',
    'description' => null,
    'canonical' => null,
    'ogType' => 'website',
    'image' => null,
])

@php
    // Pass :canonical="false" for pages that must not declare one (e.g. error pages).
    $pageUrl = $canonical ?: url()->current();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>{{ $title }} | {{ config('app.name') }}</title>

        @if ($description)
            <meta name="description" content="{{ $description }}" />
        @endif

        @if ($canonical !== false)
            <link rel="canonical" href="{{ $pageUrl }}" />
        @endif

        <meta property="og:site_name" content="{{ config('app.name') }}" />
        <meta property="og:locale" content="{{ app()->getLocale() === 'el' ? 'el_GR' : 'en_US' }}" />
        <meta property="og:type" content="{{ $ogType }}" />
        <meta property="og:title" content="{{ $title }}" />
        <meta property="og:url" content="{{ $pageUrl }}" />
        @if ($description)
            <meta property="og:description" content="{{ $description }}" />
        @endif
        @if ($image)
            <meta property="og:image" content="{{ $image }}" />
        @endif

        <meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}" />
        <meta name="twitter:title" content="{{ $title }}" />
        @if ($description)
            <meta name="twitter:description" content="{{ $description }}" />
        @endif
        @if ($image)
            <meta name="twitter:image" content="{{ $image }}" />
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        <link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }}" href="{{ route('feed') }}" />

        @vite(['resources/css/app.css', 'resources/js/menu.js', 'resources/js/search.js'])
        @fluxAppearance(['nonce' => \Illuminate\Support\Facades\Vite::cspNonce()])

        {{ $head ?? '' }}
    </head>
    <body class="flex min-h-screen flex-col bg-zinc-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-brand focus:px-4 focus:py-2 focus:text-white"
        >
            {{ __('Skip to content') }}
        </a>

        @include('partials.site-header')

        <main id="main" class="flex-1">
            {{ $slot }}
        </main>

        @include('partials.site-footer')
    </body>
</html>
