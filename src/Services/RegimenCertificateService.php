<?php

namespace Platform\Regimen\Services;

use Illuminate\Support\Str;
use Platform\Regimen\Models\RegimenCertificate;
use Platform\Regimen\Models\RegimenPlan;

class RegimenCertificateService
{
    /**
     * Stellt ein Zertifikat aus, sobald ein Kurs zu 100% abgeschlossen ist.
     * Idempotent: existiert bereits eines fuer User+Kurs, wird es zurueckgegeben.
     */
    public function issueIfComplete(int $userId, RegimenPlan $plan): ?RegimenCertificate
    {
        $existing = $plan->certificateFor($userId);
        if ($existing) {
            return $existing;
        }

        $progress = $plan->progressFor($userId);
        if ($progress['total'] === 0 || $progress['completed'] < $progress['total']) {
            return null;
        }

        return RegimenCertificate::create([
            'user_id' => $userId,
            'regimen_plan_id' => $plan->id,
            'team_id' => $plan->team_id,
            'serial' => $this->generateSerial($plan),
            'issued_at' => now(),
        ]);
    }

    public function forUserPlan(int $userId, RegimenPlan $plan): ?RegimenCertificate
    {
        return $plan->certificateFor($userId);
    }

    /**
     * Seriennummer im Format CODE-JAHR-LFDNR, z. B. VO-2026-0007.
     * Faellt der Kurs ohne Code, wird eine Kurzform aus der Plan-ID genutzt.
     */
    protected function generateSerial(RegimenPlan $plan): string
    {
        $code = $plan->code
            ? Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $plan->code))
            : ('P' . $plan->id);
        $year = now()->format('Y');

        $seq = RegimenCertificate::where('regimen_plan_id', $plan->id)->count() + 1;

        do {
            $serial = sprintf('%s-%s-%04d', $code, $year, $seq);
            $seq++;
        } while (RegimenCertificate::where('serial', $serial)->exists());

        return $serial;
    }
}
