<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_plan_sessions')) {
            return;
        }

        Schema::create('regimen_plan_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regimen_plan_id')->constrained('regimen_plans')->cascadeOnDelete();
            $table->foreignId('regimen_session_id')->constrained('regimen_sessions')->cascadeOnDelete();
            // Position im Wochen×7-Raster: week 1..duration_weeks, weekday 1=Mo..7=So.
            // Nullable, damit eine Einheit auch (noch) ungeplant im Plan liegen kann.
            $table->unsignedSmallInteger('week')->nullable();
            $table->unsignedTinyInteger('weekday')->nullable();
            // Reihenfolge bei mehreren Einheiten am selben Tag (z.B. früh/abends).
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            // Bewusst KEIN unique(plan,session): dieselbe Einheit darf mehrfach im Plan
            // vorkommen (z.B. "Easy 5km" an Di und Do).
            $table->index(['regimen_plan_id', 'week', 'weekday']);
            $table->index(['regimen_plan_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_plan_sessions');
    }
};
