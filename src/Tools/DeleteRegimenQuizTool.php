<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class DeleteRegimenQuizTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.quizzes.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /regimen/quizzes - Entfernt den Concept-Check einer Einheit (inkl. Fragen, Optionen, Versuche). Danach ist die Einheit wieder ohne Quiz abschliessbar. Identifikation per session_id.';
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
            $session = RegimenSession::where('team_id', $resolved['team_id'])->with('quiz')->find($sessionId);
            if (!$session) {
                return ToolResult::error('NOT_FOUND', 'Session nicht gefunden.');
            }
            if (!$session->quiz) {
                return ToolResult::error('NOT_FOUND', 'Diese Einheit hat keinen Concept-Check.');
            }

            $session->quiz->delete(); // cascade: Fragen, Optionen, Versuche

            return ToolResult::success([
                'session_id' => $session->id,
                'message' => "Concept-Check von '{$session->title}' entfernt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'quizzes', 'delete'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => true,
        ];
    }
}
