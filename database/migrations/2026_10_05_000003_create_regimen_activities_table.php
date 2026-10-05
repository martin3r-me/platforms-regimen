<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Absolvierte Aktivitäten (z.B. aus Garmin importiert). Eigenständig — eine
 * Aktivität existiert auch ohne Plan ("ungeplanter Lauf"). Das Matching verbindet
 * sie als Reconciliation mit einer geplanten regimen_plan_entry desselben Tages:
 * - Treffer:      activity.regimen_plan_entry_id gesetzt
 * - ungeplant:    activity ohne entry
 * - nicht gelaufen: entry ohne activity (status=missed)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_activities')) {
            return;
        }

        Schema::create('regimen_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();

            $table->string('source', 32)->default('garmin');   // garmin | manual | …
            $table->string('external_id')->nullable();          // Garmin activityId
            $table->string('sport', 32)->nullable();            // running | cycling | …

            $table->timestamp('started_at')->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->unsignedInteger('duration_s')->nullable();
            $table->unsignedSmallInteger('avg_pace_s_per_km')->nullable();
            $table->unsignedSmallInteger('avg_hr')->nullable();
            $table->json('raw')->nullable();                    // Roh-Payload des Connectors

            // Harte Match-FK auf die erfüllte Planeinheit (eine Aktivität erfüllt max.
            // eine Einheit). nullOnDelete: Aktivität überlebt das Löschen der Einheit.
            $table->foreignId('regimen_plan_entry_id')->nullable()
                ->constrained('regimen_plan_entries')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'source', 'external_id']);
            $table->unique('regimen_plan_entry_id'); // max. eine Aktivität pro Einheit
            $table->index(['user_id', 'started_at']);
            $table->index(['team_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_activities');
    }
};
