<x-layout title="New ticket">
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Submit a support ticket"
                       description="Tell us what went wrong. Your ticket is categorized, prioritized and routed straight away, and if a help article answers your question you'll get a reply within seconds." />

        <div class="grid gap-10 md:grid-cols-[minmax(0,1fr)_14rem]">
            {{-- Intake form: numbered fields, printed-style labels, helper text under each --}}
            <form method="POST" action="{{ route('tickets.store') }}" class="panel">
                @csrf

                <div class="flex items-baseline justify-between border-b border-rule px-6 py-3">
                    <p class="eyebrow">Support request</p>
                    <p class="ref">Form SD-1</p>
                </div>

                @php
                    $fieldClass = fn (string $name) => $errors->has($name) ? 'field mt-2 border-seal' : 'field mt-2';
                @endphp

                <ol class="divide-y divide-rule">
                    <li class="grid grid-cols-[2rem_1fr] gap-x-2 px-6 py-5">
                        <span class="font-serif text-lg text-ink-3" aria-hidden="true">1</span>
                        <div>
                            <label for="customer_email" class="label">Email address</label>
                            <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email') }}" required
                                   autocomplete="email" placeholder="name@organization.gov" class="{{ $fieldClass('customer_email') }}"
                                   aria-describedby="customer_email-help" @error('customer_email') aria-invalid="true" @enderror>
                            @error('customer_email')
                                <p id="customer_email-help" class="mt-1.5 text-sm font-semibold text-seal">{{ $message }}</p>
                            @else
                                <p id="customer_email-help" class="mt-1.5 text-sm text-ink-3">We reply to this address.</p>
                            @enderror
                        </div>
                    </li>

                    <li class="grid grid-cols-[2rem_1fr] gap-x-2 px-6 py-5">
                        <span class="font-serif text-lg text-ink-3" aria-hidden="true">2</span>
                        <div>
                            <label for="subject" class="label">Subject</label>
                            <input type="text" id="subject" name="subject" value="{{ old('subject') }}" required maxlength="255"
                                   placeholder="A short summary of the problem" class="{{ $fieldClass('subject') }}"
                                   aria-describedby="subject-help" @error('subject') aria-invalid="true" @enderror>
                            @error('subject')
                                <p id="subject-help" class="mt-1.5 text-sm font-semibold text-seal">{{ $message }}</p>
                            @else
                                <p id="subject-help" class="mt-1.5 text-sm text-ink-3">For example: "Agenda packet will not compile".</p>
                            @enderror
                        </div>
                    </li>

                    <li class="grid grid-cols-[2rem_1fr] gap-x-2 px-6 py-5">
                        <span class="font-serif text-lg text-ink-3" aria-hidden="true">3</span>
                        <div>
                            <label for="body" class="label">Description</label>
                            <textarea id="body" name="body" rows="8" required maxlength="5000"
                                      placeholder="What happened, what you expected, and any error messages you saw."
                                      class="{{ $fieldClass('body') }} leading-relaxed"
                                      aria-describedby="body-help" @error('body') aria-invalid="true" @enderror>{{ old('body') }}</textarea>
                            @error('body')
                                <p id="body-help" class="mt-1.5 text-sm font-semibold text-seal">{{ $message }}</p>
                            @else
                                <p id="body-help" class="mt-1.5 text-sm text-ink-3">Up to 5,000 characters. Include the page or module you were using.</p>
                            @enderror
                        </div>
                    </li>
                </ol>

                <div class="flex items-center justify-end gap-3 border-t border-rule bg-paper px-6 py-4">
                    <a href="{{ route('tickets.index') }}" class="btn-ghost">Cancel</a>
                    <button type="submit" class="btn-primary">Submit ticket</button>
                </div>
            </form>

            {{-- What happens after submitting --}}
            <aside class="text-sm text-ink-2">
                <h2 class="panel-title border-b border-rule-strong pb-2">What happens next</h2>
                <ol class="mt-3 space-y-3">
                    <li><span class="font-semibold text-ink">1. Filed.</span> Your ticket gets a case number straight away.</li>
                    <li><span class="font-semibold text-ink">2. Triaged.</span> It is categorized, given a priority and routed to the right team.</li>
                    <li><span class="font-semibold text-ink">3. Answered.</span> If a help article covers it, you get a reply in seconds. Otherwise an agent picks it up.</li>
                </ol>
                <p class="mt-4 border-t border-rule pt-3 text-ink-3">Urgent problems always go to a person, never to an automatic reply.</p>
            </aside>
        </div>
    </div>
</x-layout>
