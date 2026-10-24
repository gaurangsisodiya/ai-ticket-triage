<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // What the customer submits
            $table->string('subject');
            $table->text('body');
            $table->string('customer_email');

            // Lifecycle: new -> processing -> auto_resolved | needs_review -> resolved
            $table->string('status')->default('new')->index();

            // Enrichment filled in by the AI. Nullable until the analysis has run.
            $table->string('category')->nullable()->index();
            $table->string('urgency')->nullable();          // low | medium | high
            $table->string('summary', 500)->nullable();
            $table->string('sentiment')->nullable();        // positive | neutral | negative | angry
            $table->string('assigned_team')->nullable()->index();

            // Auto-resolution data
            $table->foreignId('kb_article_id')->nullable()->constrained('kb_articles')->nullOnDelete();
            $table->decimal('confidence', 3, 2)->nullable(); // 0.00 - 1.00
            $table->text('ai_reply')->nullable();
            $table->string('resolved_by')->nullable();      // ai | human | null

            // Set when a human overrides the AI's category. Used for the accuracy metric.
            $table->string('corrected_category')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
