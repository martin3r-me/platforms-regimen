<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Entfernt die Quiz-/Concept-Check-Tabellen — für Trainingspläne nicht sinnvoll.
 * Reihenfolge: abhängige Tabellen zuerst (FKs). Kein down() mit Re-Create:
 * das Feature ist bewusst entfernt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('regimen_quiz_attempts');
        Schema::dropIfExists('regimen_quiz_options');
        Schema::dropIfExists('regimen_quiz_questions');
        Schema::dropIfExists('regimen_quizzes');
    }

    public function down(): void
    {
        // Bewusst leer — das Quiz-Feature wurde dauerhaft entfernt.
    }
};
