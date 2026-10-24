@props(['title', 'description' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <div class="min-w-0">
        @isset($breadcrumb)
            <div class="mb-1 text-[13px] text-zinc-500">{{ $breadcrumb }}</div>
        @endisset
        <h1 class="text-xl font-semibold tracking-tight text-zinc-900">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-[13px] text-zinc-500">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
