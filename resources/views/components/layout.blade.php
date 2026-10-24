@props(['title' => null])

@php
    $reviewActive = request()->routeIs('tickets.index') && request('status') === 'needs_review';
    $nav = [
        ['route' => 'dashboard', 'params' => [], 'label' => 'Overview', 'active' => request()->routeIs('dashboard'),
         'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
        ['route' => 'tickets.index', 'params' => [], 'label' => 'All tickets', 'active' => (request()->routeIs('tickets.index') && ! $reviewActive) || request()->routeIs('tickets.show'),
         'icon' => 'M4 5h16v4a2 2 0 000 4v4H4v-4a2 2 0 000-4V5z'],
        ['route' => 'tickets.index', 'params' => ['status' => 'needs_review'], 'label' => 'Review queue', 'active' => $reviewActive,
         'icon' => 'M12 9v4m0 4h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z', 'count' => $navCounts['review'] ?? null],
        ['route' => 'tickets.create', 'params' => [], 'label' => 'New ticket', 'active' => request()->routeIs('tickets.create'),
         'icon' => 'M12 5v14M5 12h14'],
    ];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? "$title · " : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-zinc-50 text-sm text-zinc-800 antialiased">
<div class="lg:flex">
    {{-- Sidebar (desktop) / top bar (mobile) --}}
    <aside class="border-b border-zinc-200 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-60 lg:shrink-0 lg:flex-col lg:border-r lg:border-b-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-4 pt-4 pb-3 lg:px-5 lg:pt-5">
            <svg viewBox="0 0 24 24" class="size-6 text-zinc-900" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="4"/>
                <path d="M7 9h10M7 13h6M7 17h3" stroke-linecap="round"/>
            </svg>
            <span class="text-[15px] font-semibold tracking-tight text-zinc-900">{{ config('app.name') }}</span>
        </a>

        <nav class="flex gap-1 overflow-x-auto px-2 pb-2 lg:mt-2 lg:flex-col lg:px-3">
            @foreach ($nav as $item)
                <a href="{{ route($item['route'], $item['params']) }}"
                   @class([
                       'flex shrink-0 items-center gap-2.5 rounded-md px-2.5 py-1.5 text-[13px]',
                       'bg-zinc-100 font-medium text-zinc-900' => $item['active'],
                       'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900' => ! $item['active'],
                   ])>
                    <svg viewBox="0 0 24 24" class="size-4 shrink-0 {{ $item['active'] ? 'text-zinc-900' : 'text-zinc-400' }}" fill="none"
                         stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="{{ $item['icon'] }}"/>
                    </svg>
                    {{ $item['label'] }}
                    @if (! empty($item['count']))
                        <span class="ml-auto rounded bg-zinc-100 px-1.5 text-xs tabular-nums text-zinc-600 lg:ml-auto">{{ $item['count'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        {{-- Which model is triaging tickets, and the auto-resolve threshold in force --}}
        <div class="mt-auto hidden border-t border-zinc-200 px-5 py-4 text-xs text-zinc-500 lg:block">
            <p class="font-medium text-zinc-700">Triage model</p>
            <p class="mt-1 truncate font-mono text-[11px]" title="{{ config('ai.llm.model') }}">{{ config('ai.llm.model') }}</p>
            <p class="mt-0.5">via {{ ucfirst(config('ai.llm.provider')) }} · auto-resolve ≥ {{ round(config('ai.auto_resolve_threshold') * 100) }}%</p>
        </div>
    </aside>

    <main class="min-w-0 flex-1">
        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @if (session('success'))
                <div class="mb-5 flex items-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-[13px] text-emerald-800" role="status">
                    <svg viewBox="0 0 20 20" class="size-4 shrink-0" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            {{ $slot }}
        </div>
    </main>
</div>
</body>
</html>
