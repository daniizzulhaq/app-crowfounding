@props(['value' => 0, 'label' => 'Progres dana terkumpul', 'size' => 'md'])
@php $value = max(0, min(100, (int) $value)); @endphp
<div role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $value }}"
     {{ $attributes->class(['w-full overflow-hidden rounded-full bg-marigold-soft', 'h-1.5' => $size === 'sm', 'h-2' => $size === 'md', 'h-2.5' => $size === 'lg']) }}>
    <div class="h-full rounded-full bg-marigold" style="width: {{ $value === 0 ? 0 : max(2, $value) }}%"></div>
</div>
