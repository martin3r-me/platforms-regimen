<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_plans')) {
            return;
        }

        Schema::create('regimen_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 32)->nullable();
            $table->string('target_audience')->nullable();
            // Länge des Plan-Templates in Wochen (treibt das Wochen×7-Raster).
            $table->unsignedSmallInteger('duration_weeks')->nullable();
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_plans');
    }
};
