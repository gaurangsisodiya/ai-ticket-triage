<?php

namespace App\Http\Controllers;

use App\Models\AiLog;
use App\Models\Category;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): View
    {
        // "Finished" = the AI has had its turn (whether it succeeded, failed or a human took over).
        $finished = Ticket::whereNotIn('status', ['new', 'processing'])->count();
        $resolvedByAi = Ticket::where('resolved_by', 'ai')->count();

        // Accuracy only counts tickets the AI actually categorized. If the analysis failed,
        // there was no AI answer to be right or wrong about.
        $aiCategorized = Ticket::whereNotNull('category')->count();
        $corrected = Ticket::whereNotNull('category')->whereNotNull('corrected_category')->count();

        $aiCalls = AiLog::count();
        $aiCallsOk = AiLog::where('parsed_ok', true)->count();

        $categoryNames = Category::pluck('name', 'slug');

        return view('dashboard', [
            'resolutionRate' => $this->percent($resolvedByAi, $finished),
            'resolvedByAi' => $resolvedByAi,
            'finished' => $finished,
            'reviewQueue' => Ticket::where('status', 'needs_review')->count(),
            'awaitingAnalysis' => Ticket::whereIn('status', ['new', 'processing'])->count(),
            'accuracy' => $this->percent($aiCategorized - $corrected, $aiCategorized),
            'corrected' => $corrected,
            'aiCategorized' => $aiCategorized,
            'aiCallSuccess' => $this->percent($aiCallsOk, $aiCalls),
            'aiCallsFailed' => $aiCalls - $aiCallsOk,

            // Use the human correction when there is one, so the chart shows reality.
            'byCategory' => Ticket::query()
                ->selectRaw('COALESCE(corrected_category, category) AS slug, COUNT(*) AS total')
                ->whereRaw('COALESCE(corrected_category, category) IS NOT NULL')
                ->groupByRaw('COALESCE(corrected_category, category)')
                ->orderByDesc('total')
                ->get()
                ->mapWithKeys(fn ($row) => [$categoryNames[$row->slug] ?? $row->slug => (int) $row->total]),

            'byTeam' => Ticket::query()
                ->whereNotNull('assigned_team')
                ->select('assigned_team', DB::raw('COUNT(*) AS total'))
                ->groupBy('assigned_team')
                ->orderByDesc('total')
                ->pluck('total', 'assigned_team')
                ->map(fn ($total) => (int) $total),

            // Which mistakes does the AI make most? These point at category descriptions
            // in the prompt that need improving.
            'topCorrections' => Ticket::query()
                ->whereNotNull('category')
                ->whereNotNull('corrected_category')
                ->select('category', 'corrected_category', DB::raw('COUNT(*) AS total'))
                ->groupBy('category', 'corrected_category')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'ai' => $categoryNames[$row->category] ?? $row->category,
                    'human' => $categoryNames[$row->corrected_category] ?? $row->corrected_category,
                    'total' => (int) $row->total,
                ]),
        ]);
    }

    /** Whole-number percentage, or null when there is nothing to divide by yet. */
    private function percent(int $part, int $whole): ?int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : null;
    }
}
