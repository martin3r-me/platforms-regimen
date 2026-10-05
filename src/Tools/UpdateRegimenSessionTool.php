<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Services\RegimenSessionService;
use Platform\Regimen\Tools\Concerns\DescribesAppletBlocks;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class UpdateRegimenSessionTool implements ToolContract, ToolMetadataContract
{
    use DescribesAppletBlocks;
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.sessions.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /regimen/sessions - Aktualisiert eine Session. ERFORDERLICH: session_id. Optional: title, summary, content, status, estimated_minutes, sort_order. Content-Operationen via op: append, prepend, replace_exact, upsert_heading.' . $this->appletDoc();
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'session_id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'content' => ['type' => 'string', 'description' => 'Ersetzt komplett. Wird ignoriert wenn op gesetzt. Unterstuetzt interaktive Applets via ```applet-Codeblock (siehe Tool-Beschreibung).'],
                'status' => ['type' => 'string', 'enum' => ['draft', 'published', 'archived']],
                'estimated_minutes' => ['type' => 'integer'],
                'sort_order' => ['type' => 'integer'],
                'op' => [
                    'type' => 'string',
                    'enum' => ['append', 'prepend', 'replace_exact', 'upsert_heading'],
                    'description' => 'Content-Operation statt vollstaendigem Ersetzen.',
                ],
                'text' => ['type' => 'string', 'description' => 'Text fuer op=append/prepend/upsert_heading.'],
                'old' => ['type' => 'string', 'description' => 'Alter Text fuer op=replace_exact.'],
                'new' => ['type' => 'string', 'description' => 'Neuer Text fuer op=replace_exact.'],
                'heading' => ['type' => 'string', 'description' => 'Heading-Text fuer op=upsert_heading.'],
                'level' => ['type' => 'integer', 'description' => 'Heading-Level 1-6 fuer op=upsert_heading. Default 2.'],
                'mode' => ['type' => 'string', 'enum' => ['append', 'replace'], 'description' => 'Modus fuer op=upsert_heading.'],
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

            $service = app(RegimenSessionService::class);
            $payload = [];

            $op = $arguments['op'] ?? null;
            if ($op !== null && $op !== '') {
                $result = $service->applyContentOp($session, (string) $op, $arguments);
                if (!$result['success']) {
                    return ToolResult::error('VALIDATION_ERROR', $result['error']);
                }
                if ((string) $result['content'] === (string) ($session->content ?? '')) {
                    return ToolResult::error('NO_CHANGE', 'Keine Aenderung am Content.');
                }
                $payload['content'] = (string) $result['content'];
            }

            foreach (['title', 'summary', 'estimated_minutes', 'status', 'sort_order'] as $field) {
                if (array_key_exists($field, $arguments) && $arguments[$field] !== null) {
                    $payload[$field] = $arguments[$field];
                }
            }
            if ($op === null && array_key_exists('content', $arguments) && $arguments['content'] !== null) {
                $payload['content'] = (string) $arguments['content'];
            }

            if (empty($payload)) {
                return ToolResult::error('NO_CHANGE', 'Keine Aenderungen uebergeben.');
            }

            $session = $service->update($session, $payload);

            return ToolResult::success([
                'id' => $session->id,
                'uuid' => $session->uuid,
                'title' => $session->title,
                'status' => $session->status,
                'message' => "Session '{$session->title}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'sessions', 'update'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
