<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenCategory;
use Platform\Regimen\Services\RegimenCategoryService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class DeleteRegimenCategoryTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.categories.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /regimen/categories - Loescht eine Kategorie. Zugeordnete Kurse bleiben erhalten (Kategorie wird entfernt). ERFORDERLICH: category_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'category_id' => ['type' => 'integer'],
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

            $title = $category->title;
            app(RegimenCategoryService::class)->delete($category);

            return ToolResult::success([
                'message' => "Kategorie '{$title}' geloescht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'categories', 'delete'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
