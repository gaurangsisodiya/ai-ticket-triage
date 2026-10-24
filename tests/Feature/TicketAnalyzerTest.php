<?php

namespace Tests\Feature;

use App\Models\AiLog;
use App\Models\Category;
use App\Models\KbArticle;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\TicketAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * Tests the analyzer against a fake LLM (Http::fake), so no API key or network is needed.
 */
class TicketAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.llm.base_url' => 'https://llm.test/v1',
            'ai.llm.api_key' => 'test-key',
            'ai.llm.model' => 'test-model',
        ]);
        Sleep::fake(); // skip the 1s pauses between retries

        // A minimal domain: one team, one category, one KB article.
        $team = Team::create(['name' => 'L1 Support', 'description' => 'First line']);
        Category::create(['slug' => 'password_reset', 'name' => 'Password reset', 'description' => 'Forgotten passwords', 'team_id' => $team->id]);
        KbArticle::create(['slug' => 'password-reset', 'title' => 'Reset your password', 'content' => 'Click Forgot password.']);

        $this->ticket = Ticket::create([
            'subject' => 'Forgot password',
            'body' => 'I forgot my password.',
            'customer_email' => 'a@example.com',
        ]);
    }

    /** Wrap model text the way an OpenAI-compatible API returns it. */
    private function llmReply(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    private function validAnalysis(array $overrides = []): array
    {
        return array_merge([
            'category' => 'password_reset',
            'urgency' => 'low',
            'summary' => 'Customer forgot their password.',
            'sentiment' => 'neutral',
            'team' => 'L1 Support',
            'kb_match' => 'password-reset',
            'confidence' => 0.95,
            'reply_draft' => 'Hi, click Forgot password. Support Team',
        ], $overrides);
    }

    public function test_valid_response_is_returned_and_logged(): void
    {
        Http::fake(['*' => Http::response($this->llmReply(json_encode($this->validAnalysis())))]);

        $result = app(TicketAnalyzer::class)->analyze($this->ticket);

        $this->assertSame('password_reset', $result['category']);
        $this->assertSame(0.95, $result['confidence']);
        $this->assertTrue(AiLog::sole()->parsed_ok);

        // The request uses settings from config: endpoint, key, model, JSON mode.
        Http::assertSent(fn (Request $r) => $r->url() === 'https://llm.test/v1/chat/completions'
            && $r->hasHeader('Authorization', 'Bearer test-key')
            && $r['model'] === 'test-model'
            && $r['response_format'] === ['type' => 'json_object']);
    }

    public function test_json_wrapped_in_code_fences_is_still_parsed(): void
    {
        $content = "Here is the analysis:\n```json\n".json_encode($this->validAnalysis(['urgency' => 'LOW']))."\n```";
        Http::fake(['*' => Http::response($this->llmReply($content))]);

        $result = app(TicketAnalyzer::class)->analyze($this->ticket);

        $this->assertSame('low', $result['urgency']); // also normalised from "LOW"
    }

    public function test_invalid_values_fail_validation(): void
    {
        $bad = $this->validAnalysis(['category' => 'made_up', 'confidence' => 1.5, 'team' => 'Sales']);
        Http::fake(['*' => Http::response($this->llmReply(json_encode($bad)))]);

        $this->assertNull(app(TicketAnalyzer::class)->analyze($this->ticket));

        $log = AiLog::sole();
        $this->assertFalse($log->parsed_ok);
        $this->assertStringContainsString('category', $log->error);
        $this->assertStringContainsString('confidence', $log->error);
        $this->assertStringContainsString('team', $log->error);
    }

    public function test_missing_key_and_kb_match_without_reply_fail_validation(): void
    {
        $bad = $this->validAnalysis(['reply_draft' => null]);
        unset($bad['sentiment']);
        Http::fake(['*' => Http::response($this->llmReply(json_encode($bad)))]);

        $this->assertNull(app(TicketAnalyzer::class)->analyze($this->ticket));
        $this->assertStringContainsString('sentiment', AiLog::sole()->error);
        $this->assertStringContainsString('reply draft', AiLog::sole()->error);
    }

    public function test_non_json_response_fails(): void
    {
        Http::fake(['*' => Http::response($this->llmReply('Sorry, I cannot help with that.'))]);

        $this->assertNull(app(TicketAnalyzer::class)->analyze($this->ticket));
        $this->assertSame('LLM response is not valid JSON', AiLog::sole()->error);
    }

    public function test_server_error_is_retried_then_logged(): void
    {
        Http::fake(['*' => Http::response('upstream error', 503)]);

        $this->assertNull(app(TicketAnalyzer::class)->analyze($this->ticket));
        Http::assertSentCount(3);
        $this->assertSame('HTTP 503 from LLM provider', AiLog::sole()->error);
    }

    public function test_rate_limit_waits_for_retry_after_then_succeeds(): void
    {
        Http::fakeSequence()
            ->push(['error' => 'rate limited'], 429, ['Retry-After' => '5'])
            ->push($this->llmReply(json_encode($this->validAnalysis())));

        $this->assertNotNull(app(TicketAnalyzer::class)->analyze($this->ticket));
        Http::assertSentCount(2);
        Sleep::assertSlept(fn ($duration) => (int) $duration->totalMilliseconds === 5000);
    }

    public function test_bad_api_key_is_not_retried(): void
    {
        Http::fake(['*' => Http::response(['error' => 'invalid key'], 401)]);

        $this->assertNull(app(TicketAnalyzer::class)->analyze($this->ticket));
        Http::assertSentCount(1);
        $this->assertSame('HTTP 401 from LLM provider', AiLog::sole()->error);
    }

    public function test_connection_failure_never_throws(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->assertNull(app(TicketAnalyzer::class)->analyze($this->ticket));
        $this->assertStringContainsString('ConnectionException', AiLog::sole()->error);
    }
}
