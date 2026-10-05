<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Services\RegimenPlanService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class DeleteRegimenPlanTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.plans.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /regimen/plans - Loescht einen Lernpfad (Sessions bleiben erhalten, nur die Zuordnung wird entfernt).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'plan_id' => ['type' => 'integer'],
            ],
            'required' => ['plan_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $planId = (int) ($arguments['plan_id'] ?? 0);
            $plan = RegimenPlan::where('team_id', $resolved['team_id'])->find($planId);
            if (!$plan) {
                return ToolResult::error('NOT_FOUND', 'Lernpfad nicht gefunden.');
            }

            $title = $plan->title;
            app(RegimenPlanService::class)->delete($plan);

            return ToolResult::success([
                'message' => "Lernpfad '{$title}' geloescht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'plans', 'delete'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'destructive', 'idempotent' => true,
        ];
    }
}
