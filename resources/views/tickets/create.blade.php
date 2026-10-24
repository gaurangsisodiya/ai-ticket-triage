<x-layout title="New ticket">
    <div class="mx-auto max-w-2xl">
        <x-page-header title="Submit a support ticket"
                       description="Your ticket is categorized, prioritized and routed straight away. If a help article answers your question, you'll get a reply within seconds." />

        <form method="POST" action="{{ route('tickets.store') }}" class="panel">
            @csrf

            <div class="space-y-5 px-5 py-5">
                <div>
                    <label for="customer_email" class="label">Email address</label>
                    <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email') }}" required
                           autocomplete="email" placeholder="name@organization.gov" class="field mt-1.5">
                    @error('customer_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="subject" class="label">Subject</label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255"
                           placeholder="A short summary of the problem" class="field mt-1.5">
                    @error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="body" class="label">Description</label>
                    <textarea id="body" name="body" rows="8" required maxlength="5000"
                              placeholder="What happened, what you expected, and any error messages you saw."
                              class="field mt-1.5 leading-relaxed">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-xs text-zinc-500">Up to 5,000 characters.</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-zinc-200 bg-zinc-50/60 px-5 py-3">
                <a href="{{ route('tickets.index') }}" class="btn-ghost">Cancel</a>
                <button type="submit" class="btn-primary">Submit ticket</button>
            </div>
        </form>
    </div>
</x-layout>
