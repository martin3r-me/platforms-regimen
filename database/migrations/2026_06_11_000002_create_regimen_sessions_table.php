<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_sessions')) {
            return;
        }

        Schema::create('regimen_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('regimen_topic_id')->constrained('regimen_topics')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('content')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();

            // --- Lauf-spezifisch (hybrid: Skalare für Listen/Matching, JSON für den Workout-Push) ---
            // Art der Einheit: easy | long | tempo | interval | recovery | rest | race
            $table->string('kind', 32)->nullable();
            $table->unsignedInteger('target_distance_m')->nullable();   // Zieldistanz in Metern
            $table->unsignedInteger('target_duration_s')->nullable();   // Zieldauer in Sekunden
            $table->unsignedSmallInteger('target_pace_s_per_km')->nullable(); // Zielpace Sek/km
            $table->string('intensity', 32)->nullable();                // z.B. Zone/Effort-Label
            // Strukturierter Workout (Warmup → Repeats → Cooldown), Garmin-kompatibler Step-Baum.
            $table->json('structure')->nullable();

            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['regimen_topic_id', 'slug']);
            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_sessions');
    }
};
