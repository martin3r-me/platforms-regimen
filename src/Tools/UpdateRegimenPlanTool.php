<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Services\RegimenPlanService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class UpdateRegimenPlanTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.plans.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /regimen/plans - Aktualisiert einen Plan. ERFORDERLICH: plan_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'plan_id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'regimen_category_id' => ['type' => 'integer', 'description' => 'Kategorie/"Disziplin" des Plans.'],
                'code' => ['type' => 'string', 'description' => 'Plan-Code, z.B. "AI-101". Leerstring entfernt den Code.'],
                'level' => ['type' => 'string', 'enum' => ['beginner', 'intermediate', 'advanced']],
                'type' => ['type' => 'string', 'enum' => ['running', 'equipment'], 'description' => 'Plan-Typ: running = Laufplan, equipment = Fitnessgeräte-Plan.'],
                'duration_weeks' => ['type' => 'integer', 'description' => 'Länge des Plans in Wochen.'],
                'description' => ['type' => 'string'],
                'target_audience' => ['type' => 'string'],
                'status' => ['type' => 'string', 'enum' => ['draft', 'published', 'archived']],
                'public' => ['type' => 'boolean', 'description' => 'Website-Freigabe. Nur Pläne mit status=published UND public=true werden über die Public Plan API ausgeliefert.'],
                'icon' => ['type' => 'string'],
                'color' => ['type' => 'string', 'description' => 'Cover-Farb-Override (Hex).'],
                'sort_order' => ['type' => 'integer'],
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
                return ToolResult::error('NOT_FOUND', 'Plan nicht gefunden.');
            }

            $plan = app(RegimenPlanService::class)->update($plan, $arguments);

            return ToolResult::success([
                'id' => $plan->id,
                'uuid' => $plan->uuid,
                'title' => $plan->title,
                'type' => $plan->type,
                'status' => $plan->status,
                'public' => $plan->public,
                'message' => "Plan aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'plans', 'update'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
