<?php

namespace Platform\Regimen\Services;

use Illuminate\Support\Collection;
use Platform\Regimen\Models\RegimenPlanAssignment;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenUserAssignment;
use Platform\Regimen\Services\RegimenScheduleService;
use Platform\Core\Registry\AudienceResolverRegistry;
use Platform\Notifications\Models\NotificationsNotice;
use Illuminate\Support\Facades\Route;

/**
 * Kern der Kurs-Delegation / Pflichtkurse. Entkoppelt: die Ziel-Auflösung
 * ("wer steckt hinter target_type/target_id?") läuft über die
 * AudienceResolverRegistry im Core — kein Wissen über Organisation o.Ä.
 */
class RegimenAssignmentService
{
    public function __construct(
        private RegimenEnrollmentService $enrollments,
    ) {}

    /**
     * Legt eine Delegations-Regel an und fächert sie sofort auf Personen aus.
     *
     * @param  array<string,mixed>  $options  Ziel-Optionen (z.B. include_subteams)
     * @param  array<string,mixed>  $attrs    is_mandatory, starts_at, due_at, note
     */
    public function assign(
        RegimenPlan $plan,
        string $targetType,
        int $targetId,
        array $options = [],
        ?int $assignedByUserId = null,
        array $attrs = []
    ): RegimenPlanAssignment {
        $rule = RegimenPlanAssignment::create([
            'team_id' => $plan->team_id,
            'regimen_plan_id' => $plan->id,
            'assigned_by_user_id' => $assignedByUserId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_options' => $options ?: null,
            'is_mandatory' => $attrs['is_mandatory'] ?? true,
            'starts_at' => $attrs['starts_at'] ?? null,
            'due_at' => $attrs['due_at'] ?? null,
            'note' => $attrs['note'] ?? null,
            'status' => RegimenPlanAssignment::STATUS_ACTIVE,
        ]);

        $this->fanOut($rule);

        return $rule;
    }

    /**
     * Löst das Ziel in User auf und legt fehlende pro-Person-Zuweisungen an
     * (idempotent, Auto-Enroll inklusive). Gibt die Zahl neu erzeugter zurück.
     */
    public function fanOut(RegimenPlanAssignment $rule): int
    {
        $plan = $rule->plan;
        if (!$plan || !$rule->isActive()) {
            return 0;
        }

        $userIds = app(AudienceResolverRegistry::class)->resolve(
            $rule->target_type,
            (int) $rule->target_id,
            $rule->target_options ?? [],
            $rule->team_id,
        );

        $created = 0;
        foreach ($userIds as $userId) {
            $exists = RegimenUserAssignment::where('regimen_plan_assignment_id', $rule->id)
                ->where('user_id', $userId)
                ->exists();
            if ($exists) {
                continue;
            }

            // Auto-Enroll (idempotent) — Fortschritt/Zertifikat laufen dadurch mit.
            $enrollment = $this->enrollments->enroll($userId, $plan);
            $isDone = $enrollment->isCompleted();

            $ua = RegimenUserAssignment::create([
                'team_id' => $rule->team_id,
                'regimen_plan_assignment_id' => $rule->id,
                'user_id' => $userId,
                'regimen_plan_id' => $plan->id,
                'regimen_plan_enrollment_id' => $enrollment->id,
                'is_mandatory' => $rule->is_mandatory,
                'starts_at' => $rule->starts_at,
                'due_at' => $rule->due_at,
                'status' => $isDone
                    ? RegimenUserAssignment::STATUS_COMPLETED
                    : RegimenUserAssignment::STATUS_ASSIGNED,
                'completed_at' => $isDone ? now() : null,
            ]);

            // Datierten persönlichen Plan materialisieren (Garmin-Fläche), sobald ein
            // Startdatum bekannt ist. Die Zuweisung ist die Quelle des Startdatums,
            // nicht ein Ad-hoc-Enrollment.
            if ($rule->starts_at) {
                $enrollment->start_date = $rule->starts_at;
                $enrollment->save();
                app(RegimenScheduleService::class)->materialize($enrollment);
            }

            // Generische Org-Verknüpfung: Plan an die Person-Entity hängen
            // (entity-Dimension), damit er in der Org-Draufsicht erscheint.
            $this->linkPlanToPersonEntity($plan, $userId);

            if (!$isDone) {
                $this->notify($ua, 'assigned');
            }
            $created++;
        }

        $rule->forceFill(['last_synced_at' => now()])->save();

        return $created;
    }

    /**
     * Wird bei Kurs-Abschluss/-Reaktivierung aufgerufen (Hook im EnrollmentService).
     * Hält die pro-Person-Zuweisungen synchron zum Fortschritt.
     */
    public function syncPlanCompletion(int $userId, RegimenPlan $plan, bool $isCompleted): void
    {
        RegimenUserAssignment::where('user_id', $userId)
            ->where('regimen_plan_id', $plan->id)
            ->get()
            ->each(function (RegimenUserAssignment $ua) use ($isCompleted) {
                if ($ua->status === RegimenUserAssignment::STATUS_REVOKED) {
                    return;
                }

                if ($isCompleted && !$ua->isCompleted()) {
                    $ua->status = RegimenUserAssignment::STATUS_COMPLETED;
                    $ua->completed_at = now();
                    $ua->save();
                } elseif (!$isCompleted && $ua->isCompleted()) {
                    $ua->status = $this->openStatusFor($ua);
                    $ua->completed_at = null;
                    $ua->save();
                }
            });
    }

