<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\KbArticle;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\TicketRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketRouterTest extends TestCase
{
    use RefreshDatabase;

    private TicketRouter $router;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.auto_resolve_threshold' => 0.8]);

        $l1 = Team::create(['name' => 'L1 Support', 'description' => 'First line']);
        $dev = Team::create(['name' => 'Dev Team', 'description' => 'Engineers']);
        $billing = Team::create(['name' => 'Billing', 'description' => 'Money']);

        Category::create(['slug' => 'login_access', 'name' => 'Login', 'description' => '-', 'team_id' => $l1->id]);
        Category::create(['slug' => 'bug', 'name' => 'Bug', 'description' => '-', 'team_id' => $dev->id]);
        Category::create(['slug' => 'account_billing', 'name' => 'Billing', 'description' => '-', 'team_id' => $billing->id]);

        $this->router = new TicketRouter;
    }

    // --- Routing ---

    public function test_routes_to_the_category_default_team(): void
    {
        $this->assertSame('L1 Support', $this->router->route('login_access', 'medium'));
        $this->assertSame('Dev Team', $this->router->route('bug', 'low'));
        $this->assertSame('Billing', $this->router->route('account_billing', 'medium'));
    }

    public function test_high_urgency_first_line_ticket_is_escalated(): void
    {
        $this->assertSame('Dev Team', $this->router->route('login_access', 'high'));
    }

    public function test_high_urgency_specialist_ticket_stays_with_specialists(): void
    {
        $this->assertSame('Billing', $this->router->route('account_billing', 'high'));
    }

    public function test_unknown_category_goes_to_first_line(): void
    {
        $this->assertSame('L1 Support', $this->router->route('no_such_category', 'low'));
    }

    public function test_no_escalation_if_escalation_team_does_not_exist(): void
    {
        config(['ai.routing.escalation_team' => 'Team That Does Not Exist']);

        $this->assertSame('L1 Support', $this->router->route('login_access', 'high'));
    }

    // --- Auto-resolve rule ---

    public function test_auto_resolve_needs_kb_match_and_enough_confidence(): void
    {
        $this->assertTrue($this->router->canAutoResolve('password-reset', 0.95));
        $this->assertTrue($this->router->canAutoResolve('password-reset', 0.80));  // threshold is inclusive
        $this->assertFalse($this->router->canAutoResolve('password-reset', 0.79));
        $this->assertFalse($this->router->canAutoResolve(null, 0.99));             // confident, but no article
    }

    public function test_high_urgency_is_never_auto_resolved(): void
    {
        // e.g. "No bulletins delivered since 8am": matches a KB article, but it's an outage
        $this->assertFalse($this->router->canAutoResolve('bulletin-delivery-failures', 0.99, 'high'));
        $this->assertTrue($this->router->canAutoResolve('bulletin-delivery-failures', 0.99, 'medium'));

        $ticket = $this->ticket();
        $this->router->apply($ticket, $this->analysis(['urgency' => 'high', 'confidence' => 0.99]));

        $this->assertSame('needs_review', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->ai_reply); // draft kept as a suggestion
    }

    public function test_angry_customers_are_never_auto_resolved(): void
    {
        $this->assertFalse($this->router->canAutoResolve('password-reset', 0.99, 'low', 'angry'));
        $this->assertTrue($this->router->canAutoResolve('password-reset', 0.99, 'low', 'negative'));
    }

    public function test_threshold_comes_from_config(): void
    {
        config(['ai.auto_resolve_threshold' => 0.95]);

        $this->assertFalse($this->router->canAutoResolve('password-reset', 0.9));
    }

    // --- apply() ---

    private function ticket(): Ticket
    {
        return Ticket::create(['subject' => 'S', 'body' => 'B', 'customer_email' => 'a@example.com', 'status' => 'processing']);
    }

    private function analysis(array $overrides = []): array
    {
        return array_merge([
            'category' => 'login_access', 'urgency' => 'medium', 'summary' => 'Locked out.',
            'sentiment' => 'neutral', 'team' => 'L1 Support', 'kb_match' => 'login-troubleshooting',
            'confidence' => 0.92, 'reply_draft' => 'Wait 15 minutes.',
        ], $overrides);
    }

    public function test_confident_kb_match_is_auto_resolved(): void
    {
        $article = KbArticle::create(['slug' => 'login-troubleshooting', 'title' => 'Login', 'content' => '...']);
        $ticket = $this->ticket();

        $this->router->apply($ticket, $this->analysis());

        $ticket->refresh();
        $this->assertSame('auto_resolved', $ticket->status);
        $this->assertSame('ai', $ticket->resolved_by);
        $this->assertSame($article->id, $ticket->kb_article_id);
        $this->assertSame('Wait 15 minutes.', $ticket->ai_reply);
    }

    public function test_low_confidence_goes_to_review_but_keeps_suggested_reply(): void
    {
        KbArticle::create(['slug' => 'login-troubleshooting', 'title' => 'Login', 'content' => '...']);
        $ticket = $this->ticket();

        $this->router->apply($ticket, $this->analysis(['confidence' => 0.6]));

        $ticket->refresh();
        $this->assertSame('needs_review', $ticket->status);
        $this->assertNull($ticket->resolved_by);
        $this->assertSame('Wait 15 minutes.', $ticket->ai_reply);
    }

    public function test_urgent_ticket_without_kb_match_is_escalated_for_review(): void
    {
        $ticket = $this->ticket();

        $this->router->apply($ticket, $this->analysis(['urgency' => 'high', 'kb_match' => null, 'reply_draft' => null]));

        $ticket->refresh();
        $this->assertSame('needs_review', $ticket->status);
        $this->assertSame('Dev Team', $ticket->assigned_team);
    }

    public function test_failed_analysis_goes_to_first_line_review(): void
    {
        $ticket = $this->ticket();

        $this->router->apply($ticket, null);

        $ticket->refresh();
        $this->assertSame('needs_review', $ticket->status);
        $this->assertSame('L1 Support', $ticket->assigned_team);
    }
}
