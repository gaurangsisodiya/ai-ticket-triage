@php
    $currentStatus = $filters['status'] ?? null;
    $statusTabs = [null => 'All', 'needs_review' => 'Needs review', 'auto_resolved' => 'Auto-resolved', 'resolved' => 'Resolved'];
    $threshold = config('ai.auto_resolve_threshold');
@endphp

<x-layout :title="$currentStatus === 'needs_review' ? 'Review queue' : 'Tickets'">
    <x-page-header :title="$currentStatus === 'needs_review' ? 'Review queue' : 'Tickets'"
                   :description="$currentStatus === 'needs_review'
                        ? 'Tickets the AI could not resolve on its own. Most include a suggested reply.'
                        : 'Every ticket with its AI triage: category, priority, team and confidence.'">
        <x-slot:actions>
            <a href="{{ route('tickets.create') }}" class="btn-primary">New ticket</a>
        </x-slot:actions>
    </x-page-header>

    {{-- Status tabs keep the other filters; the filter form is a plain GET form, so every view has a shareable URL. --}}
    <nav aria-label="Filter by status" class="flex flex-wrap gap-x-7 border-b border-rule">
        @foreach ($statusTabs as $value => $label)
            @php $active = ($currentStatus ?: null) === ($value ?: null); @endphp
            <a href="{{ route('tickets.index', array_filter([...$filters, 'status' => $value])) }}"
               @if ($active) aria-current="page" @endif
               @class(['-mb-px border-b-2 pb-3 text-base transition-colors',
                       'border-navy font-semibold text-ink' => $active,
                       'border-transparent text-ink-2 hover:text-ink hover:underline' => ! $active])>
                {{ $label }}
                @if ($value === 'needs_review' && $reviewCount)
                    <span class="ref ml-1 text-review">({{ $reviewCount }})</span>
                @endif
            </a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('tickets.index') }}" class="flex flex-wrap items-end gap-3 py-5">
        @if ($currentStatus) <input type="hidden" name="status" value="{{ $currentStatus }}"> @endif

        <div class="w-full sm:w-72">
            <label for="q" class="eyebrow mb-1.5 block">Search</label>
            <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Subject, email or summary" class="field h-10 py-0">
        </div>

        @php
            $selects = [
                'category' => ['Category', $categories->map->name->all()],
                'team' => ['Team', $teams->combine($teams)->all()],
                'urgency' => ['Priority', collect(App\Models\Ticket::URGENCIES)->mapWithKeys(fn ($u) => [$u => ucfirst($u)])->all()],
            ];
        @endphp
        @foreach ($selects as $name => [$placeholder, $options])
            <div>
                <label for="filter-{{ $name }}" class="eyebrow mb-1.5 block">{{ $placeholder }}</label>
                <select id="filter-{{ $name }}" name="{{ $name }}" class="field h-10 w-auto py-0 pr-9">
                    <option value="">Any</option>
                    @foreach ($options as $value => $label)
                        <option value="{{ $value }}" @selected(($filters[$name] ?? '') === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        @endforeach

        <button class="btn-secondary h-10">Apply</button>
        @if (array_filter(\Illuminate\Support\Arr::except($filters, 'status')))
            <a href="{{ route('tickets.index', array_filter(['status' => $currentStatus])) }}" class="btn-ghost">Clear filters</a>
        @endif
        <span class="ref ml-auto self-center">{{ $tickets->total() }} {{ Str::plural('ticket', $tickets->total()) }}</span>
    </form>

    {{-- Ledger: strong rule under the header, hairlines between rows, generous row height --}}
    <div class="overflow-x-auto border-b border-rule bg-surface">
        <table class="min-w-full">
            <thead class="ledger-head bg-paper">
                <tr>
                    <th class="th w-28">Case no.</th>
                    <th class="th">Subject</th>
                    <th class="th">Status</th>
                    <th class="th">Category</th>
                    <th class="th">Priority</th>
                    <th class="th">Team</th>
                    <th class="th text-right" title="AI confidence in its knowledge base match">Confidence</th>
                    <th class="th text-right">Received</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-rule">
                @forelse ($tickets as $ticket)
                    {{-- The subject link is stretched over the whole row, so any click on the row opens the ticket --}}
                    <tr class="relative transition-colors hover:bg-sunk">
                        <td class="td whitespace-nowrap"><x-case-ref :ticket="$ticket" /></td>
                        <td class="td max-w-[16rem]">
                            <a href="{{ route('tickets.show', $ticket) }}" class="block truncate font-semibold text-ink after:absolute after:inset-0 hover:underline">
                                {{ $ticket->subject }}
                            </a>
                            <p class="truncate text-sm text-ink-3">
                                {{ $ticket->customer_email }}
                                @if ($ticket->sentiment === 'angry') · <span class="font-semibold text-seal">angry</span>
                                @elseif ($ticket->sentiment === 'negative') · <span class="text-review">frustrated</span> @endif
                            </p>
                        </td>
                        <td class="td"><x-status :value="$ticket->status" /></td>
                        <td class="td text-ink-2">
                            @if ($slug = $ticket->effectiveCategory())
                                {{ $categories[$slug]->name ?? $slug }}
                                @if ($ticket->corrected_category)
                                    <span title="Corrected by an agent (AI said: {{ $categories[$ticket->category]->name ?? $ticket->category ?? 'nothing' }})"
                                          class="tag relative z-10 ml-1 border-rule-strong text-ink-3">edited</span>
                                @endif
                            @else
                                <span class="text-ink-3">—</span>
                            @endif
                        </td>
                        <td class="td"><x-priority :value="$ticket->urgency" /></td>
                        <td class="td whitespace-nowrap text-ink-2">{{ $ticket->assigned_team ?? '—' }}</td>
                        <td class="td text-right font-mono text-sm tabular-nums">
                            @if ($ticket->confidence !== null)
                                <span class="{{ $ticket->confidence >= $threshold ? 'text-ink' : 'text-ink-3' }}">{{ round($ticket->confidence * 100) }}%</span>
                            @else
                                <span class="text-ink-3">—</span>
                            @endif
                        </td>
                        <td class="td text-right whitespace-nowrap" title="{{ $ticket->created_at->format('d M Y, H:i') }}">
                            <span class="ref text-ink-3">{{ $ticket->created_at->diffForHumans(short: true) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-16 text-center text-base text-ink-3">
                            No tickets match these filters. Try clearing them, or pick another status above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $tickets->links() }}</div>
</x-layout>
