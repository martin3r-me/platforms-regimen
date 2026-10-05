<?php

namespace Platform\Regimen\Services;

use Illuminate\Support\Collection;
use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenSessionProgress;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenPlanEnrollment;

class RegimenEnrollmentService
{
    /**
     * Schreibt einen User bewusst in einen Kurs (Plan) ein. Idempotent.
     */
    public function enroll(int $userId, RegimenPlan $plan): RegimenPlanEnrollment
    {
        $enrollment = RegimenPlanEnrollment::firstOrCreate(
            ['user_id' => $userId, 'regimen_plan_id' => $plan->id],
            [
                'team_id' => $plan->team_id,
                'status' => RegimenPlanEnrollment::STATUS_ACTIVE,
                'enrolled_at' => now(),
                'last_activity_at' => now(),
            ],
        );

        // Falls direkt beim Einschreiben schon alles erledigt ist.
        $this->refreshCompletion($enrollment, $plan);

        return $enrollment;
    }

    /**
     * Beendet die Einschreibung ("Kurs verlassen"). Der Session-Fortschritt bleibt erhalten.
     */
    public function drop(int $userId, RegimenPlan $plan): void
    {
        RegimenPlanEnrollment::where('user_id', $userId)
            ->where('regimen_plan_id', $plan->id)
            ->delete();
    }

    public function isEnrolled(int $userId, RegimenPlan $plan): bool
    {
        return RegimenPlanEnrollment::where('user_id', $userId)
            ->where('regimen_plan_id', $plan->id)
            ->exists();
    }

    /**
     * Aktive & abgeschlossene Einschreibungen eines Users, angereichert mit
     * Fortschritt und Resume-Session. Sortiert nach letzter Aktivitaet.
     *
     * @return Collection<int, array>
     */
    public function activeForUser(int $userId, int $teamId): Collection
    {
        return RegimenPlanEnrollment::query()
            ->where('user_id', $userId)
            ->where('team_id', $teamId)
            ->with(['plan.category'])
            ->orderByRaw('last_activity_at is null, last_activity_at desc')
            ->get()
            ->filter(fn (RegimenPlanEnrollment $e) => $e->plan !== null)
            ->map(function (RegimenPlanEnrollment $e) use ($userId) {
                $progress = $e->plan->progressFor($userId);

                return [
                    'enrollment' => $e,
                    'plan' => $e->plan,
                    'progress' => $progress,
                    'resume' => $this->resumeSession($e),
                ];
            })
            ->values();
    }

    /**
     * Bestimmt die Session, bei der der User weitermachen soll:
     * gemerkter Resume-Punkt > erste nicht abgeschlossene Session > erste Session.
     */
    public function resumeSession(RegimenPlanEnrollment $enrollment): ?RegimenSession
    {
        $plan = $enrollment->plan ?? RegimenPlan::find($enrollment->regimen_plan_id);
        if (!$plan) {
            return null;
        }

        $sessions = $plan->publishedSessions()->get();
        if ($sessions->isEmpty()) {
            return null;
        }

        if ($enrollment->last_session_id) {
            $last = $sessions->firstWhere('id', $enrollment->last_session_id);
            if ($last) {
                return $last;
            }
        }

        $completedIds = $this->completedSessionIds($enrollment->user_id, $sessions->pluck('id')->all());
        $firstOpen = $sessions->first(fn (RegimenSession $l) => !in_array($l->id, $completedIds, true));

        return $firstOpen ?? $sessions->first();
    }

    /**
     * Aktualisiert den Resume-Punkt fuer alle Kurse, in die der User eingeschrieben
     * ist und die diese Session enthalten. Wird beim Oeffnen einer Session aufgerufen.
     */
    public function touch(int $userId, RegimenSession $session): void
    {
        $planIds = $session->plans()->pluck('regimen_plans.id');
        if ($planIds->isEmpty()) {
            return;
        }

        RegimenPlanEnrollment::where('user_id', $userId)
            ->whereIn('regimen_plan_id', $planIds)
            ->update([
                'last_session_id' => $session->id,
                'last_activity_at' => now(),
            ]);
    }

    /**
     * Prueft nach Abschluss einer Session, ob dadurch ein eingeschriebener Kurs
     * vollstaendig wurde, und markiert die Einschreibung ggf. als abgeschlossen.
     */
    public function syncCompletion(int $userId, RegimenSession $session): void
    {
        $planIds = $session->plans()->pluck('regimen_plans.id');
        if ($planIds->isEmpty()) {
            return;
        }

        $enrollments = RegimenPlanEnrollment::where('user_id', $userId)
            ->whereIn('regimen_plan_id', $planIds)
            ->with('plan')
            ->get();

        foreach ($enrollments as $enrollment) {
            if ($enrollment->plan) {
                $this->refreshCompletion($enrollment, $enrollment->plan);
            }
        }
    }

    /**
     * Setzt den Status einer Einschreibung anhand des aktuellen Fortschritts.
     */
    protected function refreshCompletion(RegimenPlanEnrollment $enrollment, RegimenPlan $plan): void
    {
        $progress = $plan->progressFor($enrollment->user_id);
        $isComplete = $progress['total'] > 0 && $progress['completed'] >= $progress['total'];

        if ($isComplete && !$enrollment->isCompleted()) {
            $enrollment->status = RegimenPlanEnrollment::STATUS_COMPLETED;
            $enrollment->completed_at = now();
            $enrollment->save();

            // Kurs vollstaendig -> Zertifikat ausstellen (idempotent).
            app(RegimenCertificateService::class)->issueIfComplete($enrollment->user_id, $plan);
        } elseif (!$isComplete && $enrollment->isCompleted()) {
            // Kurs wurde erweitert oder Session wieder geoeffnet -> zurueck auf aktiv.
            $enrollment->status = RegimenPlanEnrollment::STATUS_ACTIVE;
            $enrollment->completed_at = null;
            $enrollment->save();
        }

        // Pflichtkurs-Zuweisungen (falls vorhanden) synchron zum Fortschritt halten.
        app(RegimenAssignmentService::class)->syncPlanCompletion($enrollment->user_id, $plan, $isComplete);
    }

    /**
     * @param  array<int, int>  $sessionIds
     * @return array<int, int>
     */
    protected function completedSessionIds(int $userId, array $sessionIds): array
    {
        if (empty($sessionIds)) {
            return [];
        }

        return RegimenSessionProgress::query()
            ->where('user_id', $userId)
            ->whereIn('regimen_session_id', $sessionIds)
            ->where('status', RegimenSessionProgress::STATUS_COMPLETED)
            ->pluck('regimen_session_id')
            ->all();
    }
}
