<?php

namespace Platform\Regimen\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Platform\Regimen\Models\RegimenPlanEnrollment;
use Platform\Regimen\Models\RegimenPlanEntry;

/**
 * Materialisiert ein Plan-Template in den persönlichen, datierten Plan einer
 * Person: aus start_date + (week, weekday) je Einheit werden absolute
 * Kalendertage und daraus regimen_plan_entries (mit Snapshot der Ziele).
 */
class RegimenScheduleService
{
    /**
     * Erzeugt die datierten Einträge für eine Enrollment.
     *
     * @param  bool  $force  Vorhandene, noch nicht absolvierte Einträge neu aufbauen.
     *                       Bereits gematchte/absolvierte Einträge bleiben unangetastet.
     * @return int  Anzahl erzeugter Einträge.
     */
    public function materialize(RegimenPlanEnrollment $enrollment, bool $force = false): int
    {
        if (!$enrollment->start_date) {
            throw new \InvalidArgumentException('Enrollment hat kein start_date — persönlicher Plan nicht materialisierbar.');
        }

        $existing = $enrollment->entries()->count();
        if ($existing > 0 && !$force) {
            return 0;
        }

        // Wochen-Anker: Montag der Startwoche. So landet weekday=7 (So) auf einem Sonntag,
        // egal auf welchen Wochentag start_date fällt.
        $anchorMonday = $enrollment->start_date->copy()->startOfWeek(Carbon::MONDAY);

        $plan = $enrollment->plan()->first();
        $sessions = $plan->sessions()->get();

        return DB::transaction(function () use ($enrollment, $sessions, $anchorMonday, $force) {
            if ($force) {
                // Nur ungelaufene Einträge verwerfen — absolvierte/gematchte behalten.
                $enrollment->entries()
                    ->whereNull('regimen_activity_id')
                    ->where('status', RegimenPlanEntry::STATUS_PLANNED)
                    ->delete();
            }

            $created = 0;
            foreach ($sessions as $session) {
                $week = $session->pivot->week;
                $weekday = $session->pivot->weekday;

                // Nur Einheiten, die bereits auf einen Tag gelegt wurden.
                if ($week === null || $weekday === null) {
                    continue;
                }

                $date = $anchorMonday->copy()->addDays(($week - 1) * 7 + ($weekday - 1));

                // Idempotenz: an diesem Tag für diese Session nicht doppelt anlegen.
                $exists = $enrollment->entries()
                    ->whereDate('scheduled_date', $date->toDateString())
                    ->where('regimen_session_id', $session->id)
                    ->exists();
                if ($exists) {
                    continue;
                }

                RegimenPlanEntry::create([
                    'regimen_plan_enrollment_id' => $enrollment->id,
                    'user_id' => $enrollment->user_id,
                    'team_id' => $enrollment->team_id,
                    'regimen_plan_id' => $enrollment->regimen_plan_id,
                    'regimen_session_id' => $session->id,
                    'scheduled_date' => $date->toDateString(),
                    'week' => $week,
                    'weekday' => $weekday,
                    'sort_order' => $session->pivot->sort_order ?? 0,
                    // Snapshot der geplanten Werte:
                    'title' => $session->title,
                    'kind' => $session->kind,
                    'target_distance_m' => $session->target_distance_m,
                    'target_duration_s' => $session->target_duration_s,
                    'target_pace_s_per_km' => $session->target_pace_s_per_km,
                    'structure' => $session->structure,
                    'status' => RegimenPlanEntry::STATUS_PLANNED,
                ]);
                $created++;
            }

            return $created;
        });
    }
}
