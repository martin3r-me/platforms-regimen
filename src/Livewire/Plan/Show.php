<?php

namespace Platform\Regimen\Livewire\Plan;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenUserAssignment;
use Platform\Regimen\Services\RegimenCertificateService;
use Platform\Regimen\Services\RegimenEnrollmentService;
use Platform\Regimen\Services\RegimenProgressService;

class Show extends Component
{
    public string $uuid;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function enroll(): void
    {
        $user = Auth::user();
        $plan = $this->resolvePlan($user);
        app(RegimenEnrollmentService::class)->enroll($user->id, $plan);
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

        // Offene Zuweisung dieses Users für diesen Kurs (für den Pflicht-Banner).
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
