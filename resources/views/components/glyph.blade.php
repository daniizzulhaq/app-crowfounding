@props(['name', 'filled' => false])
@php
    // SVG sources: Lucide (ISC/MIT). See public/icons/lucide/LICENSE.
    $icons = [
        'home' => 'house', 'gift' => 'gift', 'report' => 'file-chart-column',
        'heart' => 'heart', 'search' => 'search', 'back' => 'chevron-left',
        'chevron-right' => 'chevron-right', 'chevron-down' => 'chevron-down',
        'share' => 'share-2', 'clock' => 'clock-3', 'users' => 'users-round',
        'pin' => 'map-pin', 'calendar' => 'calendar-days', 'check' => 'check',
        'check-circle' => 'circle-check', 'x-circle' => 'circle-x',
        'alert' => 'circle-alert', 'hourglass' => 'hourglass', 'download' => 'download',
        'copy' => 'copy', 'user' => 'user-round', 'eye-off' => 'eye-off',
        'shield' => 'shield-check', 'wallet' => 'wallet', 'logout' => 'log-out',
        'refresh' => 'refresh-cw', 'sparkle' => 'sparkles', 'image' => 'image',
        'x' => 'x', 'phone' => 'phone', 'mail' => 'mail', 'book' => 'book-open',
        'education' => 'graduation-cap', 'hand-heart' => 'hand-heart',
        'health' => 'stethoscope', 'food' => 'utensils', 'grid' => 'grid-2x2',
        'handshake' => 'handshake', 'quote' => 'quote', 'sprout' => 'sprout',
        'heart-handshake' => 'heart-handshake', 'badge' => 'badge-check',
        'sliders' => 'sliders-horizontal', 'hand-coins' => 'hand-coins',
    ];
    $paths = '';
    $iconFile = isset($icons[$name]) ? public_path('icons/lucide/'.$icons[$name].'.svg') : null;
    if ($iconFile && is_file($iconFile)) {
        $svg = file_get_contents($iconFile);
        $paths = preg_replace('/^.*?<svg\\b[^>]*>|<\\/svg>\\s*$/s', '', $svg);
    }
    if ($name === 'alert' && !$paths) {
        $paths = '<circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/>';
    }
    if ($name === 'whatsapp') {
        $paths = '<path d="M4 20l1.3-3.9A8.5 8.5 0 1 1 8.6 19z"/><path d="M9 8.5c0 3.6 2.9 6.5 6.5 6.5l1-1.6-2.1-1-1 .9a4.4 4.4 0 0 1-2.7-2.7l.9-1-1-2.1z"/>';
    }
@endphp
<svg {{ $attributes->merge(['class' => 'glyph shrink-0', 'aria-hidden' => 'true', 'focusable' => 'false']) }}
     viewBox="0 0 24 24" fill="{{ $filled ? 'currentColor' : 'none' }}" stroke="currentColor"
     stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $paths !!}</svg>
