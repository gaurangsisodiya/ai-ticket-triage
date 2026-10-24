<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\TicketAnalyzer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tickets:analyze {ticket : The ticket ID}')]
#[Description('Run the AI analysis on one ticket and print the result (the ticket itself is not changed)')]
class AnalyzeTicket extends Command
{
    public function handle(TicketAnalyzer $analyzer): int
    {
        $ticket = Ticket::find($this->argument('ticket'));

        if (! $ticket) {
            $this->error('Ticket not found.');

            return self::FAILURE;
        }

        $this->info("Analyzing #{$ticket->id}: {$ticket->subject}");
        $this->line('Model: '.config('ai.llm.provider').' / '.config('ai.llm.model'));

        $result = $analyzer->analyze($ticket);
        $log = $ticket->aiLogs()->latest('id')->first();

        if ($result === null) {
            $this->error('Analysis failed: '.$log?->error);
            $this->line("Details in ai_logs, id {$log?->id}");

            return self::FAILURE;
        }

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->line("Logged as ai_logs id {$log?->id}");

        return self::SUCCESS;
    }
}
