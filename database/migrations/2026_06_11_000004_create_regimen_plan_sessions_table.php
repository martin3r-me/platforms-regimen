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
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['regimen_plan_id', 'regimen_session_id']);
            $table->index(['regimen_plan_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_plan_sessions');
    }
};
