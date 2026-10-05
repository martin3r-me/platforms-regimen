<?php

namespace Platform\Regimen\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenPlan;

class RegimenPlanService
{
    public function listForTeam(int $teamId, bool $publishedOnly = false)
    {
        $query = RegimenPlan::query()
            ->where('team_id', $teamId)
            ->with('category')
            ->withCount('sessions')
            ->orderBy('sort_order')
            ->orderBy('title');

        if ($publishedOnly) {
            $query->where('status', RegimenPlan::STATUS_PUBLISHED);
        }

        return $query->get();
    }

    public function create(int $teamId, int $userId, array $attributes): RegimenPlan
    {
        $slug = $attributes['slug'] ?? Str::slug($attributes['title']);
        $code = isset($attributes['code']) ? trim((string) $attributes['code']) : null;

        return RegimenPlan::create([
            'team_id' => $teamId,
            'regimen_category_id' => $attributes['regimen_category_id'] ?? null,
            'created_by_user_id' => $userId,
            'slug' => $this->uniqueSlug($teamId, $slug),
            'title' => $attributes['title'],
            'code' => $code ? $this->uniqueCode($teamId, $code) : null,
            'level' => $this->normalizeLevel($attributes['level'] ?? null),
            'description' => $attributes['description'] ?? null,
            'icon' => $attributes['icon'] ?? null,
            'color' => $attributes['color'] ?? null,
            'target_audience' => $attributes['target_audience'] ?? null,
            'type' => $this->normalizeType($attributes['type'] ?? null),
            'duration_weeks' => $attributes['duration_weeks'] ?? null,
            'status' => $attributes['status'] ?? RegimenPlan::STATUS_DRAFT,
            'public' => (bool) ($attributes['public'] ?? false),
            'sort_order' => $attributes['sort_order'] ?? $this->nextSortOrder($teamId),
        ]);
    }

    public function update(RegimenPlan $plan, array $attributes): RegimenPlan
    {
        $data = array_intersect_key($attributes, array_flip([
            'title', 'regimen_category_id', 'level', 'description', 'icon', 'color',
            'target_audience', 'type', 'duration_weeks', 'status', 'public', 'sort_order',
        ]));

        if (array_key_exists('level', $data)) {
            $data['level'] = $this->normalizeLevel($data['level']);
        }

        if (array_key_exists('type', $data)) {
            $data['type'] = $this->normalizeType($data['type']);
        }

        if (array_key_exists('public', $data)) {
            $data['public'] = (bool) $data['public'];
        }

        // Code nur anfassen, wenn uebergeben - und Eindeutigkeit sicherstellen
        // (der eigene bestehende Code darf erhalten bleiben).
        if (array_key_exists('code', $attributes)) {
            $code = trim((string) $attributes['code']);
            $data['code'] = $code === '' ? null : $this->uniqueCode($plan->team_id, $code, $plan->id);
        }

        $plan->fill($data);
        $plan->save();

        return $plan;
    }

    protected function normalizeLevel(mixed $level): ?string
    {
        if ($level === null || $level === '') {
            return null;
        }

        return array_key_exists($level, RegimenPlan::LEVELS) ? $level : null;
    }

    protected function normalizeType(mixed $type): string
    {
        if (is_string($type) && array_key_exists($type, RegimenPlan::TYPES)) {
            return $type;
        }

        return RegimenPlan::DEFAULT_TYPE;
    }

    protected function uniqueCode(int $teamId, string $base, ?int $ignoreId = null): string
    {
        $base = strtoupper($base);
        $candidate = $base;
        $i = 2;

        while (
            RegimenPlan::where('team_id', $teamId)
                ->where('code', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $candidate = $base . '-' . $i++;
        }

        return $candidate;
    }

    public function delete(RegimenPlan $plan): void
    {
        $plan->delete();
    }

    public function attachSession(RegimenPlan $plan, RegimenSession $session, ?int $sortOrder = null): void
    {
        $sortOrder ??= ($plan->sessions()->max('regimen_plan_sessions.sort_order') ?? 0) + 10;

        $plan->sessions()->syncWithoutDetaching([
            $session->id => ['sort_order' => $sortOrder],
        ]);
    }

    public function detachSession(RegimenPlan $plan, RegimenSession $session): void
    {
        $plan->sessions()->detach($session->id);
    }

    /**
     * Legt eine Einheit auf einen Tag im Wochen×7-Raster (week 1..N, weekday 1=Mo..7=So).
     * Dieselbe Einheit darf an mehreren Tagen liegen; eine bereits an genau diesem Tag
     * platzierte Einheit wird nur in der sort_order aktualisiert (idempotent).
     */
    public function placeSessionOnDay(RegimenPlan $plan, RegimenSession $session, int $week, int $weekday, ?int $sortOrder = null): void
    {
        $sortOrder ??= 0;

        $already = DB::table('regimen_plan_sessions')
            ->where('regimen_plan_id', $plan->id)
            ->where('regimen_session_id', $session->id)
            ->where('week', $week)
            ->where('weekday', $weekday)
            ->exists();

        if ($already) {
            DB::table('regimen_plan_sessions')
                ->where('regimen_plan_id', $plan->id)
                ->where('regimen_session_id', $session->id)
                ->where('week', $week)
                ->where('weekday', $weekday)
                ->update(['sort_order' => $sortOrder, 'updated_at' => now()]);
            return;
        }

        $plan->sessions()->attach($session->id, [
            'week' => $week,
            'weekday' => $weekday,
            'sort_order' => $sortOrder,
        ]);
    }

    public function reorderSessions(RegimenPlan $plan, array $sessionIdsInOrder): void
    {
        foreach (array_values($sessionIdsInOrder) as $index => $sessionId) {
            $plan->sessions()->updateExistingPivot($sessionId, [
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }

    protected function uniqueSlug(int $teamId, string $base): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $i = 2;

        while (RegimenPlan::where('team_id', $teamId)->where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $i++;
        }

        return $candidate;
    }

    protected function nextSortOrder(int $teamId): int
    {
        return (int) RegimenPlan::where('team_id', $teamId)->max('sort_order') + 10;
    }
}
