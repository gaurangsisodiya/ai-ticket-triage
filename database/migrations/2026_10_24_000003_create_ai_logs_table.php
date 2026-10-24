<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per LLM call, successful or not. This is the audit trail: when the AI
        // gets something wrong we can see exactly what it was sent and what it returned.
        Schema::create('ai_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->longText('prompt');
            $table->longText('raw_response')->nullable();   // null if the HTTP call itself failed
            $table->boolean('parsed_ok')->default(false);
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
