<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Jobs\AnalyzeTicketJob;
use App\Models\Category;
use App\Models\Team;
use App\Models\Ticket;
use App\Services\TicketRouter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'category', 'team', 'urgency']);

        // when() only adds a condition if the filter has a value, so an empty form shows everything.
        $tickets = Ticket::query()
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where(fn ($q) => $q
                ->where('subject', 'like', "%{$term}%")
                ->orWhere('customer_email', 'like', "%{$term}%")
                ->orWhere('summary', 'like', "%{$term}%")))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            // Filter on the category we believe is right: a human correction overrides the AI.
            ->when($filters['category'] ?? null, fn ($query, $slug) => $query->whereRaw('COALESCE(corrected_category, category) = ?', [$slug]))
            ->when($filters['team'] ?? null, fn ($query, $team) => $query->where('assigned_team', $team))
            ->when($filters['urgency'] ?? null, fn ($query, $urgency) => $query->where('urgency', $urgency))
            ->latest('id')
            ->paginate(20)
            ->withQueryString(); // keep the filters in the pagination links

        return view('tickets.index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get()->keyBy('slug'),
            'teams' => Team::orderBy('name')->pluck('name'),
            'reviewCount' => Ticket::where('status', 'needs_review')->count(),
        ]);
    }

    public function create(): View
    {
        return view('tickets.create');
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = Ticket::create($request->validated() + ['status' => 'new']);

        // The LLM call can take seconds (longer if we're rate-limited), so it runs on the
        // queue and the customer gets an instant response.
        AnalyzeTicketJob::dispatch($ticket);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket #{$ticket->id} received. Our AI assistant is analyzing it now.");
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load('kbArticle');

        return view('tickets.show', [
            'ticket' => $ticket,
            'categories' => Category::with('team')->orderBy('name')->get()->keyBy('slug'),
            'logs' => $ticket->aiLogs()->latest('id')->get(),
        ]);
    }

    /**
     * A human overrides the AI's category. This feeds the accuracy metric on the dashboard.
     */
    public function correctCategory(Request $request, Ticket $ticket, TicketRouter $router): RedirectResponse
    {
        $slug = $request->validate([
            'category' => ['required', Rule::exists('categories', 'slug')],
        ])['category'];

        // Choosing the AI's own category again means "the AI was right": clear the correction.
        $ticket->corrected_category = $slug === $ticket->category ? null : $slug;

        // Re-route with the corrected category, using the same rules as the AI path.
        $ticket->assigned_team = $router->route($slug, $ticket->urgency ?? 'low');

        // Re-apply the auto-resolve rule (tickets a human already resolved are left alone):
        // - corrected: the AI misunderstood the ticket, so its answer is suspect -> human review
        // - correction undone: the AI's answer stands again -> back to auto_resolved if it passed the threshold
        if ($ticket->status !== 'resolved') {
            $autoResolve = $ticket->corrected_category === null
                && $ticket->confidence !== null
                && $router->canAutoResolve($ticket->kbArticle?->slug, $ticket->confidence, $ticket->urgency, $ticket->sentiment);

            $ticket->status = $autoResolve ? 'auto_resolved' : 'needs_review';
            $ticket->resolved_by = $autoResolve ? 'ai' : null;
        }

        $ticket->save();

        return back()->with('success', $ticket->corrected_category
            ? "Category corrected. Ticket routed to {$ticket->assigned_team}."
            : 'Correction removed: the AI category is marked as correct.');
    }

    public function resolve(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['status' => 'resolved', 'resolved_by' => 'human']);

        return back()->with('success', "Ticket #{$ticket->id} marked as resolved.");
    }

    public function reanalyze(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['status' => 'new']);
        AnalyzeTicketJob::dispatch($ticket);

        return back()->with('success', 'Ticket queued for a new AI analysis.');
    }
}
