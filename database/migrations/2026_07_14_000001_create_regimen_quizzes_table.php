<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_quizzes')) {
            return;
        }

        Schema::create('regimen_quizzes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('regimen_session_id')->constrained('regimen_sessions')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->unsignedTinyInteger('pass_pct')->default(70); // Bestehensgrenze in %
            $table->boolean('shuffle_questions')->default(true);
            $table->timestamps();

            // Genau ein Quiz pro Lektion.
            $table->unique('regimen_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_quizzes');
    }
};
