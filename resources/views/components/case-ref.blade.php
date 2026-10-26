@props(['ticket'])

{{-- Ticket reference shown as a case number, e.g. CASE-0042. Formatting only. --}}
<span {{ $attributes->class('ref') }}>CASE-{{ str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT) }}</span>
