@props(['title', 'data', 'unit' => 'ticket', 'link' => null])

{{--
    Ledger of counts (label => number): label, dotted leader, count, and a thin bar underneath.
    One hue; values in ink, not bar color. $link: optional fn(string $label): string.
--}}
@php $max = max(1, $data->max() ?? 0); $sum = max(1, $data->sum()); @endphp

<section>
    <div class="flex items-baseline justify-between border-b border-rule-strong pb-2">
        <h2 class="panel-title">{{ $title }}</h2>
        <span class="ref">{{ $data->sum() }} {{ Str::plural($unit, $data->sum()) }}</span>
    </div>

    @if ($data->isEmpty())
        <p class="py-6 text-base text-ink-3">No analyzed tickets yet. Counts appear here as the AI works through the queue.</p>
    @else
        <ul class="divide-y divide-rule">
            @foreach ($data as $label => $value)
                @php $share = round($value / $sum * 100); @endphp
                <li title="{{ $label }}: {{ $value }} {{ Str::plural($unit, $value) }} ({{ $share }}%)">
                    @php $row = 'group block py-2.5' ; @endphp
                    @if ($link) <a href="{{ $link($label) }}" class="{{ $row }}"> @else <div class="{{ $row }}"> @endif
                        <span class="flex items-baseline">
                            <span @class(['text-base text-ink', 'group-hover:underline' => $link])>{{ $label }}</span>
                            <span class="leader" aria-hidden="true"></span>
                            <span class="font-mono text-sm tabular-nums text-ink">{{ $value }}</span>
                        </span>
                        <span class="mt-1.5 block h-[3px] bg-sunk" aria-hidden="true">
                            <span class="block h-[3px] bg-navy" style="width: {{ $value / $max * 100 }}%"></span>
                        </span>
                    @if ($link) </a> @else </div> @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
