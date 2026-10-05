<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('regimen_certificates')) {
            return;
        }

        Schema::create('regimen_certificates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('regimen_plan_id')->constrained('regimen_plans')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('serial')->unique();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();

            // Ein Zertifikat pro User und Kurs.
            $table->unique(['user_id', 'regimen_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regimen_certificates');
    }
};
