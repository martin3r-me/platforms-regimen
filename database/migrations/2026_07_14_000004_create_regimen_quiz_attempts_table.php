<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_quiz_attempts')) {
            return;
        }

        Schema::create('regimen_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('regimen_quiz_id')->constrained('regimen_quizzes')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedTinyInteger('score_pct')->default(0);
            $table->boolean('passed')->default(false);
            $table->json('answers')->nullable(); // Snapshot: question_id => [option_id, ...]
            $table->timestamps();

            $table->index(['user_id', 'regimen_quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_quiz_attempts');
    }
};
