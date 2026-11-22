@props(['number', 'title', 'step'])
{{-- Visual step header; turns into a check when the step is complete (reads Alpine `step`). --}}
<div class="mb-3 flex items-center gap-2.5">
    <span class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors"
          :class="step > {{ (int) $step }} ? 'bg-jade text-white' : 'bg-jade-soft text-jade'" aria-hidden="true">
        <span x-show="step <= {{ (int) $step }}">{{ $number }}</span>
        <svg x-show="step > {{ (int) $step }}" x-cloak class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
    </span>
    <h2 class="text-[15px] font-bold">{{ $title }}</h2>
</div>
