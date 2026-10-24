@props(['value'])

{{-- Urgency as a 3-step signal-bar icon + label, the convention most issue trackers use. --}}
@php $level = ['low' => 1, 'medium' => 2, 'high' => 3][$value] ?? 0; @endphp

@if ($level)
    <span {{ $attributes->class(['inline-flex items-center gap-1.5 whitespace-nowrap text-[13px]', 'font-medium text-red-700' => $level === 3, 'text-zinc-700' => $level < 3]) }}>
        <svg viewBox="0 0 12 12" class="size-3 shrink-0" aria-hidden="true">
            @foreach ([1, 2, 3] as $i)
                <rect x="{{ ($i - 1) * 4 }}" y="{{ 12 - $i * 4 }}" width="3" height="{{ $i * 4 }}" rx="0.5"
                      class="{{ $i <= $level ? ($level === 3 ? 'fill-red-600' : 'fill-zinc-600') : 'fill-zinc-200' }}"/>
            @endforeach
        </svg>
        {{ ucfirst($value) }}
    </span>
@else
    <span class="text-zinc-300">—</span>
@endif
