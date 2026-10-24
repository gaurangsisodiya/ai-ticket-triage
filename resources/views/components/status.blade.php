@props(['value'])

@php
    // Ticket status as a colored dot + plain label (color is never the only signal).
    [$label, $dot] = match ($value) {
        'new' => ['New', 'bg-zinc-400'],
        'processing' => ['Analyzing', 'bg-sky-500 animate-pulse'],
        'auto_resolved' => ['Auto-resolved', 'bg-emerald-500'],
        'needs_review' => ['Needs review', 'bg-amber-500'],
        'resolved' => ['Resolved', 'bg-zinc-700'],
        default => [ucfirst(str_replace('_', ' ', (string) $value)), 'bg-zinc-300'],
    };
@endphp

<span {{ $attributes->class('inline-flex items-center gap-1.5 whitespace-nowrap text-[13px] text-zinc-700') }}>
    <span class="size-2 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>{{ $label }}
</span>
