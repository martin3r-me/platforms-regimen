<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenTopic;
use Platform\Regimen\Services\RegimenTopicService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class UpdateRegimenTopicTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.topics.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /regimen/topics - Aktualisiert ein Thema. ERFORDERLICH: topic_id. Optional: title, description, icon, color, sort_order.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'topic_id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'icon' => ['type' => 'string'],
                'color' => ['type' => 'string'],
                'sort_order' => ['type' => 'integer'],
            ],
            'required' => ['topic_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $topicId = (int) ($arguments['topic_id'] ?? 0);
            $topic = RegimenTopic::where('team_id', $resolved['team_id'])->find($topicId);
            if (!$topic) {
                return ToolResult::error('NOT_FOUND', 'Thema nicht gefunden.');
            }

            $topic = app(RegimenTopicService::class)->update($topic, $arguments);

            return ToolResult::success([
                'id' => $topic->id,
                'uuid' => $topic->uuid,
                'title' => $topic->title,
                'message' => "Thema aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'topics', 'update'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
