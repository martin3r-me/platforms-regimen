<?php

namespace Platform\Regimen\Tools;

use Illuminate\Support\Carbon;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenPlanEnrollment;
use Platform\Regimen\Services\RegimenAssignmentService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Weist einen Plan einem Ziel zu (Person, Team oder Org-Entity/Rolle) und
 * erzeugt daraus — über die generische Org-Zuweisung — die pro-Person-Zuweisungen,
 * das Enrollment und den persönlichen, datierten Plan (regimen_plan_entries).
 *
 * Dies ist der korrekte Pfad: Personen werden als Org-Ziel adressiert und vom
 * Core-AudienceResolver aufgelöst. Das Startdatum der Zuweisung treibt die
 * Materialisierung des datierten Plans (Garmin-Fläche).
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
        return 'POST /regimen/plans/assign - Weist einen Plan einem Ziel zu und erzeugt den persönlichen, '
            . 'datierten Trainingsplan. target_type: user (Default = aktueller User) | team | org_entity | org_role. '
            . 'starts_at treibt die Materialisierung (wird auf Montag der Woche gesnappt). ERFORDERLICH: plan_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'plan_id' => ['type' => 'integer'],
                'target_type' => ['type' => 'string', 'enum' => ['user', 'team', 'org_entity', 'org_role'], 'description' => 'Zielart. Default: user.'],
                'target_id' => ['type' => 'integer', 'description' => 'ID des Ziels (User-, Team-, Entity- oder Rollen-ID). Default bei target_type=user: aktueller User.'],
                'start_date' => ['type' => 'string', 'description' => 'Startdatum (YYYY-MM-DD). Default: heute. Wird auf Montag der Woche gesnappt.'],
                'due_date' => ['type' => 'string', 'description' => 'Optionales Fälligkeitsdatum (YYYY-MM-DD).'],
                'is_mandatory' => ['type' => 'boolean', 'description' => 'Pflicht-Zuweisung. Default: false (Selbst-Zuweisung).'],
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

            $targetType = $arguments['target_type'] ?? 'user';
            $targetId = isset($arguments['target_id'])
                ? (int) $arguments['target_id']
                : ($targetType === 'user' ? (int) $context->user->id : 0);
            if ($targetId < 1) {
                return ToolResult::error('VALIDATION_ERROR', 'target_id fehlt (nur bei target_type=user optional).');
            }

            try {
                $startDate = isset($arguments['start_date']) && trim((string) $arguments['start_date']) !== ''
                    ? Carbon::parse($arguments['start_date'])->toDateString()
                    : Carbon::today()->toDateString();
                $dueDate = isset($arguments['due_date']) && trim((string) $arguments['due_date']) !== ''
                    ? Carbon::parse($arguments['due_date'])->toDateString()
                    : null;
            } catch (\Throwable $e) {
                return ToolResult::error('VALIDATION_ERROR', 'start_date/due_date ist kein gültiges Datum (YYYY-MM-DD).');
            }

            $rule = app(RegimenAssignmentService::class)->assign(
                $plan,
                $targetType,
                $targetId,
                [],
                $context->user->id,
                [
                    'is_mandatory' => (bool) ($arguments['is_mandatory'] ?? false),
                    'starts_at' => $startDate,
                    'due_at' => $dueDate,
                ],
            );

            // Ergebnis zusammenfassen: bei Einzel-User auch den datierten Plan zeigen.
            $summary = [
                'assignment_id' => $rule->id,
                'plan_id' => $plan->id,
                'plan_title' => $plan->title,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'people_assigned' => $rule->userAssignments()->count(),
            ];

            if ($targetType === 'user') {
                $enrollment = RegimenPlanEnrollment::where('user_id', $targetId)
                    ->where('regimen_plan_id', $plan->id)
                    ->first();
                if ($enrollment) {
                    $entries = $enrollment->entries()->get(['scheduled_date']);
                    $summary['entries_total'] = $entries->count();
                    $summary['first_date'] = optional($entries->min('scheduled_date'))->toDateString();
                    $summary['last_date'] = optional($entries->max('scheduled_date'))->toDateString();
                }
            }

            $summary['message'] = "Plan '{$plan->title}' zugewiesen ({$targetType}:{$targetId}) ab {$startDate}"
                . (isset($summary['entries_total']) ? " — {$summary['entries_total']} Einheiten eingeplant." : '.');

            return ToolResult::success($summary);
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
