<?php

namespace App\Services;

use App\Models\Category;
use App\Models\KbArticle;
use App\Models\Team;
use App\Models\Ticket;

/**
 * Turns the AI's analysis into decisions: which team gets the ticket,
 * and whether the AI may resolve it on its own.
 *
 * These are plain PHP rules on purpose. The AI provides the inputs (category, urgency,
 * KB match, confidence), but the decisions stay predictable and easy to audit or change.
 */
class TicketRouter
{
    /**
     * Save the analysis and the routing decision on the ticket.
     *
     * @param  array|null  $analysis  validated output of TicketAnalyzer, or null if the analysis failed
     */
    public function apply(Ticket $ticket, ?array $analysis): void
    {
        if ($analysis === null) {
            // No AI data: a human must triage it. Give it an owner so it doesn't sit unassigned.
            $ticket->update([
                'status' => 'needs_review',
                'assigned_team' => config('ai.routing.first_line_team'),
                'resolved_by' => null,
            ]);

            return;
        }

        $autoResolve = $this->canAutoResolve($analysis['kb_match'], $analysis['confidence'], $analysis['urgency'], $analysis['sentiment']);

        $ticket->update([
            'category' => $analysis['category'],
            'urgency' => $analysis['urgency'],
            'summary' => $analysis['summary'],
            'sentiment' => $analysis['sentiment'],
            'assigned_team' => $this->route($analysis['category'], $analysis['urgency']),
            'kb_article_id' => $analysis['kb_match'] ? KbArticle::where('slug', $analysis['kb_match'])->value('id') : null,
            'confidence' => $analysis['confidence'],
            // Kept even when not auto-resolving: it becomes a suggested reply for the human reviewer.
            'ai_reply' => $analysis['reply_draft'],
            'status' => $autoResolve ? 'auto_resolved' : 'needs_review',
            'resolved_by' => $autoResolve ? 'ai' : null,
        ]);
    }

    /**
     * Team = the category's default team, escalated when urgent.
     */
    public function route(string $categorySlug, string $urgency): string
    {
        $firstLine = config('ai.routing.first_line_team');
        $escalation = config('ai.routing.escalation_team');

        $team = Category::with('team')->firstWhere('slug', $categorySlug)?->team->name ?? $firstLine;

        // Only escalate away from first line. A high-urgency billing ticket should still go
        // to Billing, not to engineers. The exists() check keeps routing safe if a dataset
        // has no team with that name.
        if ($urgency === 'high' && $team === $firstLine && Team::where('name', $escalation)->exists()) {
            return $escalation;
        }

        return $team;
    }

    /**
     * The AI may close the ticket only when it found a KB article AND is confident enough,
     * and the ticket is not high urgency. An outage can look exactly like a how-to question
     * ("no bulletins delivered since 8am" matches the delivery-troubleshooting article), so
     * urgent tickets always get a human, however confident the model is. Angry customers
     * do too: a canned reply to someone who is already upset usually makes things worse.
     */
    public function canAutoResolve(?string $kbMatch, float $confidence, ?string $urgency = null, ?string $sentiment = null): bool
    {
        return $kbMatch !== null
            && $urgency !== 'high'
            && $sentiment !== 'angry'
            && $confidence >= (float) config('ai.auto_resolve_threshold');
    }
}
