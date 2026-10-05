<?php

namespace Platform\Regimen\Livewire\Topic;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Services\RegimenProgressService;
use Platform\Regimen\Services\RegimenTopicService;

class Index extends Component
{
    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => null, 'modelId' => null,
            'subject' => 'Regimen: Themen',
            'description' => 'Bibliothek — alle Lektionen nach Thema',
            'url' => route('regimen.topics.index'),
            'source' => 'regimen.topics.index',
            'recipients' => [],
            'meta' => ['view_type' => 'index', 'resource' => 'topics'],
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $topics = app(RegimenTopicService::class)->listForTeam($teamId);

        // Fortschritt pro Thema (abgeschlossene veröffentlichte Lektionen).
        $sessionRows = RegimenSession::query()
            ->where('team_id', $teamId)
            ->where('status', RegimenSession::STATUS_PUBLISHED)
            ->whereIn('regimen_topic_id', $topics->pluck('id'))
            ->get(['id', 'regimen_topic_id']);

        $byTopic = $sessionRows->groupBy('regimen_topic_id');
        $completedSet = array_flip(
            app(RegimenProgressService::class)
                ->completedSessionIdsForUser($user->id, $sessionRows->pluck('id')->all())
        );

        foreach ($topics as $topic) {
            $ids = ($byTopic[$topic->id] ?? collect())->pluck('id');
            $total = $ids->count();
            $done = $ids->filter(fn ($id) => isset($completedSet[$id]))->count();
            $topic->setAttribute('session_total', $total);
            $topic->setAttribute('session_done', $done);
            $topic->setAttribute('progress_pct', $total > 0 ? (int) round($done / $total * 100) : 0);
        }

        // Leere Autoren-Themen (0 veröffentlichte Lektionen) für Lernende ausblenden.
        $topics = $topics->filter(fn ($topic) => $topic->session_total > 0)->values();

        return view('regimen::livewire.topic.index', [
            'topics' => $topics,
            'sessionsTotal' => $sessionRows->count(),
            'completedTotal' => count($completedSet),
        ])->layout('platform::layouts.app');
    }
}