    /**
     * Hängt den Plan generisch an die Person-Entity im Org-Baum (entity-Dimension),
     * damit er in der Org-Draufsicht ("was hängt an Entity Y?") erscheint. Idempotent
     * (Unique-Constraint). WEICH gekoppelt: ohne Organization-Modul oder ohne
     * Person-Entity ist es ein No-Op — die Zuweisung scheitert nie daran.
     */
    protected function linkPlanToPersonEntity(RegimenPlan $plan, int $userId): void
    {
        $entityClass  = \Platform\Organization\Models\OrganizationEntity::class;
        $defClass     = \Platform\Organization\Models\OrganizationDimensionDefinition::class;
        $valueClass   = \Platform\Organization\Models\OrganizationDimensionValue::class;
        $serviceClass = \Platform\Organization\Services\DimensionLinkService::class;

        if (!class_exists($serviceClass) || !class_exists($entityClass)) {
            return; // Organization-Modul nicht installiert.
        }

        try {
            $entity = $entityClass::persons()->linkedToUser($userId)->first();
            if (!$entity) {
                return;
            }

            $def = $defClass::findByKey('entity');
            if (!$def) {
                return;
            }

            $value = $valueClass::where('dimension_definition_id', $def->id)
                ->where('metadata->source_entity_id', $entity->id)
                ->first();
            if (!$value) {
                return;
            }

            // Platform-Morph-Alias (NICHT $plan->getMorphClass(): RegimenPlan ist nicht
            // in der Morph-Map → das lieferte den FQCN und der Link wäre für Alias-
            // basierte Abfragen unsichtbar).
            app($serviceClass)->link(
                'entity',
                'regimen_plan',
                $plan->id,
                $value->id,
                [
                    'team_id' => $plan->team_id,
                    'created_by_user_id' => $plan->created_by_user_id,
                    'is_primary' => false,
                ],
            );
        } catch (\Throwable $e) {
            // Org-Verlinkung ist optional — nur protokollieren, nicht werfen.
            report($e);
        }
    }

    /** Neue Mitglieder von team-/org-basierten Regeln nachziehen. */
    public function resyncActiveRules(): int
    {
        $count = 0;

        RegimenPlanAssignment::where('status', RegimenPlanAssignment::STATUS_ACTIVE)
            ->whereIn('target_type', ['team', 'org_entity', 'org_role'])
            ->with('plan')
            ->chunkById(100, function ($rules) use (&$count) {
                foreach ($rules as $rule) {
                    $count += $this->fanOut($rule);
                }
            });

        return $count;
    }

    /** Offene, überfällige Zuweisungen markieren. Gibt die Zahl zurück. */
    public function refreshOverdue(): int
    {
        return RegimenUserAssignment::whereNotNull('due_at')
            ->whereDate('due_at', '<', now()->startOfDay())
            ->whereIn('status', [
                RegimenUserAssignment::STATUS_ASSIGNED,
                RegimenUserAssignment::STATUS_IN_PROGRESS,
            ])
            ->update(['status' => RegimenUserAssignment::STATUS_OVERDUE]);
    }

    /** Regel widerrufen: archivieren + offene pro-Person-Zuweisungen auf 'revoked'. */
    public function revoke(RegimenPlanAssignment $rule): void
    {
        $rule->status = RegimenPlanAssignment::STATUS_ARCHIVED;
        $rule->save();

        RegimenUserAssignment::where('regimen_plan_assignment_id', $rule->id)
            ->whereIn('status', [
                RegimenUserAssignment::STATUS_ASSIGNED,
                RegimenUserAssignment::STATUS_IN_PROGRESS,
                RegimenUserAssignment::STATUS_OVERDUE,
            ])
            ->update(['status' => RegimenUserAssignment::STATUS_REVOKED]);
    }

    /** Offene Zuweisungen eines Users (für Lernenden-Ansicht / MCP). */
    public function openForUser(int $userId, int $teamId): Collection
    {
        return RegimenUserAssignment::where('user_id', $userId)
            ->where('team_id', $teamId)
            ->whereIn('status', [
                RegimenUserAssignment::STATUS_ASSIGNED,
                RegimenUserAssignment::STATUS_IN_PROGRESS,
                RegimenUserAssignment::STATUS_OVERDUE,
            ])
            ->with('plan.category')
            ->orderByRaw('due_at is null, due_at asc')
            ->get();
    }

