<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Services\RegimenSessionService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class DeleteRegimenSessionTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.sessions.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /regimen/sessions - Loescht eine Session. ERFORDERLICH: session_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'session_id' => ['type' => 'integer'],
            ],
            'required' => ['session_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $sessionId = (int) ($arguments['session_id'] ?? 0);
            $session = RegimenSession::where('team_id', $resolved['team_id'])->find($sessionId);
            if (!$session) {
                return ToolResult::error('NOT_FOUND', 'Session nicht gefunden.');
            }

            $title = $session->title;
            app(RegimenSessionService::class)->delete($session);

            return ToolResult::success([
                'message' => "Session '{$title}' geloescht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'sessions', 'delete'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'destructive', 'idempotent' => true,
        ];
    }
}
