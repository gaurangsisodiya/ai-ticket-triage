<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Evaluation set: the correct answer for a ticket, decided by a person before the AI sees it.
 * Only seeded demo tickets have these. Tickets submitted through the form leave them null
 * and are not part of the evaluation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('expected_category')->nullable()->after('corrected_category');
            $table->string('expected_team')->nullable()->after('expected_category');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['expected_category', 'expected_team']);
        });
    }
};
