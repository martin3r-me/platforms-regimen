<?php

namespace Platform\Regimen\Livewire\Topic;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenTopic;
use Platform\Regimen\Services\RegimenProgressService;

class Show extends Component
{
    public string $uuid;

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function render()
    {
        $user = Auth::user();
        $topic = RegimenTopic::query()
            ->where('uuid', $this->uuid)
            ->where('team_id', $user->currentTeam->id)
            ->firstOrFail();

        $sessions = $topic->sessions()
            ->where('status', RegimenSession::STATUS_PUBLISHED)
            ->get();

        $progress = app(RegimenProgressService::class);
        $completedIds = $progress->completedSessionIdsForUser($user->id, $sessions->pluck('id')->all());
        $completedSet = array_flip($completedIds);

        $total = $sessions->count();
        $done = count($completedSet);
        $pct = $total > 0 ? (int) round($done / $total * 100) : 0;

        $this->dispatch('comms', [
            'model' => \Platform\Regimen\Models\RegimenTopic::class,
            'modelId' => $topic->id,
            'subject' => 'Regimen: ' . $topic->title,
            'description' => $topic->description,
            'url' => route('regimen.topics.show', ['uuid' => $topic->uuid]),
            'source' => 'regimen.topics.show',
            'recipients' => [],
            'meta' => ['view_type' => 'show', 'resource' => 'topic'],
        ]);

        return view('regimen::livewire.topic.show', [
            'topic' => $topic,
            'sessions' => $sessions,
            'completedSet' => $completedSet,
            'total' => $total,
            'done' => $done,
            'pct' => $pct,
        ])->layout('platform::layouts.app');
    }
}
