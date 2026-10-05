<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Services\RegimenAssignmentService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Registry\AudienceResolverRegistry;

class CreatePlanAssignmentTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.assignments.POST';
    }

    public function getDescription(): string
    {
        return 'POST /regimen/assignments - Weist einen Kurs (Plan) einem Ziel zu und macht ihn optional zur Pflicht, mit Start/Fällig-Datum. Das Ziel wird sofort zu Personen aufgeloest (Auto-Enroll). ERFORDERLICH: plan_id, target_type (user|team|org_entity|org_role), target_id. Optional: target_options (z.B. {"include_subteams":true,"include_descendants":true}), is_mandatory (Default true), starts_at, due_at (YYYY-MM-DD), note. Hinweis: org_entity/org_role sind nur verfuegbar, wenn das Organisation-Modul installiert ist.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'plan_id' => ['type' => 'integer'],
                'target_type' => ['type' => 'string', 'enum' => ['user', 'team', 'org_entity', 'org_role']],
                'target_id' => ['type' => 'integer'],
                'target_options' => ['type' => 'object', 'description' => 'Ziel-Optionen, z.B. include_subteams / include_descendants.'],
                'is_mandatory' => ['type' => 'boolean', 'description' => 'Pflicht (Default true) vs. Empfehlung.'],
                'starts_at' => ['type' => 'string', 'description' => 'Ab wann relevant (YYYY-MM-DD).'],
                'due_at' => ['type' => 'string', 'description' => 'Deadline (YYYY-MM-DD).'],
                'note' => ['type' => 'string'],
            ],
            'required' => ['plan_id', 'target_type', 'target_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $plan = RegimenPlan::where('team_id', $resolved['team_id'])->find((int) ($arguments['plan_id'] ?? 0));
            if (!$plan) {
                return ToolResult::error('NOT_FOUND', 'Kurs (Plan) nicht gefunden.');
            }

            $targetType = (string) ($arguments['target_type'] ?? '');
            $registry = app(AudienceResolverRegistry::class);
            if (!$registry->supports($targetType)) {
                return ToolResult::error('UNSUPPORTED_TARGET', "Ziel-Typ '{$targetType}' ist nicht verfuegbar. Verfuegbar: " . implode(', ', $registry->supportedTypes()) . '.');
            }

            $targetId = (int) ($arguments['target_id'] ?? 0);
            if ($targetId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'target_id ist erforderlich.');
            }

            $rule = app(RegimenAssignmentService::class)->assign(
                $plan,
                $targetType,
                $targetId,
                is_array($arguments['target_options'] ?? null) ? $arguments['target_options'] : [],
                $context->user->id ?? null,
                [
                    'is_mandatory' => $arguments['is_mandatory'] ?? true,
                    'starts_at' => $arguments['starts_at'] ?? null,
                    'due_at' => $arguments['due_at'] ?? null,
                    'note' => $arguments['note'] ?? null,
                ],
            );

            $count = $rule->userAssignments()->count();

            return ToolResult::success([
                'id' => $rule->id,
                'uuid' => $rule->uuid,
                'plan_id' => $plan->id,
                'target' => $registry->label($targetType, $targetId, $resolved['team_id']) ?? ($targetType . ' #' . $targetId),
                'is_mandatory' => (bool) $rule->is_mandatory,
                'due_at' => $rule->due_at?->toDateString(),
                'assigned_persons' => $count,
                'message' => "Kurs '{$plan->title}' zugewiesen an {$count} Person(en).",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'assignments', 'create'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
