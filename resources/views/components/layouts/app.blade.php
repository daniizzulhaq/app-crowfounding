@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'nav' => true,
    'active' => null,
    'header' => 'app',      // app | back | none
    'back' => null,         // fallback url for back button
    'heading' => null,      // title text for the "back" header
    'search' => '',
    'noindex' => false,
])
@php
    $brand = $settings->foundationName();
    $fullTitle = $title ? $title.' · '.$brand : $brand.' — '.$settings->get('tagline');
    $metaDescription = \Illuminate\Support\Str::limit(strip_tags($description ?? $settings->get('about')), 160);
    $ogImage = $image ?? asset('images/og-default.png');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#075E46">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @if ($noindex)
        <meta name="robots" content="noindex, nofollow">
    @endif

    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:title" content="{{ $title ?? $brand }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="{{ $settings->logoUrl() }}">
    <link rel="apple-touch-icon" href="{{ $settings->logoUrl() }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="min-h-dvh">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Lewati ke konten</a>

    <div class="app-shell relative mx-auto flex min-h-dvh w-full max-w-[480px] flex-col bg-paper">
        @if ($header === 'app')
            <x-app-header :search="$search" />
        @elseif ($header === 'back')
            <header class="back-header sticky top-0 z-30 flex h-17 items-center gap-1 px-3 backdrop-blur">
                <x-back-button :fallback="$back" />
                <p class="min-w-0 flex-1 truncate text-base font-bold">{{ $heading ?? $title }}</p>
                <div class="flex shrink-0 items-center">
                    {{ $actions ?? '' }}
                    <x-home-button />
                </div>
            </header>
        @endif

        <main id="konten" @class(['flex-1', 'pb-28' => isset($sticky), 'pb-24' => $nav && ! isset($sticky)])>
            {{ $slot }}
        </main>

        @isset($sticky)
            <div class="sticky-action fixed inset-x-0 bottom-0 z-40 mx-auto w-full max-w-[480px] bg-white px-5 pt-4 pb-safe shadow-[var(--shadow-bar)]">
                {{ $sticky }}
            </div>
        @elseif ($nav)
            <x-bottom-nav :active="$active" />
        @endisset
    </div>

    {{ $scripts ?? '' }}
</body>
</html>
