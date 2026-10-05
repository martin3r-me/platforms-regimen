<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class GetRegimenSessionTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.session.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/session - Liefert eine Session mit vollem Markdown-Content. Identifikation per session_id ODER uuid.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'session_id' => ['type' => 'integer'],
                'uuid' => ['type' => 'string'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $query = RegimenSession::where('team_id', $resolved['team_id'])->with('topic');
            if (!empty($arguments['session_id'])) {
                $query->where('id', (int) $arguments['session_id']);
            } elseif (!empty($arguments['uuid'])) {
                $query->where('uuid', (string) $arguments['uuid']);
            } else {
                return ToolResult::error('VALIDATION_ERROR', 'session_id oder uuid ist erforderlich.');
            }

            $session = $query->first();
            if (!$session) {
                return ToolResult::error('NOT_FOUND', 'Session nicht gefunden.');
            }

            return ToolResult::success([
                'id' => $session->id,
                'uuid' => $session->uuid,
                'topic' => [
                    'id' => $session->topic->id,
                    'uuid' => $session->topic->uuid,
                    'title' => $session->topic->title,
                ],
                'slug' => $session->slug,
                'title' => $session->title,
                'summary' => $session->summary,
                'content' => $session->content,
                'estimated_minutes' => $session->estimated_minutes,
                'status' => $session->status,
                'sort_order' => $session->sort_order,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query', 'tags' => ['regimen', 'sessions', 'get'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'read', 'idempotent' => true,
        ];
    }
}
