@props(['icon' => 'sparkle', 'title', 'text' => null])
<div {{ $attributes->class('flex flex-col items-center px-6 py-10 text-center') }}>
    <div class="mb-3 flex size-14 items-center justify-center rounded-2xl bg-jade-soft text-jade">
        <x-glyph :name="$icon" class="size-7" />
    </div>
    <p class="text-[15px] font-bold text-ink">{{ $title }}</p>
    @if ($text)
        <p class="mt-1 max-w-72 text-sm text-stone">{{ $text }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
