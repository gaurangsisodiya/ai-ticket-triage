<x-layout :title="$ticket->reference()">
    {{-- While the AI is working, reload every 5 seconds. No JavaScript needed. --}}
    @if ($ticket->isAwaitingAnalysis())
        @push('head') <meta http-equiv="refresh" content="5"> @endpush
    @endif

    @php
        $threshold = config('ai.auto_resolve_threshold');
        $categoryName = fn (?string $slug) => $slug ? ($categories[$slug]->name ?? $slug) : null;
        $usualTeam = $categories->get($ticket->effectiveCategory() ?? '')?->team->name;
        $escalated = $usualTeam && $ticket->assigned_team && $usualTeam !== $ticket->assigned_team;
        $initials = strtoupper(substr($ticket->customer_email, 0, 2));
    @endphp

    <x-page-header :title="$ticket->subject">
        <x-slot:breadcrumb>
            <a href="{{ route('tickets.index') }}" class="hover:text-zinc-800">Tickets</a>
            <span class="mx-1 text-zinc-300">/</span>
            <span class="font-mono text-xs">{{ $ticket->reference() }}</span>
        </x-slot:breadcrumb>
        <x-slot:actions>
            @unless ($ticket->isAwaitingAnalysis())
                <form method="POST" action="{{ route('tickets.reanalyze', $ticket) }}">
                    @csrf
                    <button class="btn-secondary">Re-run analysis</button>
                </form>
                @if ($ticket->status === 'needs_review')
                    <form method="POST" action="{{ route('tickets.resolve', $ticket) }}">
                        @csrf
                        @method('PATCH')
                        <button class="btn-primary">Mark as resolved</button>
                    </form>
                @endif
            @endunless
        </x-slot:actions>
    </x-page-header>

    {{-- Why the ticket is in this state, in one sentence --}}
    <div class="mb-6 flex items-start gap-2.5 rounded-md border border-zinc-200 bg-white px-3.5 py-2.5 text-[13px]">
        <svg viewBox="0 0 20 20" class="mt-0.5 size-4 shrink-0 text-zinc-400" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.25v2.75a.75.75 0 001.5 0V10a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <p><span class="font-medium text-zinc-900">Triage decision.</span> <span class="text-zinc-600">{{ $ticket->decisionReason() }}</span></p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
        {{-- Conversation --}}
        <div class="min-w-0 space-y-4">
            <article class="panel">
                <header class="flex items-center gap-3 border-b border-zinc-100 px-4 py-3">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-medium text-zinc-600">{{ $initials }}</span>
                    <div class="min-w-0 text-[13px]">
                        <p class="truncate font-medium text-zinc-900">{{ $ticket->customer_email }}</p>
                        <p class="text-xs text-zinc-500" title="{{ $ticket->created_at->format('d M Y, H:i') }}">Customer · {{ $ticket->created_at->format('d M Y, H:i') }}</p>
                    </div>
                </header>
                <p class="px-4 py-4 leading-relaxed break-words whitespace-pre-line text-zinc-800">{{ $ticket->body }}</p>
            </article>

            @if ($ticket->ai_reply)
                @php $sent = $ticket->status === 'auto_resolved'; @endphp
                <article @class(['panel', 'border-l-2 border-l-emerald-500' => $sent, 'border-l-2 border-l-amber-400 border-dashed' => ! $sent])>
                    <header class="flex flex-wrap items-center gap-3 border-b border-zinc-100 px-4 py-3">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-900 text-white">
                            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="4"/><path d="M7 9h10M7 13h6M7 17h3" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1 text-[13px]">
                            <p class="font-medium text-zinc-900">{{ $sent ? 'Reply sent to customer' : 'Suggested reply' }}</p>
                            <p class="text-xs text-zinc-500">
                                Written by the AI assistant{{ $ticket->kbArticle ? ' from "' . $ticket->kbArticle->title . '"' : '' }}
                            </p>
                        </div>
                        <span @class(['rounded px-1.5 py-0.5 text-[11px] font-medium',
                                      'bg-emerald-50 text-emerald-700' => $sent, 'bg-amber-50 text-amber-800' => ! $sent])>
                            {{ $sent ? 'Sent automatically' : 'Draft · not sent' }}
                        </span>
                    </header>
                    <p class="px-4 py-4 text-[13px] leading-relaxed whitespace-pre-line text-zinc-800">{{ $ticket->ai_reply }}</p>
                </article>
            @elseif ($ticket->isAwaitingAnalysis())
                <div class="panel flex items-center gap-2.5 px-4 py-3 text-[13px] text-zinc-600">
                    <span class="size-2 animate-pulse rounded-full bg-sky-500"></span>
                    The AI is analyzing this ticket. This page refreshes automatically.
                </div>
            @endif

            {{-- Audit trail: every LLM call for this ticket --}}
            <section class="pt-4">
                <h2 class="panel-title">LLM calls <span class="font-normal text-zinc-500">({{ $logs->count() }})</span></h2>
                <p class="mt-0.5 text-xs text-zinc-500">The exact prompt and raw response of every call, for auditing and debugging.</p>
                <div class="panel mt-3 divide-y divide-zinc-100">
                    @forelse ($logs as $log)
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-center gap-3 px-4 py-2.5 text-[13px] hover:bg-zinc-50">
                                <svg viewBox="0 0 20 20" class="size-3.5 shrink-0 text-zinc-400 transition-transform group-open:rotate-90" fill="currentColor" aria-hidden="true">
                                    <path d="M7.2 4.2a.75.75 0 011.06 0l5.25 5.25a.75.75 0 010 1.06L8.26 15.8a.75.75 0 11-1.06-1.06L11.94 10 7.2 5.26a.75.75 0 010-1.06z"/>
                                </svg>
                                <span class="inline-flex items-center gap-1.5 {{ $log->parsed_ok ? 'text-zinc-700' : 'text-red-700' }}">
                                    <span class="size-1.5 rounded-full {{ $log->parsed_ok ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                    {{ $log->parsed_ok ? 'Valid response' : 'Failed' }}
                                </span>
                                <span class="font-mono text-xs text-zinc-500">{{ $log->created_at->format('d M H:i:s') }}</span>
                                @if ($log->error) <span class="min-w-0 truncate text-xs text-red-600">{{ $log->error }}</span> @endif
                            </summary>
                            @php
                                $decoded = json_decode($log->raw_response ?? '', true);
                                $pretty = is_array($decoded) ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $log->raw_response;
                            @endphp
                            <div class="space-y-3 border-t border-zinc-100 bg-zinc-50/60 px-4 py-3">
                                <div>
                                    <p class="text-xs font-medium text-zinc-500">Raw response</p>
                                    <pre class="mt-1 max-h-64 overflow-auto rounded border border-zinc-200 bg-white p-3 font-mono text-xs text-zinc-800">{{ $pretty ?? '(no response)' }}</pre>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-zinc-500">Prompt</p>
                                    <pre class="mt-1 max-h-64 overflow-auto rounded border border-zinc-200 bg-white p-3 font-mono text-xs whitespace-pre-wrap text-zinc-700">{{ $log->prompt }}</pre>
                                </div>
                            </div>
                        </details>
                    @empty
                        <p class="px-4 py-4 text-[13px] text-zinc-500">No calls yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        {{-- Properties --}}
        <aside class="space-y-4">
            <section class="panel">
                <div class="panel-header"><h2 class="panel-title">Details</h2></div>
                <dl class="divide-y divide-zinc-100 text-[13px]">
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-zinc-500">Status</dt><dd><x-status :value="$ticket->status" /></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-zinc-500">Priority</dt><dd><x-priority :value="$ticket->urgency" /></dd>
                    </div>
                    <div class="flex items-start justify-between gap-3 px-4 py-2.5">
                        <dt class="text-zinc-500">Team</dt>
                        <dd class="text-right text-zinc-900">
                            {{ $ticket->assigned_team ?? '—' }}
                            {{-- Shown when routing moved the ticket away from the category's usual team --}}
                            @if ($escalated)
                                <span class="block text-xs text-zinc-500">Escalated from {{ $usualTeam }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                        <dt class="text-zinc-500">Sentiment</dt>
                        <dd @class(['text-zinc-900', 'font-medium text-red-700' => $ticket->sentiment === 'angry'])>{{ $ticket->sentiment ? ucfirst($ticket->sentiment) : '—' }}</dd>
                    </div>
                    <div class="px-4 py-2.5">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-zinc-500">Confidence</dt>
                            <dd class="tabular-nums text-zinc-900">{{ $ticket->confidence !== null ? round($ticket->confidence * 100) . '%' : '—' }}</dd>
                        </div>
                        @if ($ticket->confidence !== null)
                            {{-- Bar with a tick at the auto-resolve threshold --}}
                            <div class="relative mt-2 h-1.5 rounded-full bg-zinc-100" title="Auto-resolve threshold: {{ round($threshold * 100) }}%">
                                <div class="h-1.5 rounded-full {{ $ticket->confidence >= $threshold ? 'bg-zinc-800' : 'bg-zinc-400' }}" style="width: {{ $ticket->confidence * 100 }}%"></div>
                                <div class="absolute -top-1 h-3.5 w-px bg-zinc-500" style="left: {{ $threshold * 100 }}%"></div>
                            </div>
                            <p class="mt-1 text-[11px] text-zinc-400">Threshold {{ round($threshold * 100) }}%</p>
                        @endif
                    </div>
                    <div class="px-4 py-2.5">
                        <dt class="text-zinc-500">Created</dt>
                        <dd class="mt-0.5 text-zinc-900">{{ $ticket->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                </dl>
            </section>

            @unless ($ticket->isAwaitingAnalysis())
                <section class="panel">
                    <div class="panel-header"><h2 class="panel-title">Category</h2></div>
                    <div class="px-4 py-3 text-[13px]">
                        @if ($ticket->corrected_category)
                            <p class="text-zinc-900">{{ $categoryName($ticket->corrected_category) }}</p>
                            <p class="mt-0.5 text-xs text-zinc-500">Corrected by an agent. AI suggested <span class="line-through">{{ $categoryName($ticket->category) ?? 'none' }}</span>.</p>
                        @else
                            <p class="text-zinc-900">{{ $categoryName($ticket->category) ?? '—' }}</p>
                            <p class="mt-0.5 text-xs text-zinc-500">Suggested by the AI.</p>
                        @endif

                        <form method="POST" action="{{ route('tickets.correct-category', $ticket) }}" class="mt-3 flex gap-2">
                            @csrf
                            @method('PATCH')
                            <select name="category" aria-label="Change category" class="field min-w-0 flex-1 py-1.5 text-[13px]">
                                @foreach ($categories as $slug => $category)
                                    <option value="{{ $slug }}" @selected($ticket->effectiveCategory() === $slug)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn-secondary">Save</button>
                        </form>
                        @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-zinc-500">Changing it re-routes the ticket and counts as an AI error on the overview.</p>
                    </div>
                </section>
            @endunless

            @if ($ticket->summary)
                <section class="panel">
                    <div class="panel-header"><h2 class="panel-title">AI summary</h2></div>
                    <p class="px-4 py-3 text-[13px] leading-relaxed text-zinc-700">{{ $ticket->summary }}</p>
                </section>
            @endif

            @if ($ticket->kbArticle)
                <section class="panel">
                    <div class="panel-header"><h2 class="panel-title">Matched article</h2></div>
                    <div class="px-4 py-3 text-[13px]">
                        <p class="font-medium text-zinc-900">{{ $ticket->kbArticle->title }}</p>
                        <p class="mt-0.5 font-mono text-xs text-zinc-500">{{ $ticket->kbArticle->slug }}</p>
                    </div>
                </section>
            @endif
        </aside>
    </div>
</x-layout>
