<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\TicketAnalyzer;
use App\Services\TicketRouter;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Throwable;

/**
 * Runs the AI analysis for one ticket in the background, so submitting a ticket is instant.
 */
class AnalyzeTicketJob implements ShouldQueue
{
    use Queueable;

    /**
     * Worst case inside the analyzer: 3 attempts x 30s timeout + 2 x 20s Retry-After waits.
     * Must stay below the queue's retry_after (DB_QUEUE_RETRY_AFTER in .env).
     */
    public int $timeout = 180;

    /** If handle() itself throws 3 times (e.g. database errors), give up and call failed(). */
    public int $maxExceptions = 3;

    /** If the ticket was deleted before the job ran (e.g. demo reseeded), just drop the job. */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public Ticket $ticket)
    {
    }

    /**
     * The rate limiter "releases" jobs back onto the queue, and each release counts as an
     * attempt. A fixed $tries would fail tickets that were only waiting their turn, so we
     * allow retries for up to an hour after the job was queued.
     */
    public function retryUntil(): DateTime
    {
        return now()->addHour();
    }

    /**
     * Holds the job back when we'd exceed LLM_REQUESTS_PER_MINUTE (limiter defined in AppServiceProvider).
     */
    public function middleware(): array
    {
        return [new RateLimited('llm')];
    }

    public function handle(TicketAnalyzer $analyzer, TicketRouter $router): void
    {
        $this->ticket->update(['status' => 'processing']);

        // null = analysis failed (reason is in ai_logs); the router sends those to review.
        $router->apply($this->ticket, $analyzer->analyze($this->ticket));
    }

    /**
     * Called by Laravel when the job gives up for good (exceptions, timeout, retryUntil passed).
     * Never leave a ticket stuck in "processing": hand it to a human.
     */
    public function failed(?Throwable $exception): void
    {
        $this->ticket->update(['status' => 'needs_review']);
    }
}
