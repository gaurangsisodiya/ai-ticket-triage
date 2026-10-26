<x-layout title="Overview">
    @php
        // "X of Y analyzed": accuracy only counts labeled tickets whose analysis has finished.
        $evalNote = $evaluation['labeled'] === 0
            ? 'No labeled tickets'
            : "{$evaluation['analyzed']} of {$evaluation['labeled']} analyzed";
        $figures = [
            ['label' => 'AI resolution rate', 'value' => $resolutionRate !== null ? "{$resolutionRate}%" : '—',
             'note' => "{$resolvedByAi} of {$finished} processed tickets", 'href' => route('tickets.index', ['status' => 'auto_resolved'])],
            ['label' => 'Review queue', 'value' => $reviewQueue,
             'note' => 'waiting for an agent', 'href' => route('tickets.index', ['status' => 'needs_review'])],
            ['label' => 'Category accuracy', 'value' => $evaluation['categoryAccuracy'] !== null ? "{$evaluation['categoryAccuracy']}%" : '—',
             'note' => $evalNote, 'href' => route('evaluation')],
            ['label' => 'Team accuracy', 'value' => $evaluation['teamAccuracy'] !== null ? "{$evaluation['teamAccuracy']}%" : '—',
             'note' => $evalNote, 'href' => route('evaluation')],
            ['label' => 'LLM call success', 'value' => $aiCallSuccess !== null ? "{$aiCallSuccess}%" : '—',
             'note' => $aiCallsFailed === 0 ? 'no failed calls' : "{$aiCallsFailed} failed (errors, rate limits, invalid JSON)", 'href' => null],
        ];
    @endphp

    {{-- Masthead of the daily desk report --}}
    <header class="mb-10">
        <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 border-b border-rule pb-2">
            <p class="eyebrow">Support Desk · Daily report</p>
            <p class="ref">{{ now()->format('l, j F Y') }}</p>
        </div>
        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4 border-b-4 border-double border-rule-strong pt-5 pb-5">
            <div class="max-w-3xl">
                <h1 class="font-serif text-2xl font-semibold text-ink">Overview</h1>
                <p class="mt-2 text-base text-ink-2">How the AI is triaging support tickets, and where people had to step in.</p>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                @if ($awaitingAnalysis > 0)
                    <span class="text-sm text-slate">{{ $awaitingAnalysis }} {{ Str::plural('ticket', $awaitingAnalysis) }} awaiting analysis</span>
                @endif
                <a href="{{ route('tickets.create') }}" class="btn-primary">New ticket</a>
            </div>
        </div>
    </header>

    {{-- Headline figures: big serif numerals separated by hairline rules, not boxed cards --}}
    <section aria-label="Key figures" class="grid gap-x-8 border-b border-rule sm:grid-cols-2 lg:grid-cols-5 lg:gap-x-0 lg:divide-x lg:divide-rule">
        @foreach ($figures as $figure)
            @php
                $cell = Illuminate\Support\Arr::toCssClasses(['group block border-t border-rule py-5 lg:border-t-0 lg:px-5 lg:first:pl-0',
                    'sm:col-span-2 lg:col-span-1' => $loop->last]);
            @endphp
            @if ($figure['href']) <a href="{{ $figure['href'] }}" class="{{ $cell }}"> @else <div class="{{ $cell }}"> @endif
                <p class="eyebrow">{{ $figure['label'] }}</p>
                <p @class(['numeral mt-2 text-4xl text-ink transition-colors', 'group-hover:text-navy' => $figure['href']])>{{ $figure['value'] }}</p>
                <p @class(['mt-2 text-sm text-ink-3', 'group-hover:underline' => $figure['href']])>{{ $figure['note'] }}</p>
            @if ($figure['href']) </a> @else </div> @endif
        @endforeach
    </section>

    <div class="mt-12 grid gap-x-12 gap-y-10 lg:grid-cols-2">
        <x-bar-list title="Tickets by category" :data="$byCategory" />
        <x-bar-list title="Tickets by team" :data="$byTeam" :link="fn ($team) => route('tickets.index', ['team' => $team])" />
    </div>

    <section class="mt-12">
        <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1 border-b border-rule-strong pb-2">
            <h2 class="panel-title">Category corrections</h2>
            <span class="ref">{{ $corrected }} of {{ $aiCategorized }} corrected</span>
        </div>
        <p class="mt-2 text-sm text-ink-3">Where an agent overruled the AI on live tickets. Pairs that repeat point to category descriptions in the prompt that need work.</p>

        @if ($topCorrections->isEmpty())
            <p class="py-6 text-base text-ink-3">No corrections yet. When an agent changes a ticket's category on its page, the change is recorded here.</p>
        @else
            <table class="mt-3 min-w-full">
                <thead class="ledger-head">
                    <tr><th class="th pl-0">AI category</th><th class="th">Corrected to</th><th class="th pr-0 text-right">Tickets</th></tr>
                </thead>
                <tbody class="divide-y divide-rule">
                    @foreach ($topCorrections as $row)
                        <tr>
                            <td class="td pl-0 text-ink-3 line-through decoration-rule-strong">{{ $row['ai'] }}</td>
                            <td class="td font-semibold text-ink">{{ $row['human'] }}</td>
                            <td class="td pr-0 text-right font-mono tabular-nums">{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <p class="mt-10 max-w-3xl border-t border-rule pt-4 text-sm text-ink-3">
        Accuracy is measured on the hand-labeled evaluation set (see Evaluation for every miss). Agent corrections cover live tickets, which have no labels;
        an uncorrected live ticket is not proof the AI was right.
    </p>
</x-layout>
