@props(['title' => null])

@php
    $reviewActive = request()->routeIs('tickets.index') && request('status') === 'needs_review';
    // The sidebar reads like an office directory: two sections, plain text entries.
    $directory = [
        'Desk' => [
            ['route' => 'dashboard', 'params' => [], 'label' => 'Overview', 'active' => request()->routeIs('dashboard')],
            ['route' => 'evaluation', 'params' => [], 'label' => 'Evaluation', 'active' => request()->routeIs('evaluation')],
        ],
        'Cases' => [
            ['route' => 'tickets.index', 'params' => [], 'label' => 'All tickets',
             'active' => (request()->routeIs('tickets.index') && ! $reviewActive) || request()->routeIs('tickets.show')],
            ['route' => 'tickets.index', 'params' => ['status' => 'needs_review'], 'label' => 'Review queue',
             'active' => $reviewActive, 'count' => $navCounts['review'] ?? null],
            ['route' => 'tickets.create', 'params' => [], 'label' => 'New ticket', 'active' => request()->routeIs('tickets.create')],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title · " : '' }}Support Desk</title>
    {{-- Font CSS and preload tags for the families configured in vite.config.js --}}
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen">
<a href="#main" class="sr-only z-50 bg-navy px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Skip to content</a>

<div class="lg:flex">
    <aside class="border-b border-rule bg-paper lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-72 lg:shrink-0 lg:flex-col lg:border-r lg:border-b-0">
        {{-- Department header --}}
        <a href="{{ route('dashboard') }}" class="block px-5 pt-5 pb-4 lg:px-7 lg:pt-8 lg:pb-6">
            <span class="block font-serif text-xl font-semibold text-ink">Support Desk</span>
            <span class="eyebrow mt-1 block tracking-[0.06em]">Office of Digital Services</span>
        </a>

        <nav aria-label="Main" class="flex gap-x-6 overflow-x-auto px-5 pb-3 lg:block lg:space-y-7 lg:px-0 lg:pb-0">
            @foreach ($directory as $section => $items)
                <div class="flex shrink-0 gap-x-5 lg:block">
                    <p class="eyebrow hidden px-7 pb-2 lg:block">{{ $section }}</p>
                    <ul class="flex gap-x-5 lg:block lg:space-y-0.5">
                        @foreach ($items as $item)
                            <li>
                                {{-- Active entry: a navy rule on the left (desktop) or underneath (phone), plus bold text --}}
                                <a href="{{ route($item['route'], $item['params']) }}"
                                   @if ($item['active']) aria-current="page" @endif
                                   @class([
                                       'flex items-baseline gap-3 border-b-2 py-1.5 text-base whitespace-nowrap transition-colors lg:border-b-0 lg:border-l-2 lg:px-7 lg:py-1.5',
                                       'border-navy font-semibold text-ink' => $item['active'],
                                       'border-transparent text-ink-2 hover:text-ink hover:underline' => ! $item['active'],
                                   ])>
                                    {{ $item['label'] }}
                                    @if (! empty($item['count']))
                                        <span class="ref ml-auto" aria-label="{{ $item['count'] }} waiting">{{ $item['count'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        {{-- Which model is triaging, and the auto-resolve threshold in force --}}
        <div class="mt-auto hidden border-t border-rule px-7 py-5 lg:block">
            <p class="eyebrow">Triage model</p>
            <p class="ref mt-1.5 truncate text-ink" title="{{ config('ai.llm.model') }}">{{ config('ai.llm.model') }}</p>
            <p class="mt-1 text-sm text-ink-3">via {{ ucfirst(config('ai.llm.provider')) }} · auto-resolve ≥ {{ round(config('ai.auto_resolve_threshold') * 100) }}%</p>
        </div>
    </aside>

    <main id="main" class="min-w-0 flex-1">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-8 lg:py-12">
            @if (session('success'))
                <div class="mb-8 flex items-start gap-3 border-l-4 border-ok bg-ok-tint px-4 py-3 text-base text-ink" role="status">
                    <span class="eyebrow shrink-0 pt-0.5 text-ok">Recorded</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
