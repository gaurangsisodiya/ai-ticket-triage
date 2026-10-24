<?php

/*
|--------------------------------------------------------------------------
| AI / LLM configuration
|--------------------------------------------------------------------------
|
| Application code reads these values through config('ai.*') and never calls
| env() directly. That way `php artisan config:cache` keeps working, and
| switching provider (Groq, Gemini, Ollama, ...) only means editing .env.
|
*/

return [

    'llm' => [
        // Label only, shown in logs and the UI. Every provider is called the same way.
        'provider' => env('LLM_PROVIDER', 'groq'),

        // Base URL of an OpenAI-compatible API. "/chat/completions" gets appended to it.
        'base_url' => rtrim(env('LLM_BASE_URL', 'https://api.groq.com/openai/v1'), '/'),

        'api_key' => env('LLM_API_KEY'),
        'model' => env('LLM_MODEL', 'openai/gpt-oss-120b'),

        // Seconds to wait for the LLM before we give up and mark the ticket for review.
        'timeout' => (int) env('LLM_TIMEOUT', 30),

        // Send response_format={"type":"json_object"}, which makes the provider guarantee valid JSON.
        // OpenAI, Groq and Ollama support it. Set to false for a provider that rejects the
        // parameter; the prompt still asks for JSON and the parser copes with extra text.
        'json_mode' => (bool) env('LLM_JSON_MODE', true),

        // Max analysis jobs per minute, to stay under the provider's rate limit.
        // Groq free tier: 8,000 tokens/min / ~3,000 tokens per ticket = 2. Use 0 for no limit.
        'requests_per_minute' => (int) env('LLM_REQUESTS_PER_MINUTE', 2),
    ],

    // A ticket is auto-resolved only if it matches a KB article AND confidence >= this value.
    'auto_resolve_threshold' => (float) env('AI_AUTO_RESOLVE_THRESHOLD', 0.8),

    'routing' => [
        // Gets tickets whose category has no team, and tickets the AI couldn't analyze.
        'first_line_team' => env('ROUTING_FIRST_LINE_TEAM', 'L1 Support'),

        // High-urgency tickets that would land on the first-line team go here instead.
        // Tickets already with a specialist team (e.g. Billing) stay where they are.
        'escalation_team' => env('ROUTING_ESCALATION_TEAM', 'Dev Team'),
    ],

];
