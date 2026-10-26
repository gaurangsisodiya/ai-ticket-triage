<x-layout title="Evaluation">
    <x-page-header title="Evaluation"
                   description="Seeded tickets carry a correct category and team decided by hand. Every ticket where the AI disagrees is listed here." />

    @php
        $figures = [
            ['label' => 'Category accuracy', 'value' => $evaluation['categoryAccuracy'] !== null ? "{$evaluation['categoryAccuracy']}%" : '—'],
            ['label' => 'Team accuracy', 'value' => $evaluation['teamAccuracy'] !== null ? "{$evaluation['teamAccuracy']}%" : '—'],
            ['label' => 'Labeled tickets analyzed', 'value' => "{$evaluation['analyzed']} of {$evaluation['labeled']}"],
            ['label' => 'Mismatches', 'value' => $evaluation['misses']->count()],
        ];
    @endphp

    {{-- Figures separated by hairline rules, as on the overview --}}
    <section aria-label="Evaluation figures" class="grid grid-cols-2 gap-x-8 border-y border-rule lg:grid-cols-4 lg:gap-x-0 lg:divide-x lg:divide-rule">
        @foreach ($figures as $figure)
            <div class="border-t border-rule py-5 first:border-t-0 lg:border-t-0 lg:px-5 lg:first:pl-0 [&:nth-child(2)]:border-t-0">
                <p class="eyebrow">{{ $figure['label'] }}</p>
                <p class="numeral mt-2 text-3xl text-ink">{{ $figure['value'] }}</p>
            </div>
        @endforeach
    </section>

    <section class="mt-12">
        <div class="border-b border-rule-strong pb-2">
            <h2 class="panel-title">Mismatches</h2>
        </div>
        <p class="mt-2 max-w-3xl text-sm text-ink-3">
            The wrong value is set in red and bold. AI team is the AI's own routing (its category and urgency through the routing
            rules), so a later re-route by an agent does not hide a mistake.
        </p>

        @if ($evaluation['labeled'] === 0)
            <p class="py-12 text-center text-base text-ink-3">
                No labeled tickets. Seed a demo dataset (<code class="font-mono text-sm">php artisan db:seed --class=GovTechDemoSeeder</code>) to get an evaluation set.
            </p>
        @elseif ($evaluation['analyzed'] === 0)
            <p class="py-12 text-center text-base text-ink-3">
                The labeled tickets haven't been analyzed yet. Run <code class="font-mono text-sm">php artisan tickets:queue-pending</code> and a queue worker, then come back.
            </p>
        @elseif ($evaluation['misses']->isEmpty())
            <p class="py-12 text-center text-base text-ink-3">Nothing to report: the AI matched every expected answer.</p>
        @else
            <div class="mt-3 overflow-x-auto border-b border-rule bg-surface">
                <table class="min-w-full">
                    <thead class="ledger-head bg-paper">
                        <tr>
                            <th class="th">Subject</th>
                            <th class="th">Expected category</th>
                            <th class="th">AI category</th>
                            <th class="th">Expected team</th>
                            <th class="th">AI team</th>
                            <th class="th text-right">Confidence</th>
                            <th class="th"><span class="sr-only">Link</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rule">
                        @foreach ($evaluation['misses'] as $miss)
                            <tr class="transition-colors hover:bg-sunk">
                                <td class="td max-w-[14rem]">
                                    <span class="block truncate font-semibold text-ink" title="{{ $miss['ticket']->subject }}">{{ $miss['ticket']->subject }}</span>
                                    <x-case-ref :ticket="$miss['ticket']" class="text-ink-3" />
                                </td>
                                <td class="td text-ink">{{ $miss['expected_category'] }}</td>
                                <td @class(['td', 'font-semibold text-seal' => ! $miss['category_ok'], 'text-ink-3' => $miss['category_ok']])>
                                    {{ $miss['ai_category'] }}@unless ($miss['category_ok'])<span class="sr-only"> (mismatch)</span>@endunless
                                </td>
                                <td class="td whitespace-nowrap text-ink">{{ $miss['expected_team'] }}</td>
                                <td @class(['td whitespace-nowrap', 'font-semibold text-seal' => ! $miss['team_ok'], 'text-ink-3' => $miss['team_ok']])>
                                    {{ $miss['ai_team'] }}@unless ($miss['team_ok'])<span class="sr-only"> (mismatch)</span>@endunless
                                </td>
                                <td class="td text-right font-mono text-sm tabular-nums text-ink-2">
                                    {{ $miss['ticket']->confidence !== null ? round($miss['ticket']->confidence * 100) . '%' : '—' }}
                                </td>
                                <td class="td text-right whitespace-nowrap">
                                    <a href="{{ route('tickets.show', $miss['ticket']) }}" class="text-sm font-semibold text-navy hover:underline">Open<span class="sr-only"> ticket {{ $miss['ticket']->subject }}</span></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <p class="mt-10 max-w-3xl border-t border-rule pt-4 text-sm text-ink-3">
        Only tickets that are labeled and whose analysis has finished are scored; tickets waiting for (re-)analysis are left out.
        Some labels are judgement calls (see the labeling rules in the seeders), so read each mismatch before changing the prompt.
    </p>
</x-layout>
