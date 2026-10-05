<?php

namespace Platform\Regimen\Tools;

use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenTopic;
use Platform\Regimen\Services\RegimenPlanService;
use Platform\Regimen\Services\RegimenSessionService;
use Platform\Regimen\Tools\Concerns\ResolvesRegimenTeam;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Legt eine Trainings-Einheit auf einen konkreten Tag eines Plans (Wochen×7-Raster).
 * Erstellt die Einheit bei Bedarf neu (Lauf-Felder) oder platziert eine bestehende.
 */
class AddSessionToPlanDayTool implements ToolContract, ToolMetadataContract
{
    use ResolvesRegimenTeam;

    public function getName(): string
    {
        return 'regimen.plans.days.POST';
    }

    public function getDescription(): string
    {
        return 'POST /regimen/plans/days - Legt eine Einheit auf einen Tag im Plan (week 1..N, weekday 1=Mo..7=So). '
            . 'Entweder bestehende session_id platzieren ODER neue Einheit via title/kind/Ziele anlegen. '
            . 'Distanz/Dauer als _m/_s ODER bequem als _km/_min. ERFORDERLICH: plan_id, week, weekday.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer'],
                'plan_id' => ['type' => 'integer'],
                'week' => ['type' => 'integer', 'description' => 'Woche im Plan (1-basiert).'],
                'weekday' => ['type' => 'integer', 'description' => 'Wochentag: 1=Mo … 7=So.'],
                'sort_order' => ['type' => 'integer', 'description' => 'Reihenfolge bei mehreren Einheiten am selben Tag.'],

                'session_id' => ['type' => 'integer', 'description' => 'Bestehende Einheit platzieren (statt neu anzulegen).'],

                'title' => ['type' => 'string', 'description' => 'Titel der neuen Einheit, z.B. "Longrun 12 km".'],
                'kind' => ['type' => 'string', 'enum' => ['easy', 'long', 'tempo', 'interval', 'recovery', 'rest', 'race'], 'description' => 'Art der Lauf-Einheit.'],
                'target_distance_m' => ['type' => 'integer', 'description' => 'Zieldistanz in Metern.'],
                'target_distance_km' => ['type' => 'number', 'description' => 'Bequem: Zieldistanz in km (wird in Meter umgerechnet).'],
                'target_duration_s' => ['type' => 'integer', 'description' => 'Zieldauer in Sekunden.'],
                'target_duration_min' => ['type' => 'number', 'description' => 'Bequem: Zieldauer in Minuten.'],
                'target_pace_s_per_km' => ['type' => 'integer', 'description' => 'Zielpace in Sekunden/km (z.B. 330 = 5:30/km).'],
                'intensity' => ['type' => 'string', 'description' => 'Optionales Intensitäts-/Zonen-Label.'],
                'summary' => ['type' => 'string'],
                'structure' => ['type' => 'object', 'description' => 'Optionaler strukturierter Workout (Warmup/Repeats/Cooldown) für den Garmin-Push.'],
            ],
            'required' => ['plan_id', 'week', 'weekday'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) return $resolved['error'];
            $teamId = $resolved['team_id'];

            $plan = RegimenPlan::where('team_id', $teamId)->find((int) ($arguments['plan_id'] ?? 0));
            if (!$plan) return ToolResult::error('NOT_FOUND', 'Plan nicht gefunden.');

            $week = (int) ($arguments['week'] ?? 0);
            $weekday = (int) ($arguments['weekday'] ?? 0);
            if ($week < 1) return ToolResult::error('VALIDATION_ERROR', 'week muss >= 1 sein.');
            if ($weekday < 1 || $weekday > 7) return ToolResult::error('VALIDATION_ERROR', 'weekday muss 1..7 sein (1=Mo).');

            // Bestehende Einheit platzieren …
            if (!empty($arguments['session_id'])) {
                $session = RegimenSession::where('team_id', $teamId)->find((int) $arguments['session_id']);
                if (!$session) return ToolResult::error('NOT_FOUND', 'Einheit nicht gefunden.');
            } else {
                // … oder neue Einheit anlegen.
                $title = trim((string) ($arguments['title'] ?? ''));
                $kind = $arguments['kind'] ?? null;
                if ($title === '' && $kind) {
                    $title = RegimenSession::KINDS[$kind] ?? 'Einheit';
                }
                if ($title === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'Für eine neue Einheit ist title oder kind erforderlich.');
                }

                $topic = $this->planTopic($plan, $context->user->id);

                $session = app(RegimenSessionService::class)->create($topic, $context->user->id, [
                    'title' => $title,
                    'kind' => $kind,
                    'summary' => $arguments['summary'] ?? null,
                    'target_distance_m' => $this->meters($arguments),
                    'target_duration_s' => $this->seconds($arguments),
                    'target_pace_s_per_km' => isset($arguments['target_pace_s_per_km']) ? (int) $arguments['target_pace_s_per_km'] : null,
                    'intensity' => $arguments['intensity'] ?? null,
                    'structure' => $arguments['structure'] ?? null,
                    'status' => RegimenSession::STATUS_PUBLISHED,
                ]);
            }

            $sortOrder = isset($arguments['sort_order']) ? (int) $arguments['sort_order'] : null;
            app(RegimenPlanService::class)->placeSessionOnDay($plan, $session, $week, $weekday, $sortOrder);

            return ToolResult::success([
                'plan_id' => $plan->id,
                'session_id' => $session->id,
                'week' => $week,
                'weekday' => $weekday,
                'title' => $session->title,
                'kind' => $session->kind,
                'target_distance_m' => $session->target_distance_m,
                'target_duration_s' => $session->target_duration_s,
                'target_pace_s_per_km' => $session->target_pace_s_per_km,
                'message' => "'{$session->title}' auf Woche {$week}, Tag {$weekday} gelegt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    /** Pro Plan eine Einheiten-Bibliothek (Topic), idempotent. */
    protected function planTopic(RegimenPlan $plan, int $userId): RegimenTopic
    {
        return RegimenTopic::firstOrCreate(
            ['team_id' => $plan->team_id, 'slug' => 'plan-' . $plan->id . '-einheiten'],
            ['title' => $plan->title . ' – Einheiten', 'created_by_user_id' => $userId],
        );
    }

    protected function meters(array $a): ?int
    {
        if (isset($a['target_distance_m'])) return (int) $a['target_distance_m'];
        if (isset($a['target_distance_km'])) return (int) round(((float) $a['target_distance_km']) * 1000);
        return null;
    }

    protected function seconds(array $a): ?int
    {
        if (isset($a['target_duration_s'])) return (int) $a['target_duration_s'];
        if (isset($a['target_duration_min'])) return (int) round(((float) $a['target_duration_min']) * 60);
        return null;
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'action', 'tags' => ['regimen', 'plans', 'sessions', 'schedule'],
            'read_only' => false, 'requires_auth' => true, 'requires_team' => true,
            'risk_level' => 'write', 'idempotent' => false,
        ];
    }
}
