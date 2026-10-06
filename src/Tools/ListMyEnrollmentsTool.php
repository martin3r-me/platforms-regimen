<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Services\RegimenEnrollmentService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class ListMyEnrollmentsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.enrollments.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/enrollments - Listet die Pläne des aktuellen Users ("Meine Regimen") inkl. Fortschritt und Resume-Session.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];

            $rows = app(RegimenEnrollmentService::class)
                ->activeForUser($context->user->id, $resolved['team_id']);

            return ToolResult::success([
                'team_id' => $resolved['team_id'],
                'count' => $rows->count(),
                'enrollments' => $rows->map(fn ($row) => [
                    'plan_id' => $row['plan']->id,
                    'plan_uuid' => $row['plan']->uuid,
                    'code' => $row['plan']->code,
                    'title' => $row['plan']->title,
                    'category' => $row['plan']->category?->title,
                    'status' => $row['enrollment']->status,
                    'progress_pct' => $row['progress']['pct'],
                    'completed' => $row['progress']['completed'],
                    'total' => $row['progress']['total'],
                    'resume_session' => $row['resume'] ? [
                        'uuid' => $row['resume']->uuid,
                        'title' => $row['resume']->title,
                    ] : null,
                ])->all(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query', 'tags' => ['regimen', 'enrollments', 'list'],
            'read_only' => true, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'read', 'idempotent' => true,
        ];
    }
}
