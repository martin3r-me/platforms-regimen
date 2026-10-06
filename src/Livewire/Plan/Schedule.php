<?php

namespace Platform\Regimen\Livewire\Plan;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenPlanEntry;
use Platform\Regimen\Services\RegimenScheduleService;

/**
 * "Mein Trainingsplan" — der persönliche, DATIERTE Plan einer Person.
 * Zeigt die regimen_plan_entries nach Woche/Datum und schließt sie PRO EINTRAG
 * ab (nicht pro Session-Template — dieselbe Einheit an mehreren Tagen ist
 * unabhängig abhakbar).
 */
class Schedule extends Component
{
    public string $uuid;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function markDone(int $entryId): void
    {
        $entry = $this->resolveEntry($entryId);
        if ($entry) {
            app(RegimenScheduleService::class)->completeEntry($entry);
        }
    }

    public function reopen(int $entryId): void
    {
        $entry = $this->resolveEntry($entryId);
        if ($entry) {
            app(RegimenScheduleService::class)->reopenEntry($entry);
        }
    }

    public function render()
    {
        $user = Auth::user();
        $plan = $this->resolvePlan($user);
        $enrollment = $plan->enrollmentFor($user->id);

        $entries = $enrollment
            ? $enrollment->entries()->get()
            : collect();

        $weeks = $entries->groupBy('week')->sortKeys();
        $total = $entries->count();
        $done = $entries->where('status', RegimenPlanEntry::STATUS_COMPLETED)->count();

        return view('regimen::livewire.plan.schedule', [
            'plan' => $plan,
            'enrollment' => $enrollment,
            'weeks' => $weeks,
            'total' => $total,
            'done' => $done,
            'pct' => $total > 0 ? (int) round($done / $total * 100) : 0,
            'accentColor' => $plan->coverColor(),
        ])->layout('platform::layouts.app');
    }

    protected function resolvePlan($user): RegimenPlan
    {
        return RegimenPlan::query()
            ->where('uuid', $this->uuid)
            ->where('team_id', $user->currentTeam->id)
            ->firstOrFail();
    }

    protected function resolveEntry(int $entryId): ?RegimenPlanEntry
    {
        $user = Auth::user();

        return RegimenPlanEntry::query()
            ->where('id', $entryId)
            ->where('user_id', $user->id)
            ->where('team_id', $user->currentTeam->id)
            ->first();
    }
}
