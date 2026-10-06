<?php

namespace Platform\Regimen\Services;

use Illuminate\Support\Str;
use Platform\Regimen\Models\RegimenCategory;
use Platform\Regimen\Models\RegimenPlan;

class RegimenCategoryService
{
    /**
     * Standard-Kategorien im Udacity-"Disziplin"-Stil — je mit eigener Signalfarbe
     * und Code-Prefix. Werden vom Seeder pro Team angelegt.
     */
    public const DEFAULTS = [
        ['title' => 'AI & Automation',        'slug' => 'ai-automation',        'color' => '#7C3AED', 'code_prefix' => 'AI',  'icon' => 'heroicon-o-cpu-chip'],
        ['title' => 'Frontend & Engineering', 'slug' => 'frontend-engineering', 'color' => '#4F46E5', 'code_prefix' => 'FE',  'icon' => 'heroicon-o-code-bracket'],
        ['title' => 'Value Stream',           'slug' => 'value-stream',         'color' => '#0D9488', 'code_prefix' => 'VSM', 'icon' => 'heroicon-o-arrow-trending-up'],
        ['title' => 'Business & Strategy',    'slug' => 'business-strategy',    'color' => '#D97706', 'code_prefix' => 'BIZ', 'icon' => 'heroicon-o-briefcase'],
        ['title' => 'Sales & Growth',         'slug' => 'sales-growth',         'color' => '#E11D48', 'code_prefix' => 'SAL', 'icon' => 'heroicon-o-rocket-launch'],
        ['title' => 'Design & UX',            'slug' => 'design-ux',            'color' => '#0891B2', 'code_prefix' => 'UX',  'icon' => 'heroicon-o-swatch'],
    ];

    public function listForTeam(int $teamId)
    {
        return RegimenCategory::query()
            ->where('team_id', $teamId)
            ->withCount('plans')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function create(int $teamId, ?int $userId, array $attributes): RegimenCategory
    {
        $slug = $attributes['slug'] ?? Str::slug($attributes['title']);

        return RegimenCategory::create([
            'team_id' => $teamId,
            'created_by_user_id' => $userId,
            'slug' => $this->uniqueSlug($teamId, $slug),
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
            'color' => $attributes['color'] ?? null,
            'code_prefix' => $attributes['code_prefix'] ?? null,
            'icon' => $attributes['icon'] ?? null,
            'sort_order' => $attributes['sort_order'] ?? $this->nextSortOrder($teamId),
        ]);
    }

    public function update(RegimenCategory $category, array $attributes): RegimenCategory
    {
        $category->fill(array_intersect_key($attributes, array_flip([
            'title', 'description', 'color', 'code_prefix', 'icon', 'sort_order',
        ])));
        $category->save();

        return $category;
    }

    public function delete(RegimenCategory $category): void
    {
        // Plans behalten (regimen_category_id wird per nullOnDelete auf null gesetzt).
        $category->delete();
    }

    /**
     * Legt die Standard-Kategorien fuer ein Team an (idempotent per slug).
     *
     * @return array<int, RegimenCategory>
     */
    public function seedDefaults(int $teamId, ?int $userId = null): array
    {
        $created = [];
        foreach (self::DEFAULTS as $i => $def) {
            $category = RegimenCategory::firstOrCreate(
                ['team_id' => $teamId, 'slug' => $def['slug']],
                [
                    'created_by_user_id' => $userId,
                    'title' => $def['title'],
                    'color' => $def['color'],
                    'code_prefix' => $def['code_prefix'],
                    'icon' => $def['icon'],
                    'sort_order' => ($i + 1) * 10,
                ],
            );
            $created[] = $category;
        }

        return $created;
    }

    /**
     * Naechster freier Plan-Code fuer eine Kategorie, z.B. "AI-110".
     * Zaehlt in 10er-Schritten hoch, damit spaeter Platz zum Einschieben bleibt.
     */
    public function suggestCode(RegimenCategory $category): string
    {
        $prefix = $category->code_prefix ?: strtoupper(Str::substr($category->slug, 0, 3));

        $existing = RegimenPlan::query()
            ->where('team_id', $category->team_id)
            ->where('code', 'like', $prefix . '-%')
            ->pluck('code');

        $max = 100;
        foreach ($existing as $code) {
            if (preg_match('/-(\d+)$/', (string) $code, $m)) {
                $max = max($max, (int) $m[1]);
            }
        }

        return $prefix . '-' . ($max + 10);
    }

    protected function uniqueSlug(int $teamId, string $base): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $i = 2;

        while (RegimenCategory::where('team_id', $teamId)->where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $i++;
        }

        return $candidate;
    }

    protected function nextSortOrder(int $teamId): int
    {
        return (int) RegimenCategory::where('team_id', $teamId)->max('sort_order') + 10;
    }
}
