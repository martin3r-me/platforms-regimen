<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nachzügler-Migration: Lauf-Felder an bereits migrierte regimen_sessions.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regimen_sessions')) {
            return;
        }

        Schema::table('regimen_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('regimen_sessions', 'kind')) {
                $table->string('kind', 32)->nullable()->after('estimated_minutes');
            }
            if (!Schema::hasColumn('regimen_sessions', 'target_distance_m')) {
                $table->unsignedInteger('target_distance_m')->nullable()->after('kind');
            }
            if (!Schema::hasColumn('regimen_sessions', 'target_duration_s')) {
                $table->unsignedInteger('target_duration_s')->nullable()->after('target_distance_m');
            }
            if (!Schema::hasColumn('regimen_sessions', 'target_pace_s_per_km')) {
                $table->unsignedSmallInteger('target_pace_s_per_km')->nullable()->after('target_duration_s');
            }
            if (!Schema::hasColumn('regimen_sessions', 'intensity')) {
                $table->string('intensity', 32)->nullable()->after('target_pace_s_per_km');
            }
            if (!Schema::hasColumn('regimen_sessions', 'structure')) {
                $table->json('structure')->nullable()->after('intensity');
            }
        });

        try {
            Schema::table('regimen_sessions', fn (Blueprint $t) => $t->index(['team_id', 'kind'], 'regimen_sessions_team_id_kind_index'));
        } catch (\Throwable $e) {
            // Index existiert bereits — ignorieren.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('regimen_sessions')) {
            return;
        }

        try {
            Schema::table('regimen_sessions', fn (Blueprint $t) => $t->dropIndex('regimen_sessions_team_id_kind_index'));
        } catch (\Throwable $e) {
        }

        Schema::table('regimen_sessions', function (Blueprint $table) {
            foreach (['kind', 'target_distance_m', 'target_duration_s', 'target_pace_s_per_km', 'intensity', 'structure'] as $col) {
                if (Schema::hasColumn('regimen_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
