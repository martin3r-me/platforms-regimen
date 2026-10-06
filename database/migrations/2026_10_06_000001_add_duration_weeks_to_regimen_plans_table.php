<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nachzügler-Migration: duration_weeks an bereits migrierte regimen_plans.
 * (Die create-Migration enthält die Spalte ebenfalls — für Alt-Instanzen, auf
 * denen create bereits lief, greift nur diese ALTER-Migration.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regimen_plans') || Schema::hasColumn('regimen_plans', 'duration_weeks')) {
            return;
        }

        Schema::table('regimen_plans', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration_weeks')->nullable()->after('target_audience');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('regimen_plans') && Schema::hasColumn('regimen_plans', 'duration_weeks')) {
            Schema::table('regimen_plans', fn (Blueprint $t) => $t->dropColumn('duration_weeks'));
        }
    }
};
