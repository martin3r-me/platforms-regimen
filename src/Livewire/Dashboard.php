<?php

namespace Platform\Regimen\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenSessionProgress;
use Platform\Regimen\Models\RegimenPlan;
use Platform\Regimen\Models\RegimenPlanEnrollment;
use Platform\Regimen\Services\RegimenAssignmentService;
use Platform\Regimen\Services\RegimenCategoryService;
use Platform\Regimen\Services\RegimenCertificateService;
use Platform\Regimen\Services\RegimenEnrollmentService;

class Dashboard extends Component
{
    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => null,
            'modelId' => null,
            'subject' => 'Regimen Dashboard',
            'description' => 'Übersicht aller Pläne, Kategorien und Trainingsfortschritt',
            'url' => route('regimen.dashboard'),
            'source' => 'regimen.dashboard',
            'recipients' => [],
            'meta' => ['view_type' => 'dashboard'],
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $teamId = $user?->currentTeam?->id;

        // Zugewiesene / Pflichtpläne (offen), nach Deadline sortiert.
        $assignments = app(RegimenAssignmentService::class)->openForUser($user->id, $teamId)
            ->map(fn ($ua) => [
                'ua' => $ua,
                'plan' => $ua->plan,
                'progress' => $ua->plan?->progressFor($user->id),
            ])
            ->filter(fn ($r) => $r['plan'] !== null)
            ->values();

        // "Meine Regimen" — eingeschriebene Pläne mit Fortschritt + Resume
        $enrollmentRows = app(RegimenEnrollmentService::class)->activeForUser($user->id, $teamId);
        $activePlans = $enrollmentRows->filter(fn ($r) => !$r['enrollment']->isCompleted())->take(6);

        // Abgeschlossene Pläne + zugehoerige Zertifikate.
        $certService = app(RegimenCertificateService::class);
        $completedPlans = $enrollmentRows
            ->filter(fn ($r) => $r['enrollment']->isCompleted())
            ->map(function ($r) use ($certService, $user) {
                $r['certificate'] = $certService->forUserPlan($user->id, $r['plan']);
                return $r;
            })
            ->values();

        $enrolledPlanIds = $enrollmentRows->map(fn ($r) => $r['plan']->id)->all();

        // Kategorien für den Katalog-Filter
        $categories = app(RegimenCategoryService::class)->listForTeam($teamId);

        // "Pläne entdecken" — veröffentlichte Pläne, in die man noch nicht eingeschrieben ist
        $discover = RegimenPlan::query()
            ->where('team_id', $teamId)
            ->where('status', RegimenPlan::STATUS_PUBLISHED)
            ->when($enrolledPlanIds, fn ($q) => $q->whereNotIn('id', $enrolledPlanIds))
            ->with('category')
            ->withCount(['publishedSessions as sessions_count'])
            ->orderBy('sort_order')
            ->orderBy('title')
            ->limit(6)
            ->get();

        // Kennzahlen
        $sessionsCount = RegimenSession::where('team_id', $teamId)->where('status', RegimenSession::STATUS_PUBLISHED)->count();
        $completedCount = RegimenSessionProgress::query()
            ->where('user_id', $user->id)
            ->where('status', RegimenSessionProgress::STATUS_COMPLETED)
            ->count();
        $completedThisWeek = RegimenSessionProgress::query()
            ->where('user_id', $user->id)
            ->where('status', RegimenSessionProgress::STATUS_COMPLETED)
            ->where('completed_at', '>=', now()->startOfWeek())
            ->count();
        $enrolledCount = RegimenPlanEnrollment::where('user_id', $user->id)->where('team_id', $teamId)->count();

        return view('regimen::livewire.dashboard', [
            'firstName' => str($user->name)->explode(' ')->first(),
            'assignments' => $assignments,
            'activePlans' => $activePlans,
            'completedPlans' => $completedPlans,
            'categories' => $categories,
            'discover' => $discover,
            'sessionsCount' => $sessionsCount,
            'completedCount' => $completedCount,
            'completedThisWeek' => $completedThisWeek,
            'enrolledCount' => $enrolledCount,
        ])->layout('platform::layouts.app');
    }
}
