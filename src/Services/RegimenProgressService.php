<?php

namespace Platform\Regimen\Services;

use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenSessionProgress;
use Platform\Regimen\Models\RegimenPlan;

class RegimenProgressService
{
    public function start(int $userId, RegimenSession $session): RegimenSessionProgress
    {
        return RegimenSessionProgress::firstOrCreate(
            ['user_id' => $userId, 'regimen_session_id' => $session->id],
            ['status' => RegimenSessionProgress::STATUS_IN_PROGRESS, 'started_at' => now()],
        );
    }

    public function complete(int $userId, RegimenSession $session): RegimenSessionProgress
    {
        $progress = RegimenSessionProgress::firstOrNew([
            'user_id' => $userId,
            'regimen_session_id' => $session->id,
        ]);

        $progress->status = RegimenSessionProgress::STATUS_COMPLETED;
        $progress->started_at ??= now();
        $progress->completed_at = now();
        $progress->save();

        // Kurs-Einschreibung ggf. auf "abgeschlossen" heben.
        app(RegimenEnrollmentService::class)->syncCompletion($userId, $session);

        return $progress;
    }

    public function reopen(int $userId, RegimenSession $session): ?RegimenSessionProgress
    {
        $progress = RegimenSessionProgress::where('user_id', $userId)
            ->where('regimen_session_id', $session->id)
            ->first();

        if (!$progress) {
            return null;
        }

        $progress->status = RegimenSessionProgress::STATUS_IN_PROGRESS;
        $progress->completed_at = null;
        $progress->save();

        // Falls ein Kurs dadurch nicht mehr 100% ist, Einschreibung reaktivieren.
        app(RegimenEnrollmentService::class)->syncCompletion($userId, $session);

        return $progress;
    }

    public function summaryForPlan(int $userId, RegimenPlan $plan): array
    {
        return $plan->progressFor($userId);
    }

    public function completedSessionIdsForUser(int $userId, array $sessionIds): array
    {
        if (empty($sessionIds)) {
            return [];
        }

        return RegimenSessionProgress::query()
            ->where('user_id', $userId)
            ->whereIn('regimen_session_id', $sessionIds)
            ->where('status', RegimenSessionProgress::STATUS_COMPLETED)
            ->pluck('regimen_session_id')
            ->all();
    }
}
