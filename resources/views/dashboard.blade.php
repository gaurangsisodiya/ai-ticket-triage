<x-layout title="Overview">
    <x-page-header title="Overview" description="How the AI is triaging support tickets, and where humans had to step in.">
        <x-slot:actions>
            @if ($awaitingAnalysis > 0)
                <span class="inline-flex items-center gap-1.5 text-[13px] text-zinc-600">
                    <span class="size-2 animate-pulse rounded-full bg-sky-500"></span>
                    {{ $awaitingAnalysis }} awaiting analysis
                </span>
            @endif
            <a href="{{ route('tickets.create') }}" class="btn-primary">New ticket</a>
        </x-slot:actions>
    </x-page-header>

    {{-- Headline numbers: one strip of stat cells, not charts --}}
    @php
        $stats = [
            ['label' => 'AI resolution rate', 'value' => $resolutionRate !== null ? "{$resolutionRate}%" : '—',
             'note' => "{$resolvedByAi} of {$finished} processed tickets", 'href' => route('tickets.index', ['status' => 'auto_resolved'])],
            ['label' => 'Review queue', 'value' => $reviewQueue,
             'note' => 'waiting for an agent', 'href' => route('tickets.index', ['status' => 'needs_review'])],
            ['label' => 'Category accuracy', 'value' => $accuracy !== null ? "{$accuracy}%" : '—',
             'note' => "{$corrected} of {$aiCategorized} corrected by agents", 'href' => null],
            ['label' => 'LLM call success', 'value' => $aiCallSuccess !== null ? "{$aiCallSuccess}%" : '—',
             'note' => $aiCallsFailed === 0 ? 'no failed calls' : "{$aiCallsFailed} failed (errors, rate limits, invalid JSON)", 'href' => null],
        ];
    @endphp

    <div class="panel grid divide-y divide-zinc-200 sm:grid-cols-2 sm:divide-y-0 lg:grid-cols-4 lg:divide-x">
        @foreach ($stats as $i => $stat)
            @php
                $cell = Illuminate\Support\Arr::toCssClasses(['block px-5 py-4', 'hover:bg-zinc-50' => $stat['href'],
                    'sm:border-t sm:border-zinc-200 lg:border-t-0' => $i >= 2, 'sm:border-l sm:border-zinc-200 lg:border-l-0' => $i % 2 === 1]);
            @endphp
            @if ($stat['href']) <a href="{{ $stat['href'] }}" class="{{ $cell }}"> @else <div class="{{ $cell }}"> @endif
                <p class="text-[13px] text-zinc-500">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums text-zinc-900">{{ $stat['value'] }}</p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ $stat['note'] }}</p>
            @if ($stat['href']) </a> @else </div> @endif
        @endforeach
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <x-bar-list title="Tickets by category" :data="$byCategory" />
        <x-bar-list title="Tickets by team" :data="$byTeam" :link="fn ($team) => route('tickets.index', ['team' => $team])" />
    </div>

    <section class="panel mt-4">
        <div class="panel-header">
            <div>
                <h2 class="panel-title">Category corrections</h2>
                <p class="mt-0.5 text-xs text-zinc-500">Where an agent overruled the AI. Pairs that repeat point to category descriptions in the prompt that need work.</p>
            </div>
        </div>

        @if ($topCorrections->isEmpty())
            <p class="px-4 py-6 text-[13px] text-zinc-500">No corrections yet. Agents can change a ticket's category from its detail page.</p>
        @else
            <table class="min-w-full">
                <thead class="border-b border-zinc-200 bg-zinc-50/60">
                    <tr><th class="th">AI category</th><th class="th">Corrected to</th><th class="th text-right">Tickets</th></tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-[13px]">
                    @foreach ($topCorrections as $row)
                        <tr>
                            <td class="td text-zinc-500">{{ $row['ai'] }}</td>
                            <td class="td font-medium text-zinc-900">{{ $row['human'] }}</td>
                            <td class="td text-right tabular-nums">{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <p class="mt-3 text-xs text-zinc-400">
        Category accuracy counts uncorrected tickets as correct, so it is an upper bound. It becomes more reliable as more tickets are reviewed.
    </p>
</x-layout>
