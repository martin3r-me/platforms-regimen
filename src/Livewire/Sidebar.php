<?php

namespace Platform\Regimen\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Regimen\Models\RegimenUserAssignment;
use Platform\Regimen\Services\RegimenAssignmentService;
use Platform\Regimen\Services\RegimenEnrollmentService;

class Sidebar extends Component
{
    public function render()
    {
        $user = Auth::user();

        if (!$user) {
            return view('regimen::livewire.sidebar', ['plans' => collect(), 'assignments' => collect()]);
        }

        // Nur abonnierte Pläne — sortiert nach letzter Aktivität.
        $plans = app(RegimenEnrollmentService::class)
            ->activeForUser($user->id, $user->currentTeam->id)
            ->map(fn ($row) => [
                'uuid' => $row['plan']->uuid,
                'title' => $row['plan']->title,
                'icon' => $row['plan']->icon,
                'pct' => $row['progress']['pct'],
                'completed' => $row['enrollment']->isCompleted(),
            ])
            ->take(8);

        // Offene Pflicht-/zugewiesene Pläne — nach Deadline sortiert (siehe openForUser).
        $assignments = app(RegimenAssignmentService::class)
            ->openForUser($user->id, $user->currentTeam->id)
            ->map(fn ($ua) => [
                'uuid' => $ua->plan?->uuid,
                'title' => $ua->plan?->title,
                'due' => $ua->due_at?->format('d.m.'),
                'due_full' => $ua->due_at?->format('d.m.Y'),
                'overdue' => $ua->status === RegimenUserAssignment::STATUS_OVERDUE,
            ])
            ->filter(fn ($a) => $a['uuid'] !== null)
            ->values()
            ->take(8);

        return view('regimen::livewire.sidebar', [
            'plans' => $plans,
            'assignments' => $assignments,
        ]);
    }
}
