<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nachzügler-Migration: Wochen×7-Raster (week/weekday) an bereits migrierte
 * regimen_plan_sessions. Entfernt außerdem den alten unique(plan,session), damit
 * dieselbe Einheit an mehreren Tagen liegen darf.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regimen_plan_sessions')) {
            return;
        }

        Schema::table('regimen_plan_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('regimen_plan_sessions', 'week')) {
                $table->unsignedSmallInteger('week')->nullable()->after('regimen_session_id');
            }
            if (!Schema::hasColumn('regimen_plan_sessions', 'weekday')) {
                $table->unsignedTinyInteger('weekday')->nullable()->after('week');
            }
        });

        // Alten unique(plan,session) entfernen (falls vorhanden).
        try {
            Schema::table('regimen_plan_sessions', fn (Blueprint $t) => $t->dropUnique(['regimen_plan_id', 'regimen_session_id']));
        } catch (\Throwable $e) {
            // Kein solcher Index (z.B. Frisch-Installation) — ignorieren.
        }

        try {
            Schema::table('regimen_plan_sessions', fn (Blueprint $t) => $t->index(['regimen_plan_id', 'week', 'weekday'], 'regimen_plan_sessions_plan_week_weekday_index'));
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('regimen_plan_sessions')) {
            return;
        }

        try {
            Schema::table('regimen_plan_sessions', fn (Blueprint $t) => $t->dropIndex('regimen_plan_sessions_plan_week_weekday_index'));
        } catch (\Throwable $e) {
        }

        Schema::table('regimen_plan_sessions', function (Blueprint $table) {
            foreach (['week', 'weekday'] as $col) {
                if (Schema::hasColumn('regimen_plan_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
