<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class GetRegimenPlanTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.plan.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/plan - Liefert einen Plan inkl. zugeordneter Sessions in Reihenfolge.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'plan_id' => ['type' => 'integer'],
                'uuid' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $query = RegimenPlan::where('team_id', $resolved['team_id']);
            if (!empty($arguments['plan_id'])) {
                $query->where('id', (int) $arguments['plan_id']);
            } elseif (!empty($arguments['uuid'])) {
                $query->where('uuid', (string) $arguments['uuid']);
            } else {
                return ToolResult::error('VALIDATION_ERROR', 'plan_id oder uuid ist erforderlich.');
            }

            $plan = $query->first();
            if (!$plan) {
                return ToolResult::error('NOT_FOUND', 'Plan nicht gefunden.');
            }

            $sessions = $plan->sessions()->with('topic:id,uuid,title')->get();

            return ToolResult::success([
                'id' => $plan->id,
                'uuid' => $plan->uuid,
                'slug' => $plan->slug,
                'title' => $plan->title,
                'description' => $plan->description,
                'target_audience' => $plan->target_audience,
                'status' => $plan->status,
                'public' => $plan->public,
                'icon' => $plan->icon,
                'color' => $plan->color,
                'sort_order' => $plan->sort_order,
                'sessions' => $sessions->map(fn ($l) => [
                    'id' => $l->id,
                    'uuid' => $l->uuid,
                    'title' => $l->title,
                    'summary' => $l->summary,
                    'estimated_minutes' => $l->estimated_minutes,
                    'status' => $l->status,
                    'topic' => [
                        'id' => $l->topic?->id,
                        'title' => $l->topic?->title,
                    ],
                    'sort_order_in_plan' => $l->pivot->sort_order,
                ])->all(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query', 'tags' => ['regimen', 'plans', 'get'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'read', 'idempotent' => true,
        ];
    }
}
