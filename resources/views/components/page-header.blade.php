@props(['title', 'description' => null])

{{-- Page heading set like a document title: serif, with a rule underneath. --}}
<header class="mb-10 border-b border-rule-strong pb-6">
    @isset($breadcrumb)
        <div class="mb-3 text-sm text-ink-3">{{ $breadcrumb }}</div>
    @endisset
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
        <div class="min-w-0 max-w-3xl">
            <h1 class="font-serif text-2xl font-semibold break-words text-ink">{{ $title }}</h1>
            @if ($description)
                <p class="mt-2 text-base text-ink-2">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>
        @endisset
    </div>
</header>
