@props(['value'])

@php
    // Ticket status: a small glyph whose SHAPE differs per state, plus a text label,
    // so the state never depends on color alone.
    [$label, $color, $glyph] = match ($value) {
        'new' => ['New', 'text-slate', 'open'],
        'processing' => ['Analyzing', 'text-slate', 'half'],
        'auto_resolved' => ['Auto-resolved', 'text-ok', 'check'],
        'needs_review' => ['Needs review', 'text-review', 'flag'],
        'resolved' => ['Resolved', 'text-ok', 'check'],
        default => [ucfirst(str_replace('_', ' ', (string) $value)), 'text-ink-3', 'open'],
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 text-sm font-semibold whitespace-nowrap', $color]) }}>
    <svg viewBox="0 0 12 12" class="size-3 shrink-0" aria-hidden="true">
        @switch ($glyph)
            @case('check')
                <path d="M2 6.5l2.5 2.5L10 3.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="square"/>
                @break
            @case('flag')
                <rect x="1" y="1" width="10" height="10" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M6 3.5v3.2M6 8.2v.6" stroke="currentColor" stroke-width="1.6"/>
                @break
            @case('half')
                <circle cx="6" cy="6" r="4.5" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M6 1.5a4.5 4.5 0 010 9z" fill="currentColor"/>
                @break
            @default
                <circle cx="6" cy="6" r="4.5" fill="none" stroke="currentColor" stroke-width="1.5"/>
        @endswitch
    </svg>
    {{ $label }}
</span>
