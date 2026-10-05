<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenSessionProgress;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenTopic;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class RegimenOverviewTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /regimen/overview - Zeigt eine Uebersicht der Regimen: Anzahl Themen, Sessions, Lernpfade + eigene Lernfortschritte.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = $resolved['team_id'];
            $userId = $context->user->id;

            $topicsCount = RegimenTopic::where('team_id', $teamId)->count();
            $sessionsTotal = RegimenSession::where('team_id', $teamId)->count();
            $sessionsPublished = RegimenSession::where('team_id', $teamId)
                ->where('status', RegimenSession::STATUS_PUBLISHED)->count();
            $plansTotal = RegimenPlan::where('team_id', $teamId)->count();
            $plansPublished = RegimenPlan::where('team_id', $teamId)
                ->where('status', RegimenPlan::STATUS_PUBLISHED)->count();

            $mySessionIds = RegimenSession::where('team_id', $teamId)->pluck('id');
            $myCompleted = RegimenSessionProgress::where('user_id', $userId)
                ->whereIn('regimen_session_id', $mySessionIds)
                ->where('status', RegimenSessionProgress::STATUS_COMPLETED)
                ->count();
            $myInProgress = RegimenSessionProgress::where('user_id', $userId)
                ->whereIn('regimen_session_id', $mySessionIds)
                ->where('status', RegimenSessionProgress::STATUS_IN_PROGRESS)
                ->count();

            return ToolResult::success([
                'team_id' => $teamId,
                'topics_count' => $topicsCount,
                'sessions_total' => $sessionsTotal,
                'sessions_published' => $sessionsPublished,
                'plans_total' => $plansTotal,
                'plans_published' => $plansPublished,
                'my_progress' => [
                    'completed' => $myCompleted,
                    'in_progress' => $myInProgress,
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['regimen', 'overview'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'read',
            'idempotent' => true,
        ];
    }
}
