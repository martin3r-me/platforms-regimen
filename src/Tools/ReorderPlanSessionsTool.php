<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Services\RegimenPlanService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class ReorderPlanSessionsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.plans.sessions.reorder.POST';
    }

    public function getDescription(): string
    {
        return 'POST /regimen/plans/sessions/reorder - Setzt die Reihenfolge der Sessions in einem Plan neu. session_ids ist die Liste der Session-IDs in gewuenschter Reihenfolge.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'plan_id' => ['type' => 'integer'],
                'session_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Session-IDs in gewuenschter Reihenfolge.',
                ],
            ],
            'required' => ['plan_id', 'session_ids'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $plan = RegimenPlan::where('team_id', $resolved['team_id'])->find((int) ($arguments['plan_id'] ?? 0));
            if (!$plan) return ToolResult::error('NOT_FOUND', 'Plan nicht gefunden.');

            $sessionIds = $arguments['session_ids'] ?? [];
            if (!is_array($sessionIds) || empty($sessionIds)) {
                return ToolResult::error('VALIDATION_ERROR', 'session_ids muss ein nicht-leeres Array sein.');
            }

            $sessionIds = array_map('intval', $sessionIds);
            app(RegimenPlanService::class)->reorderSessions($plan, $sessionIds);

            return ToolResult::success([
                'plan_id' => $plan->id,
                'sessions_count' => count($sessionIds),
                'message' => "Reihenfolge in Pfad '{$plan->title}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'plans', 'reorder'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
