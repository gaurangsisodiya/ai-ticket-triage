<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'subject', 'body', 'customer_email', 'status',
    'category', 'urgency', 'summary', 'sentiment', 'assigned_team',
    'kb_article_id', 'confidence', 'ai_reply', 'resolved_by', 'corrected_category',
])]
class Ticket extends Model
{
    // These values belong to the triage process itself, so they are the same in every
    // product domain. Categories and teams depend on the domain, so they live in the
    // categories/teams tables and are seeded per dataset.
    public const STATUSES = ['new', 'processing', 'auto_resolved', 'needs_review', 'resolved'];

    public const URGENCIES = ['low', 'medium', 'high'];

    public const SENTIMENTS = ['positive', 'neutral', 'negative', 'angry'];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
        ];
    }

    public function kbArticle(): BelongsTo
    {
        return $this->belongsTo(KbArticle::class);
    }

    public function aiLogs(): HasMany
    {
        return $this->hasMany(AiLog::class);
    }

    /**
     * The category we treat as correct: the human correction if there is one,
     * otherwise what the AI chose.
     */
    public function effectiveCategory(): ?string
    {
        return $this->corrected_category ?? $this->category;
    }

    /** Human-friendly ticket reference, e.g. TKT-0042. */
    public function reference(): string
    {
        return 'TKT-' . str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
    }

    public function isAwaitingAnalysis(): bool
    {
        return in_array($this->status, ['new', 'processing'], true);
    }

    /**
     * One sentence explaining why the ticket is in its current state, shown on the detail page.
     * Being able to explain every automated decision matters as much as the decision itself.
     */
    public function decisionReason(): string
    {
        $threshold = (int) round(config('ai.auto_resolve_threshold') * 100);
        $confidence = $this->confidence !== null ? (int) round($this->confidence * 100) : null;

        return match (true) {
            $this->isAwaitingAnalysis() => 'Waiting for AI analysis.',
            $this->resolved_by === 'human' => 'Resolved by a human agent.',
            $this->status === 'auto_resolved' => "Auto-resolved: matched \"{$this->kbArticle?->title}\" with {$confidence}% confidence (threshold {$threshold}%).",
            $this->category === null => 'The AI analysis failed, so a human needs to triage this ticket. See the AI log below.',
            $this->corrected_category !== null => "A human corrected the AI's category, so the ticket was re-routed for human review.",
            $this->kb_article_id !== null && $this->urgency === 'high' => 'A knowledge base article matched, but high-urgency tickets always go to a human.',
            $this->kb_article_id !== null && $this->sentiment === 'angry' => 'A knowledge base article matched, but angry customers always get a human reply.',
            $this->kb_article_id !== null && $confidence < $threshold => "A knowledge base article matched, but confidence {$confidence}% is below the {$threshold}% threshold.",
            $this->kb_article_id !== null => 'A knowledge base article matched with enough confidence, but the ticket was sent back for human review.',
            default => 'No knowledge base article fully answers this ticket, so it needs a human.',
        };
    }
}
