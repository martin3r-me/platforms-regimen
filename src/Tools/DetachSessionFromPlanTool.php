<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Services\RegimenPlanService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class DetachSessionFromPlanTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.plans.sessions.detach.POST';
    }

    public function getDescription(): string
    {
        return 'POST /regimen/plans/sessions/detach - Entfernt eine Session aus einem Plan (Session selbst bleibt erhalten).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'plan_id' => ['type' => 'integer'],
                'session_id' => ['type' => 'integer'],
            ],
            'required' => ['plan_id', 'session_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $plan = RegimenPlan::where('team_id', $resolved['team_id'])->find((int) ($arguments['plan_id'] ?? 0));
            if (!$plan) return ToolResult::error('NOT_FOUND', 'Plan nicht gefunden.');

            $session = RegimenSession::where('team_id', $resolved['team_id'])->find((int) ($arguments['session_id'] ?? 0));
            if (!$session) return ToolResult::error('NOT_FOUND', 'Session nicht gefunden.');

            app(RegimenPlanService::class)->detachSession($plan, $session);

            return ToolResult::success([
                'message' => "Session '{$session->title}' aus Pfad '{$plan->title}' entfernt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'plans', 'detach'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
