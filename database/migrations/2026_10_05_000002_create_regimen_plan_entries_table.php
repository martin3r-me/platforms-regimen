<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persönlicher, datierter Plan: das aus einem Plan-Template auf echte Kalender-
 * tage materialisierte Trainingsprogramm einer Person. Eine Zeile = eine geplante
 * Einheit an einem konkreten Datum. Dies ist die Match-Fläche für den Garmin-
 * Connector (Push zur Uhr + Reconciliation mit absolvierten Aktivitäten).
 *
 * Geplante Ziele werden als SNAPSHOT kopiert (nicht nur per FK auf die Session),
 * damit ein laufender Plan stabil bleibt, wenn das Template später editiert wird.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_plan_entries')) {
            return;
        }

        Schema::create('regimen_plan_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('regimen_plan_enrollment_id')
                ->constrained('regimen_plan_enrollments')->cascadeOnDelete();
            // Denormalisiert für schnelle Abfragen "mein Plan am Tag X".
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('regimen_plan_id')->constrained('regimen_plans')->cascadeOnDelete();
            // Herkunfts-Session (nullable, damit gelöschte Template-Einheiten den
            // persönlichen Plan nicht zerreißen — der Snapshot bleibt erhalten).
            $table->foreignId('regimen_session_id')->nullable()
                ->constrained('regimen_sessions')->nullOnDelete();

            $table->date('scheduled_date');
            $table->unsignedSmallInteger('week')->nullable();
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            // --- Snapshot der geplanten Werte ---
            $table->string('title');
            $table->string('kind', 32)->nullable();
            $table->unsignedInteger('target_distance_m')->nullable();
            $table->unsignedInteger('target_duration_s')->nullable();
            $table->unsignedSmallInteger('target_pace_s_per_km')->nullable();
            $table->json('structure')->nullable();

            // planned | completed | skipped | missed
            $table->string('status', 32)->default('planned');
            $table->timestamp('completed_at')->nullable();

            // --- Ist-Werte (Reconciliation) ---
            $table->string('source', 32)->nullable();          // manual | garmin
            $table->unsignedInteger('actual_distance_m')->nullable();
            $table->unsignedInteger('actual_duration_s')->nullable();
            $table->unsignedSmallInteger('actual_pace_s_per_km')->nullable();
            // Soft-Ref auf die gematchte Aktivität (harte FK liegt auf der Activity-Seite).
            $table->unsignedBigInteger('regimen_activity_id')->nullable();
            $table->timestamp('matched_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'scheduled_date']);
            $table->index(['regimen_plan_enrollment_id', 'scheduled_date']);
            $table->index(['team_id', 'scheduled_date']);
            $table->index(['user_id', 'status']);
            $table->index('regimen_activity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_plan_entries');
    }
};
