<?php

namespace Platform\Regimen\Tools;

use Illuminate\Support\Carbon;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Services\RegimenEnrollmentService;
use Platform\Regimen\Services\RegimenScheduleService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Weist einen Plan einer Person mit Startdatum zu und materialisiert daraus den
 * persönlichen, datierten Plan (regimen_plan_entries).
 */
class AssignPlanTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.plans.assign.POST';
    }

    public function getDescription(): string
    {
        return 'POST /regimen/plans/assign - Weist einen Plan einer Person (Default: dem aktuellen User) ab einem '
            . 'Startdatum zu und erzeugt den persönlichen, datierten Trainingsplan. start_date wird auf den Montag '
            . 'der Startwoche gesnappt. ERFORDERLICH: plan_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'plan_id' => ['type' => 'integer'],
                'user_id' => ['type' => 'integer', 'description' => 'Zielperson. Default: aktueller User.'],
                'start_date' => ['type' => 'string', 'description' => 'Startdatum (YYYY-MM-DD). Default: heute. Wird auf Montag der Woche gesnappt.'],
                'regenerate' => ['type' => 'boolean', 'description' => 'Bestehenden, noch ungelaufenen persönlichen Plan neu aufbauen (absolvierte Einheiten bleiben).'],
            ],
            'required' => ['plan_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $plan = RegimenPlan::where('team_id', $resolved['team_id'])->find((int) ($arguments['plan_id'] ?? 0));
            if (!$plan) return ToolResult::error('NOT_FOUND', 'Plan nicht gefunden.');

            $userId = (int) ($arguments['user_id'] ?? $context->user->id);

            try {
                $startDate = isset($arguments['start_date']) && trim((string) $arguments['start_date']) !== ''
                    ? Carbon::parse($arguments['start_date'])
                    : Carbon::today();
            } catch (\Throwable $e) {
                return ToolResult::error('VALIDATION_ERROR', 'start_date ist kein gültiges Datum (YYYY-MM-DD).');
            }

            $enrollment = app(RegimenEnrollmentService::class)->enroll($userId, $plan);
            $enrollment->start_date = $startDate->toDateString();
            $enrollment->save();

            $force = (bool) ($arguments['regenerate'] ?? false);
            $created = app(RegimenScheduleService::class)->materialize($enrollment, $force);

            $entries = $enrollment->entries()->get(['scheduled_date']);

            return ToolResult::success([
                'enrollment_id' => $enrollment->id,
                'plan_id' => $plan->id,
                'plan_title' => $plan->title,
                'user_id' => $userId,
                'start_date' => $enrollment->start_date->toDateString(),
                'entries_created' => $created,
                'entries_total' => $entries->count(),
                'first_date' => optional($entries->min('scheduled_date'))->toDateString(),
                'last_date' => optional($entries->max('scheduled_date'))->toDateString(),
                'message' => "Plan '{$plan->title}' zugewiesen — {$created} Einheiten ab {$enrollment->start_date->toDateString()} eingeplant.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'plans', 'assign', 'schedule'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
