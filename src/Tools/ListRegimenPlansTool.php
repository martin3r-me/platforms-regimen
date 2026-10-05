<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Services\RegimenPlanService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class ListRegimenPlansTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.plans.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/plans - Listet alle Lernpfade des Teams.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'published_only' => ['type' => 'boolean'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $plans = app(RegimenPlanService::class)->listForTeam(
                $resolved['team_id'],
                publishedOnly: (bool) ($arguments['published_only'] ?? false),
            );

            return ToolResult::success([
                'team_id' => $resolved['team_id'],
                'count' => $plans->count(),
                'plans' => $plans->map(fn ($p) => [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'slug' => $p->slug,
                    'title' => $p->title,
                    'code' => $p->code,
                    'level' => $p->level,
                    'regimen_category_id' => $p->regimen_category_id,
                    'category' => $p->category?->title,
                    'description' => $p->description,
                    'target_audience' => $p->target_audience,
                    'status' => $p->status,
                    'public' => $p->public,
                    'icon' => $p->icon,
                    'color' => $p->color,
                    'sort_order' => $p->sort_order,
                    'sessions_count' => $p->sessions_count,
                ])->all(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query', 'tags' => ['regimen', 'plans', 'list'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'read', 'idempotent' => true,
        ];
    }
}
