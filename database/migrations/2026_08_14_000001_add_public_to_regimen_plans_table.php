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
            if (!Schema::hasColumn('regimen_plans', 'public')) {
                // Freigabe für die öffentliche Website. Getrennt vom Redaktions-
                // Status: nur Kurse mit status=published UND public=true werden
                // über die Public Plan API ausgeliefert.
                $table->boolean('public')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('regimen_plans')) {
            return;
        }

        Schema::table('regimen_plans', function (Blueprint $table) {
            if (Schema::hasColumn('regimen_plans', 'public')) {
                $table->dropColumn('public');
            }
        });
    }
};
