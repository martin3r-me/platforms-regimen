<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenTopic;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class GetRegimenTopicTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.topic.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/topic - Liefert ein Thema inkl. Sessions. Identifikation per topic_id ODER uuid.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'topic_id' => ['type' => 'integer', 'description' => 'ID des Themas.'],
                'uuid' => ['type' => 'string', 'description' => 'UUID des Themas.'],
                'include_drafts' => ['type' => 'boolean', 'description' => 'Auch Draft-Sessions listen (default false).'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = $resolved['team_id'];

            $query = RegimenTopic::where('team_id', $teamId);
            if (!empty($arguments['topic_id'])) {
                $query->where('id', (int) $arguments['topic_id']);
            } elseif (!empty($arguments['uuid'])) {
                $query->where('uuid', (string) $arguments['uuid']);
            } else {
                return ToolResult::error('VALIDATION_ERROR', 'topic_id oder uuid ist erforderlich.');
            }

            $topic = $query->first();
            if (!$topic) {
                return ToolResult::error('NOT_FOUND', 'Thema nicht gefunden.');
            }

            $sessionsQuery = $topic->sessions();
            if (!($arguments['include_drafts'] ?? false)) {
                $sessionsQuery->where('status', RegimenSession::STATUS_PUBLISHED);
            }

            return ToolResult::success([
                'id' => $topic->id,
                'uuid' => $topic->uuid,
                'slug' => $topic->slug,
                'title' => $topic->title,
                'description' => $topic->description,
                'icon' => $topic->icon,
                'color' => $topic->color,
                'sort_order' => $topic->sort_order,
                'sessions' => $sessionsQuery->get()->map(fn ($l) => [
                    'id' => $l->id,
                    'uuid' => $l->uuid,
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
            'category' => 'query', 'tags' => ['regimen', 'topics', 'get'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'read', 'idempotent' => true,
        ];
    }
}
