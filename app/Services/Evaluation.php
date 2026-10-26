<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Support\Collection;

/**
 * Scores the AI against the evaluation set: tickets whose correct category and team were
 * labeled by hand when they were seeded. Unlike agent corrections, this needs no agent
 * activity and also counts the mistakes nobody noticed.
 */
class Evaluation
{
    public function __construct(private TicketRouter $router)
    {
    }

    /**
     * @return array{labeled: int, analyzed: int, categoryAccuracy: ?int, teamAccuracy: ?int, misses: Collection}
     */
    public function run(): array
    {
        $labeled = Ticket::whereNotNull('expected_category')->count();

        // Scored = labeled AND analyzed. A ticket waiting for (re-)analysis still holds its previous
        // answer in `category`, so the status must show the analysis is finished as well.
        $tickets = Ticket::whereNotNull('expected_category')
            ->whereNotNull('category')
            ->whereNotIn('status', ['new', 'processing'])
            ->orderBy('id')
            ->get();

        $categoryNames = Category::pluck('name', 'slug');

        // Score the AI pipeline's own routing (AI category + AI urgency through the routing rules),
        // not assigned_team, which an agent may have changed by correcting the category.
        // Cached per category/urgency pair, since route() queries the database.
        $routes = [];
        $aiTeam = function (Ticket $t) use (&$routes): string {
            return $routes["{$t->category}|{$t->urgency}"] ??= $this->router->route($t->category, $t->urgency ?? 'low');
        };

        $results = $tickets->map(fn (Ticket $t) => [
            'ticket' => $t,
            'expected_category' => $categoryNames[$t->expected_category] ?? $t->expected_category,
            'ai_category' => $categoryNames[$t->category] ?? $t->category,
            'category_ok' => $t->category === $t->expected_category,
            'expected_team' => $t->expected_team,
            'ai_team' => $aiTeam($t),
            'team_ok' => $aiTeam($t) === $t->expected_team,
        ]);

        $analyzed = $results->count();

        return [
            'labeled' => $labeled,
            'analyzed' => $analyzed,
            'categoryAccuracy' => $this->percent($results->where('category_ok', true)->count(), $analyzed),
            'teamAccuracy' => $this->percent($results->where('team_ok', true)->count(), $analyzed),
            'misses' => $results->reject(fn ($row) => $row['category_ok'] && $row['team_ok'])->values(),
        ];
    }

    /** Whole-number percentage, or null when there is nothing to divide by yet. */
    private function percent(int $part, int $whole): ?int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : null;
    }
}
