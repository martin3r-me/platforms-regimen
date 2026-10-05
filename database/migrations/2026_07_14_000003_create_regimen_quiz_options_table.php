<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_quiz_options')) {
            return;
        }

        Schema::create('regimen_quiz_options', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('regimen_quiz_question_id')->constrained('regimen_quiz_questions')->cascadeOnDelete();
            $table->text('label');
            $table->boolean('is_correct')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('regimen_quiz_question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_quiz_options');
    }
};
