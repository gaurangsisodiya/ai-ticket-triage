<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeTicketJob;
use App\Models\AiLog;
use App\Models\Category;
use App\Models\KbArticle;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TicketPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $l1 = Team::create(['name' => 'L1 Support', 'description' => 'First line']);
        $dev = Team::create(['name' => 'Dev Team', 'description' => 'Engineers']);
        Category::create(['slug' => 'password_reset', 'name' => 'Password reset', 'description' => '-', 'team_id' => $l1->id]);
        Category::create(['slug' => 'bug', 'name' => 'Bug / error', 'description' => '-', 'team_id' => $dev->id]);
    }

    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Forgot password', 'body' => 'Help me reset it.', 'customer_email' => 'a@example.com',
            'status' => 'auto_resolved', 'resolved_by' => 'ai', 'category' => 'password_reset', 'urgency' => 'low',
            'sentiment' => 'neutral', 'assigned_team' => 'L1 Support', 'confidence' => 0.95, 'ai_reply' => 'Click Forgot password.',
        ], $attributes));
    }

    // --- List ---

    public function test_list_shows_tickets(): void
    {
        $this->ticket();

        $this->get(route('tickets.index'))->assertOk()->assertSee('Forgot password')->assertSee('Password reset');
    }

    public function test_list_filters_by_status_and_team(): void
    {
        $this->ticket(['subject' => 'Resolved one']);
        $this->ticket(['subject' => 'Broken page', 'status' => 'needs_review', 'resolved_by' => null, 'category' => 'bug', 'assigned_team' => 'Dev Team']);

        $this->get(route('tickets.index', ['status' => 'needs_review']))->assertSee('Broken page')->assertDontSee('Resolved one');
        $this->get(route('tickets.index', ['team' => 'L1 Support']))->assertSee('Resolved one')->assertDontSee('Broken page');
    }

    public function test_category_filter_uses_the_human_correction(): void
    {
        $this->ticket(['subject' => 'Was misclassified', 'corrected_category' => 'bug']);

        $this->get(route('tickets.index', ['category' => 'bug']))->assertSee('Was misclassified');
        $this->get(route('tickets.index', ['category' => 'password_reset']))->assertDontSee('Was misclassified');
    }

    // --- Detail ---

    public function test_detail_page_shows_analysis_reply_and_logs(): void
    {
        $ticket = $this->ticket();
        AiLog::create(['ticket_id' => $ticket->id, 'prompt' => 'PROMPT TEXT', 'raw_response' => '{"ok":true}', 'parsed_ok' => true]);

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Reply sent to customer')
            ->assertSee('Click Forgot password.')
            ->assertSee('Auto-resolved: matched')
            ->assertSee('PROMPT TEXT');
    }

    public function test_detail_page_works_for_failed_analysis(): void
    {
        $ticket = $this->ticket(['status' => 'needs_review', 'resolved_by' => null, 'category' => null, 'urgency' => null,
            'sentiment' => null, 'confidence' => null, 'ai_reply' => null]);

        $this->get(route('tickets.show', $ticket))->assertOk()->assertSee('The AI analysis failed');
    }

    // --- Human actions ---

    public function test_correcting_category_reroutes_and_reopens_an_auto_resolved_ticket(): void
    {
        $ticket = $this->ticket();

        $this->patch(route('tickets.correct-category', $ticket), ['category' => 'bug'])->assertSessionHas('success');

        $ticket->refresh();
        $this->assertSame('bug', $ticket->corrected_category);
        $this->assertSame('password_reset', $ticket->category); // the AI's answer is kept for the accuracy metric
        $this->assertSame('Dev Team', $ticket->assigned_team);
        $this->assertSame('needs_review', $ticket->status);
        $this->assertNull($ticket->resolved_by);
    }

    public function test_choosing_the_ai_category_again_clears_the_correction(): void
    {
        $ticket = $this->ticket(['corrected_category' => 'bug', 'assigned_team' => 'Dev Team']);

        $this->patch(route('tickets.correct-category', $ticket), ['category' => 'password_reset']);

        $ticket->refresh();
        $this->assertNull($ticket->corrected_category);
        $this->assertSame('L1 Support', $ticket->assigned_team);
    }

    public function test_undoing_a_correction_restores_the_auto_resolution(): void
    {
        $article = KbArticle::create(['slug' => 'password-reset', 'title' => 'Reset', 'content' => '...']);
        $ticket = $this->ticket(['kb_article_id' => $article->id, 'confidence' => 0.95]);

        $this->patch(route('tickets.correct-category', $ticket), ['category' => 'bug']);
        $this->assertSame('needs_review', $ticket->refresh()->status);

        $this->patch(route('tickets.correct-category', $ticket), ['category' => 'password_reset']);

        $ticket->refresh();
        $this->assertSame('auto_resolved', $ticket->status);
        $this->assertSame('ai', $ticket->resolved_by);
        $this->assertStringStartsWith('Auto-resolved', $ticket->decisionReason());
    }

    public function test_correction_does_not_reopen_a_ticket_a_human_resolved(): void
    {
        $ticket = $this->ticket(['status' => 'resolved', 'resolved_by' => 'human']);

        $this->patch(route('tickets.correct-category', $ticket), ['category' => 'bug']);

        $this->assertSame('resolved', $ticket->refresh()->status);
        $this->assertSame('human', $ticket->resolved_by);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $ticket = $this->ticket();

        $this->patch(route('tickets.correct-category', $ticket), ['category' => 'made_up'])->assertSessionHasErrors('category');
        $this->assertNull($ticket->refresh()->corrected_category);
    }

    public function test_human_can_resolve_a_ticket(): void
    {
        $ticket = $this->ticket(['status' => 'needs_review', 'resolved_by' => null]);

        $this->patch(route('tickets.resolve', $ticket));

        $ticket->refresh();
        $this->assertSame('resolved', $ticket->status);
        $this->assertSame('human', $ticket->resolved_by);
    }

    public function test_reanalyze_queues_a_new_job(): void
    {
        Queue::fake();
        $ticket = $this->ticket(['status' => 'needs_review']);

        $this->post(route('tickets.reanalyze', $ticket));

        $this->assertSame('new', $ticket->refresh()->status);
        Queue::assertPushed(AnalyzeTicketJob::class);
    }
}
