<?php

namespace Platform\Regimen\Console\Commands;

use Illuminate\Console\Command;
use Platform\Regimen\Services\RegimenAssignmentService;

/**
 * Tägliche Pflege der Kurs-Zuweisungen: neue Mitglieder nachziehen,
 * Überfälligkeit markieren, sanfte Erinnerungen versenden.
 */
class RunAssignmentMaintenanceCommand extends Command
{
    protected $signature = 'regimen:assignments-tick {--no-resync : Mitglieder-Sync überspringen}';

    protected $description = 'Kurs-Zuweisungen pflegen: Mitglieder nachziehen, Überfälligkeit + Erinnerungen.';

    public function handle(RegimenAssignmentService $service): int
    {
        if (!$this->option('no-resync')) {
            $added = $service->resyncActiveRules();
            $this->info("Neue Zuweisungen durch Mitglieder-Sync: {$added}");
        }

        $overdue = $service->refreshOverdue();
        $this->info("Neu als überfällig markiert: {$overdue}");

        $service->sendReminders();
        $this->info('Erinnerungen versendet.');

        return self::SUCCESS;
    }
}
