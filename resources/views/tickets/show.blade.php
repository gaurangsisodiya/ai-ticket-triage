@php
    $caseNo = 'CASE-' . str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT);
@endphp

<x-layout :title="$caseNo">
    {{-- While the AI is working, reload every 5 seconds. No JavaScript needed. --}}
    @if ($ticket->isAwaitingAnalysis())
        @push('head') <meta http-equiv="refresh" content="5"> @endpush
    @endif

    @php
        $threshold = config('ai.auto_resolve_threshold');
        $categoryName = fn (?string $slug) => $slug ? ($categories[$slug]->name ?? $slug) : null;
        $usualTeam = $categories->get($ticket->effectiveCategory() ?? '')?->team->name;
        $escalated = $usualTeam && $ticket->assigned_team && $usualTeam !== $ticket->assigned_team;

        // The one place the status appears on this page: as a rubber stamp.
        [$stampLabel, $stampColor] = match ($ticket->status) {
            'new' => ['New', 'text-slate'],
            'processing' => ['Analyzing', 'text-slate'],
            'auto_resolved' => ['Auto-resolved', 'text-ok'],
            'needs_review' => ['Needs review', 'text-review'],
            'resolved' => ['Resolved', 'text-ok'],
            default => [ucfirst(str_replace('_', ' ', $ticket->status)), 'text-ink-2'],
        };
    @endphp

    {{-- Case heading --}}
    <header class="mb-8 border-b border-rule-strong pb-6">
        <div class="mb-3 text-sm text-ink-3">
            <a href="{{ route('tickets.index') }}" class="hover:text-ink hover:underline">Tickets</a>
            <span class="mx-1.5" aria-hidden="true">/</span>
            <x-case-ref :ticket="$ticket" />
        </div>
        <div class="flex flex-wrap items-start justify-between gap-x-8 gap-y-5">
            <div class="min-w-0 max-w-3xl">
                <p class="ref">{{ $caseNo }} · received {{ $ticket->created_at->format('d M Y, H:i') }}</p>
                <h1 class="mt-2 font-serif text-2xl font-semibold break-words text-ink">{{ $ticket->subject }}</h1>
            </div>
            <p class="stamp mt-1 shrink-0 {{ $stampColor }}"><span class="sr-only">Status: </span>{{ $stampLabel }}</p>
        </div>

        @unless ($ticket->isAwaitingAnalysis())
            <div class="mt-5 flex flex-wrap gap-3">
                @if ($ticket->status === 'needs_review')
                    <form method="POST" action="{{ route('tickets.resolve', $ticket) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn-primary">Mark as resolved</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('tickets.reanalyze', $ticket) }}">
                    @csrf
                    <button class="btn-secondary">Re-run analysis</button>
                </form>
            </div>
        @endunless
    </header>

    {{-- Why the ticket is in this state, in one sentence --}}
    <div class="mb-10 border-l-4 border-navy bg-surface px-5 py-4">
        <p class="eyebrow text-navy">Triage decision</p>
        <p class="mt-1 text-base text-ink">{{ $ticket->decisionReason() }}</p>
    </div>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_19rem]">
        <div class="min-w-0 space-y-8">
            {{-- The customer's message, as filed --}}
            <article class="panel">
                <header class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-rule px-6 py-4">
                    <div class="min-w-0">
                        <p class="eyebrow">Customer's message</p>
                        <p class="mt-1 truncate font-semibold text-ink">{{ $ticket->customer_email }}</p>
                    </div>
                    <p class="ref">{{ $ticket->created_at->format('d M Y, H:i') }}</p>
                </header>
                <p class="px-6 py-5 text-base leading-relaxed break-words whitespace-pre-line text-ink">{{ $ticket->body }}</p>
            </article>

            {{-- The AI's reply, set out as an internal memo --}}
            @if ($ticket->ai_reply)
                @php $sent = $ticket->status === 'auto_resolved'; @endphp
                <article @class(['panel', 'border-dashed border-field' => ! $sent])>
                    <header class="border-b border-rule px-6 py-4">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2">
                            <h2 class="panel-title">{{ $sent ? 'Reply sent to customer' : 'Suggested reply' }}</h2>
                            <span @class(['tag', 'border-ok text-ok' => $sent, 'border-dashed border-review text-review' => ! $sent])>
                                {{ $sent ? 'Sent automatically' : 'Draft · not sent' }}
                            </span>
                        </div>
                        <dl class="mt-3 grid grid-cols-[4.5rem_1fr] gap-x-3 gap-y-1 text-sm">
                            <dt class="eyebrow pt-0.5">From</dt><dd class="text-ink">AI assistant</dd>
                            <dt class="eyebrow pt-0.5">To</dt><dd class="break-all text-ink">{{ $ticket->customer_email }}</dd>
                            <dt class="eyebrow pt-0.5">Re</dt><dd class="text-ink">{{ $ticket->subject }}</dd>
                            <dt class="eyebrow pt-0.5">Source</dt>
                            <dd class="text-ink">{{ $ticket->kbArticle ? 'KB: ' . $ticket->kbArticle->title : 'No knowledge base article' }}</dd>
                        </dl>
                    </header>
                    <p class="px-6 py-5 text-base leading-relaxed whitespace-pre-line text-ink">{{ $ticket->ai_reply }}</p>
                </article>
            @elseif ($ticket->isAwaitingAnalysis())
                <div class="border-l-4 border-slate bg-slate-tint px-5 py-4 text-base text-ink">
                    The AI is reading this ticket now. This page refreshes on its own every few seconds.
                </div>
            @endif

            {{-- Audit trail: every LLM call for this ticket, as a typed record --}}
            <section>
                <div class="flex items-baseline justify-between border-b border-rule-strong pb-2">
                    <h2 class="panel-title">Audit trail</h2>
                    <span class="ref">{{ $logs->count() }} LLM {{ Str::plural('call', $logs->count()) }}</span>
                </div>
                <p class="mt-2 text-sm text-ink-3">The exact prompt and raw response of every call, for auditing and debugging.</p>

                <ol class="mt-3 divide-y divide-rule border-b border-rule">
                    @forelse ($logs as $log)
                        <li>
                            <details class="group">
                                <summary class="flex cursor-pointer list-none flex-wrap items-baseline gap-x-4 gap-y-1 py-3 font-mono text-sm transition-colors hover:bg-sunk">
                                    <span class="text-ink-3" aria-hidden="true"><span class="group-open:hidden">+</span><span class="hidden group-open:inline">−</span></span>
                                    <span class="text-ink-2">{{ $log->created_at->format('d M Y H:i:s') }}</span>
                                    <span @class(['font-medium uppercase', 'text-ok' => $log->parsed_ok, 'text-seal' => ! $log->parsed_ok])>
                                        {{ $log->parsed_ok ? 'Valid response' : 'Failed' }}
                                    </span>
                                    @if ($log->error) <span class="min-w-0 flex-1 truncate text-seal">{{ $log->error }}</span> @endif
                                </summary>
                                @php
                                    $decoded = json_decode($log->raw_response ?? '', true);
                                    $pretty = is_array($decoded) ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $log->raw_response;
                                @endphp
                                <div class="space-y-4 pb-5">
                                    <div>
                                        <p class="eyebrow mb-1.5">Raw response</p>
                                        <pre class="typed max-h-72 overflow-auto">{{ $pretty ?? '(no response)' }}</pre>
                                    </div>
                                    <div>
                                        <p class="eyebrow mb-1.5">Prompt</p>
                                        <pre class="typed max-h-72 overflow-auto whitespace-pre-wrap">{{ $log->prompt }}</pre>
                                    </div>
                                </div>
                            </details>
                        </li>
                    @empty
                        <li class="py-5 text-base text-ink-3">No calls yet. The first one is made as soon as a queue worker picks up this ticket.</li>
                    @endforelse
                </ol>
            </section>
        </div>

        {{-- Case details --}}
        <aside class="space-y-8">
            <section>
                <h2 class="panel-title border-b border-rule-strong pb-2">Details</h2>
                <dl class="divide-y divide-rule">
                    <div class="flex items-center justify-between gap-3 py-3">
                        <dt class="text-sm text-ink-3">Priority</dt><dd><x-priority :value="$ticket->urgency" /></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 py-3">
                        <dt class="text-sm text-ink-3">Team</dt>
                        <dd class="text-right text-ink">
                            {{ $ticket->assigned_team ?? '—' }}
                            {{-- Shown when routing moved the ticket away from the category's usual team --}}
                            @if ($escalated)
                                <span class="block text-sm text-seal">Escalated from {{ $usualTeam }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-3">
                        <dt class="text-sm text-ink-3">Sentiment</dt>
                        <dd @class(['text-ink', 'font-semibold text-seal' => $ticket->sentiment === 'angry'])>{{ $ticket->sentiment ? ucfirst($ticket->sentiment) : '—' }}</dd>
                    </div>
                    <div class="py-3">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-sm text-ink-3">Confidence</dt>
                            <dd class="font-mono text-sm tabular-nums text-ink">{{ $ticket->confidence !== null ? round($ticket->confidence * 100) . '%' : '—' }}</dd>
                        </div>
                        @if ($ticket->confidence !== null)
                            {{-- Bar with a tick at the auto-resolve threshold --}}
                            <div class="relative mt-2.5 h-1 bg-sunk" title="Auto-resolve threshold: {{ round($threshold * 100) }}%" aria-hidden="true">
                                <div class="h-1 {{ $ticket->confidence >= $threshold ? 'bg-navy' : 'bg-rule-strong' }}" style="width: {{ $ticket->confidence * 100 }}%"></div>
                                <div class="absolute -top-1.5 h-4 w-0.5 bg-ink" style="left: {{ $threshold * 100 }}%"></div>
                            </div>
                            <p class="mt-1.5 text-xs text-ink-3">Auto-resolve threshold {{ round($threshold * 100) }}%</p>
                        @endif
                    </div>
                    <div class="flex items-center justify-between gap-3 py-3">
                        <dt class="text-sm text-ink-3">Created</dt>
                        <dd class="ref text-ink">{{ $ticket->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                </dl>
            </section>

            @unless ($ticket->isAwaitingAnalysis())
                <section>
                    <h2 class="panel-title border-b border-rule-strong pb-2">Category</h2>
                    <div class="pt-3">
                        @if ($ticket->corrected_category)
                            <p class="font-semibold text-ink">{{ $categoryName($ticket->corrected_category) }}</p>
                            <p class="mt-1 text-sm text-ink-3">Corrected by an agent. AI suggested <span class="line-through">{{ $categoryName($ticket->category) ?? 'none' }}</span>.</p>
                        @else
                            <p class="font-semibold text-ink">{{ $categoryName($ticket->category) ?? '—' }}</p>
                            <p class="mt-1 text-sm text-ink-3">Suggested by the AI.</p>
                        @endif

                        <form method="POST" action="{{ route('tickets.correct-category', $ticket) }}" class="mt-4">
                            @csrf
                            @method('PATCH')
                            <label for="category" class="label">Change category</label>
                            <div class="mt-1.5 flex gap-2">
                                <select id="category" name="category" class="field min-w-0 flex-1 py-2">
                                    @foreach ($categories as $slug => $category)
                                        <option value="{{ $slug }}" @selected($ticket->effectiveCategory() === $slug)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn-secondary">Save</button>
                            </div>
                        </form>
                        @error('category') <p class="mt-1.5 text-sm font-semibold text-seal">{{ $message }}</p> @enderror
                        <p class="mt-2 text-sm text-ink-3">Changing it re-routes the ticket and counts as an AI error on the overview.</p>
                    </div>
                </section>
            @endunless

            @if ($ticket->expected_category && ! $ticket->isAwaitingAnalysis())
                @php
                    $aiTeam = $ticket->category ? app(App\Services\TicketRouter::class)->route($ticket->category, $ticket->urgency ?? 'low') : null;
                    $rows = [
                        ['Category', $categoryName($ticket->expected_category), $categoryName($ticket->category), $ticket->category === $ticket->expected_category],
                        ['Team', $ticket->expected_team, $aiTeam, $aiTeam === $ticket->expected_team],
                    ];
                @endphp
                <section>
                    <div class="flex items-baseline justify-between border-b border-rule-strong pb-2">
                        <h2 class="panel-title">Evaluation label</h2>
                        <a href="{{ route('evaluation') }}" class="text-sm text-navy hover:underline">All results</a>
                    </div>
                    <dl class="divide-y divide-rule">
                        @foreach ($rows as [$label, $expected, $actual, $ok])
                            <div class="py-3">
                                <dt class="flex items-center justify-between text-sm text-ink-3">
                                    {{ $label }}
                                    <span @class(['tag', 'border-ok text-ok' => $ok, 'border-seal text-seal' => ! $ok])>{{ $ok ? 'Match' : 'Miss' }}</span>
                                </dt>
                                <dd class="mt-1 text-ink">Expected: {{ $expected }}</dd>
                                @unless ($ok) <dd class="text-seal">AI: {{ $actual ?? 'no answer' }}</dd> @endunless
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif

            @if ($ticket->summary)
                <section>
                    <h2 class="panel-title border-b border-rule-strong pb-2">AI summary</h2>
                    <p class="pt-3 text-base leading-relaxed text-ink-2">{{ $ticket->summary }}</p>
                </section>
            @endif

            @if ($ticket->kbArticle)
                <section>
                    <h2 class="panel-title border-b border-rule-strong pb-2">Matched article</h2>
                    <p class="pt-3 font-semibold text-ink">{{ $ticket->kbArticle->title }}</p>
                    <p class="ref mt-1">{{ $ticket->kbArticle->slug }}</p>
                </section>
            @endif
        </aside>
    </div>
</x-layout>
