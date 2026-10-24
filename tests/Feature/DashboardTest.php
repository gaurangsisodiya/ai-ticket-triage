<?php

namespace Tests\Feature;

use App\Models\AiLog;
use App\Models\Category;
use App\Models\Team;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $l1 = Team::create(['name' => 'L1 Support', 'description' => '-']);
        $dev = Team::create(['name' => 'Dev Team', 'description' => '-']);
        Category::create(['slug' => 'password_reset', 'name' => 'Password reset', 'description' => '-', 'team_id' => $l1->id]);
        Category::create(['slug' => 'bug', 'name' => 'Bug / error', 'description' => '-', 'team_id' => $dev->id]);
    }

    private function ticket(array $attributes): Ticket
    {
        return Ticket::create($attributes + ['subject' => 'S', 'body' => 'B', 'customer_email' => 'a@example.com']);
    }

    public function test_empty_dashboard_does_not_divide_by_zero(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('resolutionRate', null)
            ->assertViewHas('accuracy', null)
            ->assertSee('No analyzed tickets yet.');
    }

    public function test_metrics_are_calculated_correctly(): void
    {
        // 2 auto-resolved by AI
        $this->ticket(['status' => 'auto_resolved', 'resolved_by' => 'ai', 'category' => 'password_reset', 'assigned_team' => 'L1 Support']);
        $this->ticket(['status' => 'auto_resolved', 'resolved_by' => 'ai', 'category' => 'password_reset', 'assigned_team' => 'L1 Support']);
        // 1 in review, AI said password_reset but a human corrected it to bug
        $this->ticket(['status' => 'needs_review', 'category' => 'password_reset', 'corrected_category' => 'bug', 'assigned_team' => 'Dev Team']);
        // 1 in review because the AI analysis failed (no category: excluded from accuracy)
        $this->ticket(['status' => 'needs_review', 'assigned_team' => 'L1 Support']);
        // 1 still waiting: excluded from the resolution rate
        $this->ticket(['status' => 'new']);

        AiLog::create(['ticket_id' => 1, 'prompt' => 'p', 'parsed_ok' => true]);
        AiLog::create(['ticket_id' => 4, 'prompt' => 'p', 'parsed_ok' => false, 'error' => 'HTTP 429']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('resolutionRate', 50)   // 2 resolved by AI / 4 processed
            ->assertViewHas('reviewQueue', 2)
            ->assertViewHas('awaitingAnalysis', 1)
            ->assertViewHas('accuracy', 67)         // (3 AI-categorized - 1 corrected) / 3
            ->assertViewHas('aiCallSuccess', 50)
            // the corrected ticket counts under its corrected category
            ->assertViewHas('byCategory', fn ($data) => $data->all() === ['Password reset' => 2, 'Bug / error' => 1])
            ->assertViewHas('byTeam', fn ($data) => $data->all() === ['L1 Support' => 3, 'Dev Team' => 1])
            ->assertViewHas('topCorrections', fn ($rows) => $rows->first() === ['ai' => 'Password reset', 'human' => 'Bug / error', 'total' => 1]);
    }
}
