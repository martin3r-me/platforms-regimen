<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Services\RegimenEnrollmentService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class DropEnrollmentTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.enrollments.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /regimen/enrollments - Beendet die Einschreibung des Users in einen Plan. Der Session-Fortschritt bleibt erhalten. ERFORDERLICH: plan_id.';
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

            $plan = RegimenPlan::where('team_id', $resolved['team_id'])->find((int) ($arguments['plan_id'] ?? 0));
            if (!$plan) {
                return ToolResult::error('NOT_FOUND', 'Plan nicht gefunden.');
            }

            app(RegimenEnrollmentService::class)->drop($context->user->id, $plan);

            return ToolResult::success([
                'plan_id' => $plan->id,
                'message' => "Einschreibung in '{$plan->title}' beendet.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'enrollments', 'drop'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
