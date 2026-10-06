<?php

namespace Platform\Regimen\Livewire\Plan;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenUserAssignment;
use Platform\Regimen\Services\RegimenAssignmentService;
use Platform\Regimen\Services\RegimenCertificateService;
use Platform\Regimen\Services\RegimenEnrollmentService;
use Platform\Regimen\Services\RegimenProgressService;

class Show extends Component
{
    public string $uuid;

    /** Startdatum für „Plan starten" — einmalig gewählt, treibt den datierten Plan. */
    public string $startDate = '';

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
        // Default: nächster Montag (sauberer Wochenstart).
        $this->startDate = Carbon::now()->next(Carbon::MONDAY)->toDateString();
    }

    public function enroll()
    {
        $user = Auth::user();
        $plan = $this->resolvePlan($user);

        try {
            $start = Carbon::parse($this->startDate)->toDateString();
        } catch (\Throwable $e) {
            $start = Carbon::today()->toDateString();
        }

        // Selbst-Start läuft über denselben Weg wie die Zuweisung: erzeugt
        // Enrollment, Startdatum, den datierten persönlichen Plan (materialize)
        // und den Org-Link — alles konsistent.
        app(RegimenAssignmentService::class)->assign(
            $plan, 'user', (int) $user->id, [], (int) $user->id,
            ['is_mandatory' => false, 'starts_at' => $start],
        );

        return $this->redirect(route('regimen.plans.schedule', ['uuid' => $plan->uuid]), navigate: true);
    }

    public function drop(): void
    {
        $user = Auth::user();
        $plan = $this->resolvePlan($user);
        app(RegimenEnrollmentService::class)->drop($user->id, $plan);
    }

    public function render()
    {
        $user = Auth::user();
        $plan = $this->resolvePlan($user);

        $sessions = $plan->publishedSessions()->with('topic')->get();
        $summary = app(RegimenProgressService::class)->summaryForPlan($user->id, $plan);

        $completedIds = app(RegimenProgressService::class)
            ->completedSessionIdsForUser($user->id, $sessions->pluck('id')->all());
        $completedSet = array_flip($completedIds);

        $enrollment = $plan->enrollmentFor($user->id);
        $resumeSession = $enrollment
            ? app(RegimenEnrollmentService::class)->resumeSession($enrollment)
            : null;

        $certificate = app(RegimenCertificateService::class)->forUserPlan($user->id, $plan);

        // Offene Zuweisung dieses Users für diesen Plan (für den Pflicht-Banner).
        // Das Zuweisen selbst passiert per MCP (regimen.assignments.*), nicht in der UI.
        $assignment = RegimenUserAssignment::where('user_id', $user->id)
            ->where('regimen_plan_id', $plan->id)
            ->whereIn('status', [
                RegimenUserAssignment::STATUS_ASSIGNED,
                RegimenUserAssignment::STATUS_IN_PROGRESS,
                RegimenUserAssignment::STATUS_OVERDUE,
            ])
            ->orderByRaw('due_at is null, due_at asc')
            ->first();

        $this->dispatch('comms', [
            'model' => \Platform\Regimen\Models\RegimenPlan::class,
            'modelId' => $plan->id,
            'subject' => 'Regimen: ' . $plan->title,
            'description' => $plan->description,
            'url' => route('regimen.plans.show', ['uuid' => $plan->uuid]),
            'source' => 'regimen.plans.show',
            'recipients' => [],
            'meta' => ['view_type' => 'show', 'resource' => 'plan'],
        ]);

        return view('regimen::livewire.plan.show', [
            'plan' => $plan,
            'sessions' => $sessions,
            'summary' => $summary,
            'completedSet' => $completedSet,
            'enrollment' => $enrollment,
            'resumeSession' => $resumeSession,
            'certificate' => $certificate,
            'assignment' => $assignment,
        ])->layout('platform::layouts.app');
    }

    protected function resolvePlan($user): RegimenPlan
    {
        return RegimenPlan::query()
            ->with('category')
            ->where('uuid', $this->uuid)
            ->where('team_id', $user->currentTeam->id)
            ->firstOrFail();
    }
}
