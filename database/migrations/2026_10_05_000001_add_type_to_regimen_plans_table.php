<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regimen_plans')) {
            return;
        }

        Schema::table('regimen_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('regimen_plans', 'type')) {
                // Sportart-Gattung des Plans. Erweiterbar — erste Typen:
                // running = Laufplan, equipment = Fitnessgeräte-Plan.
                $table->string('type')->default('running')->after('level');
            }
        });

        Schema::table('regimen_plans', function (Blueprint $table) {
            $table->index(['team_id', 'type'], 'regimen_plans_team_id_type_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('regimen_plans')) {
            return;
        }

        Schema::table('regimen_plans', function (Blueprint $table) {
            $table->dropIndex('regimen_plans_team_id_type_index');
        });

        Schema::table('regimen_plans', function (Blueprint $table) {
            if (Schema::hasColumn('regimen_plans', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
