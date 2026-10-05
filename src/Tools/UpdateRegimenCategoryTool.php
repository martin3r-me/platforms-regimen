<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenCategory;
use Platform\Regimen\Services\RegimenCategoryService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class UpdateRegimenCategoryTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.categories.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /regimen/categories - Aktualisiert eine Kategorie. ERFORDERLICH: category_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'category_id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'color' => ['type' => 'string'],
                'code_prefix' => ['type' => 'string'],
                'icon' => ['type' => 'string'],
                'sort_order' => ['type' => 'integer'],
            ],
            'required' => ['category_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $categoryId = (int) ($arguments['category_id'] ?? 0);
            $category = RegimenCategory::where('team_id', $resolved['team_id'])->find($categoryId);
            if (!$category) {
                return ToolResult::error('NOT_FOUND', 'Kategorie nicht gefunden.');
            }

            $category = app(RegimenCategoryService::class)->update($category, $arguments);

            return ToolResult::success([
                'id' => $category->id,
                'uuid' => $category->uuid,
                'title' => $category->title,
                'message' => 'Kategorie aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'categories', 'update'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
