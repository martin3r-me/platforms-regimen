<?php

namespace Platform\Regimen\Livewire\Certificate;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Platform\Regimen\Models\RegimenCertificate;

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

        $certificate = RegimenCertificate::query()
            ->where('uuid', $this->uuid)
            ->where('team_id', $user->currentTeam->id)
            ->with(['plan.category', 'user'])
            ->firstOrFail();

        $this->dispatch('comms', [
            'model' => RegimenCertificate::class,
            'modelId' => $certificate->id,
            'subject' => 'Zertifikat: ' . ($certificate->plan?->title ?? ''),
            'description' => 'Abschlusszertifikat ' . $certificate->serial,
            'url' => route('regimen.certificates.show', ['uuid' => $certificate->uuid]),
            'source' => 'regimen.certificates.show',
            'recipients' => [],
            'meta' => ['view_type' => 'show', 'resource' => 'certificate'],
        ]);

        return view('regimen::livewire.certificate.show', [
            'certificate' => $certificate,
            'plan' => $certificate->plan,
            'holder' => $certificate->user,
            'accentColor' => $certificate->plan?->coverColor() ?? '#4F46E5',
        ])->layout('platform::layouts.app');
    }
}
