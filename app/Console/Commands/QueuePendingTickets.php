<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeTicketJob;
use App\Models\Ticket;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tickets:queue-pending')]
#[Description('Queue AI analysis for every ticket that has not been analyzed yet (status "new"), e.g. seeded tickets')]
class QueuePendingTickets extends Command
{
    public function handle(): int
    {
        $tickets = Ticket::where('status', 'new')->orderBy('id')->get();

        // Bulk work goes on a separate low-priority queue, so tickets that customers submit
        // live (on the "default" queue) are analyzed first instead of waiting behind this batch.
        foreach ($tickets as $ticket) {
            AnalyzeTicketJob::dispatch($ticket)->onQueue('backfill');
        }

        $this->info("Queued {$tickets->count()} ticket(s).");

        $perMinute = (int) config('ai.llm.requests_per_minute');
        if ($tickets->isNotEmpty() && $perMinute > 0) {
            $minutes = (int) ceil($tickets->count() / $perMinute);
            $this->line("Rate limit is {$perMinute}/min, so this takes about {$minutes} minute(s).");
        }
        // A plain `queue:work` only listens to "default" and would never pick these up.
        $this->line('Process them with: php artisan queue:work --queue=default,backfill');

        return self::SUCCESS;
    }
}
