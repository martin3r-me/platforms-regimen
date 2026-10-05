<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_plan_enrollments')) {
            return;
        }

        Schema::create('regimen_plan_enrollments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('regimen_plan_id')->constrained('regimen_plans')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('status', 32)->default('active'); // active | completed
            // Anker-Tag des persönlichen Plans (i.d.R. ein Montag). Aus start_date +
            // week/weekday werden die datierten regimen_plan_entries materialisiert.
            $table->date('start_date')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('last_session_id')->nullable()
                ->constrained('regimen_sessions')->nullOnDelete(); // Resume-Punkt
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'regimen_plan_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_plan_enrollments');
    }
};
