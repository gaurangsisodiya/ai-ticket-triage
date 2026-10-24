@props(['title', 'data', 'unit' => 'ticket', 'link' => null])

{{--
    Horizontal bar list for one series of counts (label => number).
    One hue, no legend (the title names the series), values in text color, not bar color.
    $link: optional fn(string $label): string, to make each row open the filtered ticket list.
--}}
@php $max = max(1, $data->max() ?? 0); $sum = max(1, $data->sum()); @endphp

<section class="panel">
    <div class="panel-header">
        <h2 class="panel-title">{{ $title }}</h2>
        <span class="text-xs text-zinc-500 tabular-nums">{{ $data->sum() }} {{ Str::plural($unit, $data->sum()) }}</span>
    </div>

    @if ($data->isEmpty())
        <p class="px-4 py-6 text-[13px] text-zinc-500">No analyzed tickets yet.</p>
    @else
        <ul class="px-4 py-3">
            @foreach ($data as $label => $value)
                @php $share = round($value / $sum * 100); @endphp
                {{-- The whole row is the hover target (bigger than the thin bar), with a native tooltip --}}
                <li title="{{ $label }}: {{ $value }} {{ Str::plural($unit, $value) }} ({{ $share }}%)">
                    @php $row = 'group -mx-2 grid grid-cols-[minmax(0,10rem)_1fr_2.5rem] items-center gap-3 rounded px-2 py-1.5' . ($link ? ' hover:bg-zinc-50' : ''); @endphp
                    @if ($link) <a href="{{ $link($label) }}" class="{{ $row }}"> @else <div class="{{ $row }}"> @endif
                        <span class="truncate text-[13px] text-zinc-700">{{ $label }}</span>
                        <span class="h-1.5 rounded-full bg-zinc-100">
                            <span class="block h-1.5 rounded-full bg-brand-500 group-hover:bg-brand-700" style="width: {{ $value / $max * 100 }}%"></span>
                        </span>
                        <span class="text-right text-[13px] font-medium tabular-nums text-zinc-900">{{ $value }}</span>
                    @if ($link) </a> @else </div> @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
