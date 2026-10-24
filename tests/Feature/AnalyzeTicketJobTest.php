<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeTicketJob;
use App\Models\KbArticle;
use App\Models\Ticket;
use App\Services\TicketAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * Tests the job on its own: the analyzer is mocked (its own tests live in TicketAnalyzerTest).
 */
class AnalyzeTicketJobTest extends TestCase
{
    use RefreshDatabase;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticket = Ticket::create([
            'subject' => 'Forgot password',
            'body' => 'I forgot my password.',
            'customer_email' => 'a@example.com',
            'status' => 'new',
        ]);
    }

    public function test_successful_analysis_is_saved_and_routed(): void
    {
        $article = KbArticle::create(['slug' => 'password-reset', 'title' => 'Reset', 'content' => '...']);

        $this->mock(TicketAnalyzer::class)->shouldReceive('analyze')->once()->andReturn([
            'category' => 'password_reset',
            'urgency' => 'low',
            'summary' => 'Customer forgot their password.',
            'sentiment' => 'neutral',
            'team' => 'L1 Support',
            'kb_match' => 'password-reset',
            'confidence' => 0.93,
            'reply_draft' => 'Click Forgot password.',
        ]);

        AnalyzeTicketJob::dispatchSync($this->ticket);

        $this->ticket->refresh();
        $this->assertSame('password_reset', $this->ticket->category);
        $this->assertSame('L1 Support', $this->ticket->assigned_team);
        $this->assertSame($article->id, $this->ticket->kb_article_id); // slug converted to id
        $this->assertSame(0.93, $this->ticket->confidence);
        $this->assertSame('Click Forgot password.', $this->ticket->ai_reply);
        // KB match + 0.93 >= 0.8 threshold -> the router auto-resolves it
        $this->assertSame('auto_resolved', $this->ticket->status);
        $this->assertSame('ai', $this->ticket->resolved_by);
    }

    public function test_failed_analysis_sends_ticket_to_review(): void
    {
        $this->mock(TicketAnalyzer::class)->shouldReceive('analyze')->once()->andReturnNull();

        AnalyzeTicketJob::dispatchSync($this->ticket);

        $this->assertSame('needs_review', $this->ticket->refresh()->status);
        $this->assertNull($this->ticket->category);
    }

    public function test_job_that_gives_up_never_leaves_ticket_in_processing(): void
    {
        $this->ticket->update(['status' => 'processing']);

        (new AnalyzeTicketJob($this->ticket))->failed(new RuntimeException('worker died'));

        $this->assertSame('needs_review', $this->ticket->refresh()->status);
    }
}