    /**
     * Pflichtkurse eines Users mit Status + Fortschritt — Kontrakt für die
     * persönliche Sicht (home). Überfällig zuerst, dann offen, dann erledigt.
     *
     * @return array<int, array{plan_uuid:?string, title:string, status:string, is_completed:bool, is_overdue:bool, due_at:?string, progress_pct:int}>
     */
    public function mandatoryForUser(int $userId, int $teamId): array
    {
        $items = RegimenUserAssignment::query()
            ->where('user_id', $userId)
            ->where('team_id', $teamId)
            ->where('is_mandatory', true)
            ->where('status', '!=', RegimenUserAssignment::STATUS_REVOKED)
            ->with('plan')
            ->get()
            ->map(function (RegimenUserAssignment $ua) use ($userId) {
                $plan = $ua->plan;
                $progress = $plan ? $plan->progressFor($userId) : ['pct' => 0];

                return [
                    'plan_uuid'    => $plan?->uuid,
                    'title'        => $plan?->title ?? 'Kurs',
                    'url'          => ($plan?->uuid && Route::has('regimen.plans.show'))
                        ? route('regimen.plans.show', ['uuid' => $plan->uuid])
                        : null,
                    'status'       => $ua->status,
                    'is_completed' => $ua->isCompleted(),
                    'is_overdue'   => $ua->status === RegimenUserAssignment::STATUS_OVERDUE,
                    'due_at'       => $ua->due_at?->toDateString(),
                    'progress_pct' => (int) ($progress['pct'] ?? 0),
                ];
            })
            ->all();

        usort($items, function ($a, $b) {
            $pa = $a['is_completed'] ? 2 : ($a['is_overdue'] ? 0 : 1);
            $pb = $b['is_completed'] ? 2 : ($b['is_overdue'] ? 0 : 1);
            if ($pa !== $pb) {
                return $pa <=> $pb;
            }
            return strcmp($a['due_at'] ?? '9999-99-99', $b['due_at'] ?? '9999-99-99');
        });

        return $items;
    }

    /** Sanfte Erinnerungen: bald fällig + überfällig (In-App). */
    public function sendReminders(): void
    {
        $today = now()->startOfDay();

        RegimenUserAssignment::whereNotNull('due_at')
            ->whereIn('status', [
                RegimenUserAssignment::STATUS_ASSIGNED,
                RegimenUserAssignment::STATUS_IN_PROGRESS,
            ])
            ->whereDate('due_at', '>=', $today)
            ->whereDate('due_at', '<=', $today->copy()->addDays(7))
            ->where(fn ($q) => $q->whereNull('reminded_stage')->orWhere('reminded_stage', '!=', 'due_soon'))
            ->with('plan')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $ua) {
                    $this->notify($ua, 'due_soon');
                    $ua->forceFill(['reminded_stage' => 'due_soon', 'last_reminded_at' => now()])->save();
                }
            });

        RegimenUserAssignment::where('status', RegimenUserAssignment::STATUS_OVERDUE)
            ->where(fn ($q) => $q->where('reminded_stage', '!=', 'overdue')
                ->orWhereNull('reminded_stage')
                ->orWhere('last_reminded_at', '<=', now()->subDays(7)))
            ->with('plan')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $ua) {
                    $this->notify($ua, 'overdue');
                    $ua->forceFill(['reminded_stage' => 'overdue', 'last_reminded_at' => now()])->save();
                }
            });
    }

    /** In-App-Benachrichtigung (kind = assigned | due_soon | overdue). */
    public function notify(RegimenUserAssignment $ua, string $kind): void
    {
        $plan = $ua->plan;
        $planTitle = $plan?->title ?? 'Kurs';
        $due = $ua->due_at ? $ua->due_at->format('d.m.Y') : null;

        $title = match ($kind) {
            'assigned' => ($ua->is_mandatory ? 'Neuer Pflichtkurs: ' : 'Neuer Kurs für dich: ') . $planTitle,
            'due_soon' => 'Erinnerung: ' . $planTitle . ' wird fällig',
            'overdue' => 'Überfällig: ' . $planTitle,
            default => $planTitle,
        };

        $message = $ua->is_mandatory
            ? 'Dieser Kurs ist für dich verpflichtend.' . ($due ? ' Fällig bis ' . $due . '.' : '')
            : 'Dieser Kurs wurde dir empfohlen.' . ($due ? ' Bis ' . $due . '.' : '');

        NotificationsNotice::create([
            'notice_type' => 'regimen_assignment_' . $kind,
            'title' => $title,
            'message' => $message,
            'user_id' => $ua->user_id,
            'team_id' => $ua->team_id,
            'noticable_type' => RegimenUserAssignment::class,
            'noticable_id' => $ua->id,
            'metadata' => [
                'regimen_plan_id' => $ua->regimen_plan_id,
                'regimen_plan_uuid' => $plan?->uuid,
                'due_at' => $ua->due_at?->toDateString(),
                'kind' => $kind,
            ],
        ]);
    }

    private function openStatusFor(RegimenUserAssignment $ua): string
    {
        if ($ua->due_at && $ua->due_at->lt(now()->startOfDay())) {
            return RegimenUserAssignment::STATUS_OVERDUE;
        }

        return RegimenUserAssignment::STATUS_ASSIGNED;
    }
}
