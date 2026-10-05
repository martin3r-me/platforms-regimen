<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_user_assignments')) {
            return;
        }

        Schema::create('regimen_user_assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('regimen_plan_assignment_id')
                ->constrained('regimen_plan_assignments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('regimen_plan_id')->constrained('regimen_plans')->cascadeOnDelete();
            $table->foreignId('regimen_plan_enrollment_id')->nullable()
                ->constrained('regimen_plan_enrollments')->nullOnDelete();
            $table->boolean('is_mandatory')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('due_at')->nullable();
            $table->string('status', 16)->default('assigned'); // assigned|in_progress|completed|overdue|revoked
            $table->timestamp('completed_at')->nullable();
            $table->string('reminded_stage', 16)->nullable();  // due_soon | overdue
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();

            $table->unique(['regimen_plan_assignment_id', 'user_id']);
            $table->index(['user_id', 'status']);
            $table->index('regimen_plan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_user_assignments');
    }
};
