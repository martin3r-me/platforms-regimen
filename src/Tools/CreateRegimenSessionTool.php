<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenTopic;
use Platform\Regimen\Services\RegimenSessionService;
use Platform\Regimen\Tools\Concerns\DescribesAppletBlocks;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class CreateRegimenSessionTool implements ToolContract, ToolMetadataContract
{
    use DescribesAppletBlocks;
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.sessions.POST';
    }

    public function getDescription(): string
    {
        return 'POST /regimen/sessions - Erstellt eine neue Session in einem Thema. ERFORDERLICH: topic_id, title. Content ist Markdown.' . $this->appletDoc();
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'topic_id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'summary' => ['type' => 'string', 'description' => 'Kurzbeschreibung (1-2 Saetze).'],
                'content' => ['type' => 'string', 'description' => 'Markdown-Content. Unterstuetzt interaktive Applets via ```applet-Codeblock (siehe Tool-Beschreibung).'],
                'estimated_minutes' => ['type' => 'integer'],
                'status' => ['type' => 'string', 'enum' => ['draft', 'published', 'archived'], 'description' => 'Default draft.'],
                'slug' => ['type' => 'string'],
                'sort_order' => ['type' => 'integer'],
            ],
            'required' => ['topic_id', 'title'],
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

            $title = trim((string) ($arguments['title'] ?? ''));
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $session = app(RegimenSessionService::class)->create(
                $topic,
                $context->user->id,
                array_merge($arguments, ['title' => $title]),
            );

            return ToolResult::success([
                'id' => $session->id,
                'uuid' => $session->uuid,
                'topic_id' => $session->regimen_topic_id,
                'slug' => $session->slug,
                'title' => $session->title,
                'status' => $session->status,
                'message' => "Session '{$session->title}' erstellt (Status: {$session->status}).",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'sessions', 'create'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
