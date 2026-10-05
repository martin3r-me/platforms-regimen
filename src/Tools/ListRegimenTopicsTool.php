<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Services\RegimenTopicService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class ListRegimenTopicsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.topics.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/topics - Listet alle Themen-Cluster des Teams.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $topics = app(RegimenTopicService::class)->listForTeam($resolved['team_id']);

            return ToolResult::success([
                'team_id' => $resolved['team_id'],
                'count' => $topics->count(),
                'topics' => $topics->map(fn ($t) => [
                    'id' => $t->id,
                    'uuid' => $t->uuid,
                    'slug' => $t->slug,
                    'title' => $t->title,
                    'description' => $t->description,
                    'icon' => $t->icon,
                    'color' => $t->color,
                    'sort_order' => $t->sort_order,
                    'published_sessions_count' => $t->published_sessions_count,
                ])->all(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query', 'tags' => ['regimen', 'topics', 'list'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'read', 'idempotent' => true,
        ];
    }
}
