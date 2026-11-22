@props(['status'])
@php
    $classes = match ($status) {
        \App\Enums\DonationStatus::Paid => 'bg-jade-soft text-jade',
        \App\Enums\DonationStatus::Pending => 'bg-marigold-soft text-ink',
        \App\Enums\DonationStatus::Failed => 'bg-danger-soft text-danger',
        \App\Enums\DonationStatus::Expired => 'bg-paper text-stone',
    };
@endphp
<span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $classes }}">{{ $status->getLabel() }}</span>
