<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Services\RegimenCategoryService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class ListRegimenCategoriesTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.categories.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/categories - Listet alle Kurs-Kategorien ("Schools") des Teams inkl. Kurs-Anzahl.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $categories = app(RegimenCategoryService::class)->listForTeam($resolved['team_id']);

            return ToolResult::success([
                'team_id' => $resolved['team_id'],
                'count' => $categories->count(),
                'categories' => $categories->map(fn ($c) => [
                    'id' => $c->id,
                    'uuid' => $c->uuid,
                    'slug' => $c->slug,
                    'title' => $c->title,
                    'color' => $c->color,
                    'code_prefix' => $c->code_prefix,
                    'icon' => $c->icon,
                    'sort_order' => $c->sort_order,
                    'plans_count' => $c->plans_count,
                ])->all(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query', 'tags' => ['regimen', 'categories', 'list'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'read', 'idempotent' => true,
        ];
    }
}
