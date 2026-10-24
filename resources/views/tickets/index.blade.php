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
    <div class="flex flex-wrap gap-x-5 border-b border-zinc-200">
        @foreach ($statusTabs as $value => $label)
            @php $active = ($currentStatus ?: null) === ($value ?: null); @endphp
            <a href="{{ route('tickets.index', array_filter([...$filters, 'status' => $value])) }}"
               @class(['-mb-px shrink-0 border-b-2 pb-2.5 text-[13px]',
                       'border-zinc-900 font-medium text-zinc-900' => $active,
                       'border-transparent text-zinc-500 hover:text-zinc-800' => ! $active])>
                {{ $label }}
                @if ($value === 'needs_review' && $reviewCount)
                    <span class="ml-1 rounded bg-amber-100 px-1.5 text-xs tabular-nums text-amber-800">{{ $reviewCount }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('tickets.index') }}" class="flex flex-wrap items-center gap-2 py-3">
        @if ($currentStatus) <input type="hidden" name="status" value="{{ $currentStatus }}"> @endif

        <div class="relative w-full sm:w-64">
            <svg viewBox="0 0 20 20" class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-zinc-400" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.45 4.39l3.08 3.08a.75.75 0 11-1.06 1.06l-3.08-3.08A7 7 0 012 9z" clip-rule="evenodd"/>
            </svg>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search subject, email or summary" aria-label="Search"
                   class="field py-1.5 pl-8 text-[13px]">
        </div>

        @php
            $selects = [
                'category' => ['Category', $categories->map->name->all()],
                'team' => ['Team', $teams->combine($teams)->all()],
                'urgency' => ['Priority', collect(App\Models\Ticket::URGENCIES)->mapWithKeys(fn ($u) => [$u => ucfirst($u)])->all()],
            ];
        @endphp
        @foreach ($selects as $name => [$placeholder, $options])
            <select name="{{ $name }}" aria-label="{{ $placeholder }}" class="field w-auto py-1.5 pr-8 text-[13px]">
                <option value="">{{ $placeholder }}: any</option>
                @foreach ($options as $value => $label)
                    <option value="{{ $value }}" @selected(($filters[$name] ?? '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        @endforeach

        <button class="btn-secondary">Apply</button>
        @if (array_filter(\Illuminate\Support\Arr::except($filters, 'status')))
            <a href="{{ route('tickets.index', array_filter(['status' => $currentStatus])) }}" class="btn-ghost">Clear filters</a>
        @endif
        <span class="ml-auto text-xs tabular-nums text-zinc-500">{{ $tickets->total() }} {{ Str::plural('ticket', $tickets->total()) }}</span>
    </form>

    <div class="panel overflow-x-auto">
        <table class="min-w-full">
            <thead class="border-b border-zinc-200 bg-zinc-50/60">
                <tr>
                    <th class="th w-24">ID</th>
                    <th class="th">Subject</th>
                    <th class="th">Status</th>
                    <th class="th">Category</th>
                    <th class="th">Priority</th>
                    <th class="th">Team</th>
                    <th class="th text-right" title="AI confidence in its knowledge base match">Confidence</th>
                    <th class="th text-right">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 text-[13px]">
                @forelse ($tickets as $ticket)
                    {{-- The subject link is stretched over the whole row, so any click on the row opens the ticket --}}
                    <tr class="relative hover:bg-zinc-50">
                        <td class="td font-mono text-xs whitespace-nowrap text-zinc-500">{{ $ticket->reference() }}</td>
                        <td class="td max-w-sm">
                            <a href="{{ route('tickets.show', $ticket) }}" class="block truncate font-medium text-zinc-900 after:absolute after:inset-0">
                                {{ $ticket->subject }}
                            </a>
                            <p class="truncate text-xs text-zinc-500">
                                {{ $ticket->customer_email }}
                                @if ($ticket->sentiment === 'angry') · <span class="text-red-600">angry</span>
                                @elseif ($ticket->sentiment === 'negative') · <span class="text-orange-600">frustrated</span> @endif
                            </p>
                        </td>
                        <td class="td"><x-status :value="$ticket->status" /></td>
                        <td class="td whitespace-nowrap text-zinc-700">
                            @if ($slug = $ticket->effectiveCategory())
                                {{ $categories[$slug]->name ?? $slug }}
                                @if ($ticket->corrected_category)
                                    <span title="Corrected by an agent (AI said: {{ $categories[$ticket->category]->name ?? $ticket->category ?? 'nothing' }})"
                                          class="relative z-10 ml-1 rounded border border-zinc-200 px-1 text-[11px] text-zinc-500">edited</span>
                                @endif
                            @else
                                <span class="text-zinc-300">—</span>
                            @endif
                        </td>
                        <td class="td"><x-priority :value="$ticket->urgency" /></td>
                        <td class="td whitespace-nowrap text-zinc-700">{{ $ticket->assigned_team ?? '—' }}</td>
                        <td class="td text-right tabular-nums">
                            @if ($ticket->confidence !== null)
                                <span class="{{ $ticket->confidence >= $threshold ? 'text-zinc-900' : 'text-zinc-400' }}">{{ round($ticket->confidence * 100) }}%</span>
                            @else
                                <span class="text-zinc-300">—</span>
                            @endif
                        </td>
                        <td class="td whitespace-nowrap text-right text-zinc-500" title="{{ $ticket->created_at->format('d M Y, H:i') }}">
                            {{ $ticket->created_at->diffForHumans(short: true) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-[13px] text-zinc-500">No tickets match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $tickets->links() }}</div>
</x-layout>
