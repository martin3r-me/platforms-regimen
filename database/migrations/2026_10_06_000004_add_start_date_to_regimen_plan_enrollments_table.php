<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nachzügler-Migration: start_date (Anker für den datierten persönlichen Plan)
 * an bereits migrierte regimen_plan_enrollments.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regimen_plan_enrollments') || Schema::hasColumn('regimen_plan_enrollments', 'start_date')) {
            return;
        }

        Schema::table('regimen_plan_enrollments', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('regimen_plan_enrollments') && Schema::hasColumn('regimen_plan_enrollments', 'start_date')) {
            Schema::table('regimen_plan_enrollments', fn (Blueprint $t) => $t->dropColumn('start_date'));
        }
    }
};
