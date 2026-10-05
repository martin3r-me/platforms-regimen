<?php

namespace Platform\Regimen\Services;

use Illuminate\Support\Str;
use Platform\Regimen\Models\RegimenTopic;

class RegimenTopicService
{
    public function listForTeam(int $teamId)
    {
        return RegimenTopic::query()
            ->where('team_id', $teamId)
            ->withCount('publishedSessions')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function create(int $teamId, int $userId, array $attributes): RegimenTopic
    {
        $slug = $attributes['slug'] ?? Str::slug($attributes['title']);

        return RegimenTopic::create([
            'team_id' => $teamId,
            'created_by_user_id' => $userId,
            'slug' => $this->uniqueSlug($teamId, $slug),
            'title' => $attributes['title'],
            'description' => $attributes['description'] ?? null,
            'icon' => $attributes['icon'] ?? null,
            'color' => $attributes['color'] ?? null,
            'sort_order' => $attributes['sort_order'] ?? $this->nextSortOrder($teamId),
        ]);
    }

    public function update(RegimenTopic $topic, array $attributes): RegimenTopic
    {
        $topic->fill(array_intersect_key($attributes, array_flip([
            'title', 'description', 'icon', 'color', 'sort_order',
        ])));
        $topic->save();

        return $topic;
    }

    public function delete(RegimenTopic $topic): void
    {
        $topic->delete();
    }

    protected function uniqueSlug(int $teamId, string $base): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $i = 2;

        while (RegimenTopic::where('team_id', $teamId)->where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $i++;
        }

        return $candidate;
    }

    protected function nextSortOrder(int $teamId): int
    {
        return (int) RegimenTopic::where('team_id', $teamId)->max('sort_order') + 10;
    }
}
