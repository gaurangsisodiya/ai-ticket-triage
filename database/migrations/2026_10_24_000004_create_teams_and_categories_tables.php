<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Teams and categories are data, not code. Each demo dataset (ERP, GovTech, ...)
     * seeds its own, so the triage engine works for any product domain unchanged.
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();        // e.g. "L1 Support"
            $table->string('description');           // shown to the LLM so it understands the team
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();        // what the LLM returns and tickets.category stores
            $table->string('name');
            $table->string('description');           // shown to the LLM; good descriptions = better classification
            // The team that normally handles this category. Routing starts from here.
            $table->foreignId('team_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
        Schema::dropIfExists('teams');
    }
};
