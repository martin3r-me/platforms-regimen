<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class ListRegimenSessionsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.sessions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/sessions - Listet Sessions. Optional gefiltert nach topic_id, status.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'topic_id' => ['type' => 'integer', 'description' => 'Nur Sessions dieses Themas.'],
                'status' => ['type' => 'string', 'enum' => ['draft', 'published', 'archived']],
                'limit' => ['type' => 'integer', 'description' => 'Default 50, max 200.'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $query = RegimenSession::where('team_id', $resolved['team_id'])
                ->with('topic:id,uuid,title')
                ->orderBy('regimen_topic_id')
                ->orderBy('sort_order');

            if (!empty($arguments['topic_id'])) {
                $query->where('regimen_topic_id', (int) $arguments['topic_id']);
            }
            if (!empty($arguments['status'])) {
                $query->where('status', (string) $arguments['status']);
            }

            $limit = min((int) ($arguments['limit'] ?? 50), 200);
            $sessions = $query->limit($limit)->get();

            return ToolResult::success([
                'team_id' => $resolved['team_id'],
                'count' => $sessions->count(),
                'sessions' => $sessions->map(fn ($l) => [
                    'id' => $l->id,
                    'uuid' => $l->uuid,
                    'topic_id' => $l->regimen_topic_id,
                    'topic_title' => $l->topic?->title,
                    'slug' => $l->slug,
                    'title' => $l->title,
                    'summary' => $l->summary,
                    'estimated_minutes' => $l->estimated_minutes,
                    'status' => $l->status,
                    'sort_order' => $l->sort_order,
                ])->all(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query', 'tags' => ['regimen', 'sessions', 'list'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'read', 'idempotent' => true,
        ];
    }
}
