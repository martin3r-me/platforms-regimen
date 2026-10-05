<?php

namespace Platform\Regimen\Organization;

use Platform\Organization\Contracts\PersonActivityProvider;
use Platform\Regimen\Services\RegimenAssignmentService;

/**
 * Speist Regimen-Pflichtkurse in die persönliche Sicht (home) — über den
 * PersonActivityRegistry-Kontrakt, genauso wie planner/helpdesk. Basis ist der
 * regimen-eigene Service (mandatoryForUser), kein Fremdzugriff aufs Modell.
 */
class RegimenPersonActivityProvider implements PersonActivityProvider
{
    public function sectionKey(): string
    {
        return 'regimen';
    }

    public function sectionConfig(): array
    {
        return [
            'label'       => 'Akademie',
            'icon'        => 'academic-cap',
            'description' => 'Deine Pflichtkurse',
        ];
    }

    public function vitalSigns(int $userId, int $teamId): array
    {
        $plans = $this->plans($userId, $teamId);
        if (empty($plans)) {
            return [];
        }

        $total = count($plans);
        $done = count(array_filter($plans, fn ($c) => $c['is_completed']));
        $overdue = count(array_filter($plans, fn ($c) => $c['is_overdue']));
        $open = $total - $done;

        $variant = $overdue > 0 ? 'danger' : ($open > 0 ? 'warning' : 'success');

        return [
            ['key' => 'pflicht', 'label' => 'Pflichtkurse', 'value' => $done . '/' . $total, 'variant' => $variant],
        ];
    }

    public function metricConfig(): array
    {
        return [
            'pflicht_offen' => ['label' => 'Offene Pflichtkurse', 'type' => 'warning', 'sort_weight' => 6],
        ];
    }

    public function responsibilities(int $userId, int $teamId, int $limit = 5): array
    {
        $plans = $this->plans($userId, $teamId);
        $open = array_values(array_filter($plans, fn ($c) => !$c['is_completed']));

        if (empty($open)) {
            return [];
        }

        $items = [];
        foreach (array_slice($open, 0, $limit) as $i => $c) {
            $items[] = [
                'id'   => $i,
                'name' => $c['title'],
                'url'  => $c['url'] ?? null,
                'meta' => $this->metaFor($c),
            ];
        }

        return [[
            'key'         => 'pflicht',
            'label'       => 'Offene Pflichtkurse',
            'icon'        => 'academic-cap',
            'total_count' => count($open),
            'items'       => $items,
        ]];
    }

    /** @return array<int, array<string,mixed>> */
    protected function plans(int $userId, int $teamId): array
    {
        try {
            return resolve(RegimenAssignmentService::class)->mandatoryForUser($userId, $teamId);
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function metaFor(array $plan): string
    {
        if ($plan['is_overdue']) {
            return 'überfällig';
        }
        if ($plan['due_at']) {
            return 'fällig ' . \Illuminate\Support\Carbon::parse($plan['due_at'])->format('d.m.Y');
        }
        return $plan['progress_pct'] . '%';
    }
}
