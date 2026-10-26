@props(['value'])

{{-- Urgency as a 3-step bar glyph + label. High is set in seal red and bold. --}}
@php $level = ['low' => 1, 'medium' => 2, 'high' => 3][$value] ?? 0; @endphp

@if ($level)
    <span {{ $attributes->class(['inline-flex items-center gap-1.5 text-sm whitespace-nowrap',
                                 'font-semibold text-seal' => $level === 3, 'text-ink-2' => $level === 2, 'text-ink-3' => $level === 1]) }}>
        <svg viewBox="0 0 12 12" class="size-3 shrink-0" aria-hidden="true">
            @foreach ([1, 2, 3] as $i)
                <rect x="{{ ($i - 1) * 4 }}" y="{{ 12 - $i * 4 }}" width="3" height="{{ $i * 4 }}"
                      fill="currentColor" opacity="{{ $i <= $level ? 1 : 0.22 }}"/>
            @endforeach
        </svg>
        {{ ucfirst($value) }}
    </span>
@else
    <span class="text-ink-3" aria-label="Not set">—</span>
@endif
