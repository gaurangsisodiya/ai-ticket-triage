<?php

namespace App\Providers;

use App\Models\Ticket;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Used by AnalyzeTicketJob so the queue doesn't exceed the LLM provider's rate limit.
        // 0 = no limit (e.g. a local Ollama model).
        RateLimiter::for('llm', function () {
            $perMinute = (int) config('ai.llm.requests_per_minute');

            return $perMinute > 0 ? Limit::perMinute($perMinute) : Limit::none();
        });

        // Review queue size, shown next to the sidebar link on every page.
        View::composer('components.layout', function ($view) {
            $view->with('navCounts', ['review' => Ticket::where('status', 'needs_review')->count()]);
        });
    }
}
