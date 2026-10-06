<?php

namespace Platform\Regimen;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class RegimenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/regimen.php', 'regimen');
    }

    public function boot(): void
    {
        if (
            config()->has('regimen.routing') &&
            config()->has('regimen.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'regimen',
                'title'      => 'Regimen',
                'routing'    => config('regimen.routing'),
                'guard'      => config('regimen.guard'),
                'navigation' => config('regimen.navigation'),
                'sidebar'    => config('regimen.sidebar'),
            ]);
        }

        if (PlatformCore::getModule('regimen')) {
            ModuleRouter::group('regimen', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });

            // API-Routen (Prefix /api/regimen, Bearer-Token via 'api.auth').
            ModuleRouter::apiGroup('regimen', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
            });
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/regimen.php' => config_path('regimen.php'),
        ], 'config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'regimen');

        $this->registerLivewireComponents();

        $this->registerTools();

        // Pflichtkurse in die persönliche Sicht (home) einspeisen — über den
        // PersonActivityRegistry-Kontrakt der organization (falls vorhanden).
        try {
            $registryClass = \Platform\Organization\Services\PersonActivityRegistry::class;
            if (class_exists($registryClass)) {
                resolve($registryClass)->register(
                    new \Platform\Regimen\Organization\RegimenPersonActivityProvider()
                );
            }
        } catch (\Throwable $e) {
            // organization nicht verfügbar — Pflichtkurse erscheinen dann nur auf der eigenen Seite.
        }

        // Pflichtkurs-Wartung: Command + täglicher Scheduler.
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Platform\Regimen\Console\Commands\RunAssignmentMaintenanceCommand::class,
            ]);
        }

        $this->callAfterResolving(\Illuminate\Console\Scheduling\Schedule::class, function (\Illuminate\Console\Scheduling\Schedule $schedule) {
            $schedule->command('regimen:assignments-tick')->dailyAt('08:00')->withoutOverlapping();
        });
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Overview
            $registry->register(new \Platform\Regimen\Tools\RegimenOverviewTool());

            // Categories ("Schools")
            $registry->register(new \Platform\Regimen\Tools\ListRegimenCategoriesTool());
            $registry->register(new \Platform\Regimen\Tools\CreateRegimenCategoryTool());
            $registry->register(new \Platform\Regimen\Tools\UpdateRegimenCategoryTool());
            $registry->register(new \Platform\Regimen\Tools\DeleteRegimenCategoryTool());
            $registry->register(new \Platform\Regimen\Tools\SeedRegimenCategoriesTool());

            // Enrollments ("Meine Regimen")
            $registry->register(new \Platform\Regimen\Tools\ListMyEnrollmentsTool());
            $registry->register(new \Platform\Regimen\Tools\EnrollInPlanTool());
            $registry->register(new \Platform\Regimen\Tools\DropEnrollmentTool());

            // Topics
            $registry->register(new \Platform\Regimen\Tools\ListRegimenTopicsTool());
            $registry->register(new \Platform\Regimen\Tools\GetRegimenTopicTool());
            $registry->register(new \Platform\Regimen\Tools\CreateRegimenTopicTool());
            $registry->register(new \Platform\Regimen\Tools\UpdateRegimenTopicTool());
            $registry->register(new \Platform\Regimen\Tools\DeleteRegimenTopicTool());

            // Sessions (incl. content-ops on update)
            $registry->register(new \Platform\Regimen\Tools\ListRegimenSessionsTool());
            $registry->register(new \Platform\Regimen\Tools\GetRegimenSessionTool());
            $registry->register(new \Platform\Regimen\Tools\CreateRegimenSessionTool());
            $registry->register(new \Platform\Regimen\Tools\UpdateRegimenSessionTool());
            $registry->register(new \Platform\Regimen\Tools\DeleteRegimenSessionTool());


            // Plans + pivot
            $registry->register(new \Platform\Regimen\Tools\ListRegimenPlansTool());
            $registry->register(new \Platform\Regimen\Tools\GetRegimenPlanTool());
            $registry->register(new \Platform\Regimen\Tools\CreateRegimenPlanTool());
            $registry->register(new \Platform\Regimen\Tools\UpdateRegimenPlanTool());
            $registry->register(new \Platform\Regimen\Tools\DeleteRegimenPlanTool());
            $registry->register(new \Platform\Regimen\Tools\AttachSessionToPlanTool());
            $registry->register(new \Platform\Regimen\Tools\DetachSessionFromPlanTool());
            $registry->register(new \Platform\Regimen\Tools\ReorderPlanSessionsTool());
            $registry->register(new \Platform\Regimen\Tools\AddSessionToPlanDayTool());
            $registry->register(new \Platform\Regimen\Tools\AssignPlanTool());

            // Kurs-Zuweisungen / Pflichtkurse
            $registry->register(new \Platform\Regimen\Tools\CreatePlanAssignmentTool());
            $registry->register(new \Platform\Regimen\Tools\ListPlanAssignmentsTool());
            $registry->register(new \Platform\Regimen\Tools\GetPlanAssignmentTool());
            $registry->register(new \Platform\Regimen\Tools\UpdatePlanAssignmentTool());
            $registry->register(new \Platform\Regimen\Tools\DeletePlanAssignmentTool());
            $registry->register(new \Platform\Regimen\Tools\ResyncPlanAssignmentTool());
        } catch (\Throwable $e) {
            // ToolRegistry not available yet (e.g. during migrations)
        }
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Regimen\\Livewire';
        $prefix = 'regimen';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
