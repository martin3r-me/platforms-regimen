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
            if (!Schema::hasColumn('regimen_plans', 'regimen_category_id')) {
                $table->foreignId('regimen_category_id')
                    ->nullable()
                    ->after('team_id')
                    ->constrained('regimen_categories')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('regimen_plans', 'code')) {
                $table->string('code', 32)->nullable()->after('title');
            }
            if (!Schema::hasColumn('regimen_plans', 'level')) {
                $table->string('level', 16)->nullable()->after('code');
            }
        });

        // Unique-Index fuer Kurs-Codes je Team (nur einmal anlegen).
        if (!$this->indexExists('regimen_plans', 'regimen_plans_team_id_code_unique')) {
            Schema::table('regimen_plans', function (Blueprint $table) {
                $table->unique(['team_id', 'code'], 'regimen_plans_team_id_code_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('regimen_plans')) {
            return;
        }

        Schema::table('regimen_plans', function (Blueprint $table) {
            if ($this->indexExists('regimen_plans', 'regimen_plans_team_id_code_unique')) {
                $table->dropUnique('regimen_plans_team_id_code_unique');
            }
            if (Schema::hasColumn('regimen_plans', 'regimen_category_id')) {
                $table->dropConstrainedForeignId('regimen_category_id');
            }
            if (Schema::hasColumn('regimen_plans', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('regimen_plans', 'level')) {
                $table->dropColumn('level');
            }
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = method_exists($connection, 'getDoctrineSchemaManager')
            ? $connection->getDoctrineSchemaManager()
            : null;

        if ($schemaManager) {
            return array_key_exists($index, $schemaManager->listTableIndexes($table));
        }

        // Fallback fuer neuere Laravel-Versionen ohne Doctrine.
        return collect(Schema::getIndexes($table))
            ->contains(fn ($i) => ($i['name'] ?? null) === $index);
    }
};
