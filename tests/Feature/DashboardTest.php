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
            ->assertViewHas('evaluation', fn ($e) => $e['categoryAccuracy'] === null && $e['teamAccuracy'] === null)
            ->assertSee('No analyzed tickets yet.')
            ->assertSee('No labeled tickets');
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
            ->assertViewHas('corrected', 1)          // 1 of 3 AI-categorized tickets corrected by an agent
            ->assertViewHas('aiCategorized', 3)
            ->assertViewHas('aiCallSuccess', 50)
            // the corrected ticket counts under its corrected category
            ->assertViewHas('byCategory', fn ($data) => $data->all() === ['Password reset' => 2, 'Bug / error' => 1])
            ->assertViewHas('byTeam', fn ($data) => $data->all() === ['L1 Support' => 3, 'Dev Team' => 1])
            ->assertViewHas('topCorrections', fn ($rows) => $rows->first() === ['ai' => 'Password reset', 'human' => 'Bug / error', 'total' => 1]);
    }

    private function evaluationSet(): void
    {
        $labeled = fn (string $category, string $team, array $ai) => $this->ticket(
            $ai + ['status' => 'needs_review', 'expected_category' => $category, 'expected_team' => $team]); // $ai wins on overlap

        // 1: both right
        $labeled('password_reset', 'L1 Support', ['subject' => 'Both right', 'category' => 'password_reset', 'urgency' => 'low']);
        // 2: wrong category, but high urgency escalates password_reset from L1 to Dev Team, so the team is right
        $labeled('bug', 'Dev Team', ['subject' => 'Wrong category', 'category' => 'password_reset', 'urgency' => 'high', 'confidence' => 0.62]);
        // 3: right category; an agent later re-routed it to L1, but the AI's own routing (bug -> Dev Team) is what's scored
        $labeled('bug', 'Dev Team', ['subject' => 'Agent re-routed', 'category' => 'bug', 'urgency' => 'medium', 'corrected_category' => 'password_reset', 'assigned_team' => 'L1 Support']);
        // 4: right category, but high urgency escalates it to Dev Team while the label says L1
        $labeled('password_reset', 'L1 Support', ['subject' => 'Wrong team', 'category' => 'password_reset', 'urgency' => 'high']);
        // 5: labeled, never analyzed: not scored
        $labeled('bug', 'Dev Team', ['subject' => 'Never analyzed', 'status' => 'new']);
        // 6: labeled and queued for re-analysis: still holds an old (wrong) answer, which must not be scored
        $labeled('bug', 'Dev Team', ['subject' => 'Waiting for re-analysis', 'status' => 'new', 'category' => 'password_reset', 'urgency' => 'low']);
        // 7: analyzed but not labeled (a live ticket): not part of the evaluation
        $this->ticket(['subject' => 'Live ticket', 'status' => 'needs_review', 'category' => 'bug', 'urgency' => 'low']);
    }

    public function test_accuracy_counts_only_labeled_and_analyzed_tickets(): void
    {
        $this->evaluationSet();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('evaluation', function ($e) {
                return $e['labeled'] === 6
                    && $e['analyzed'] === 4
                    && $e['categoryAccuracy'] === 75   // tickets 1, 3, 4
                    && $e['teamAccuracy'] === 75       // tickets 1, 2, 3
                    && $e['misses']->pluck('ticket.id')->all() === [2, 4];
            })
            ->assertSee('4 of 6 analyzed');
    }

    public function test_evaluation_page_lists_only_mismatches(): void
    {
        $this->evaluationSet();

        $this->get(route('evaluation'))
            ->assertOk()
            ->assertSee('Wrong category')
            ->assertSee('62%')                       // confidence column
            ->assertSee(route('tickets.show', 2))     // link to the ticket
            ->assertSee('Wrong team')
            ->assertDontSee('Both right')
            ->assertDontSee('Agent re-routed')
            ->assertDontSee('Waiting for re-analysis')
            ->assertDontSee('Live ticket');
    }

    public function test_evaluation_page_without_labels(): void
    {
        $this->get(route('evaluation'))->assertOk()->assertSee('No labeled tickets.');
    }
}
