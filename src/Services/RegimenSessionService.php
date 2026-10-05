<?php

namespace Platform\Regimen\Services;

use Illuminate\Support\Str;
use Platform\Regimen\Models\RegimenSession;
use Platform\Regimen\Models\RegimenTopic;

class RegimenSessionService
{
    public function create(RegimenTopic $topic, int $userId, array $attributes): RegimenSession
    {
        $slug = $attributes['slug'] ?? Str::slug($attributes['title']);

        return RegimenSession::create([
            'team_id' => $topic->team_id,
            'regimen_topic_id' => $topic->id,
            'created_by_user_id' => $userId,
            'slug' => $this->uniqueSlug($topic->id, $slug),
            'title' => $attributes['title'],
            'summary' => $attributes['summary'] ?? null,
            'content' => $attributes['content'] ?? null,
            'estimated_minutes' => $attributes['estimated_minutes'] ?? null,
            'status' => $attributes['status'] ?? RegimenSession::STATUS_DRAFT,
            'sort_order' => $attributes['sort_order'] ?? $this->nextSortOrder($topic->id),
        ]);
    }

    public function update(RegimenSession $session, array $attributes): RegimenSession
    {
        $session->fill(array_intersect_key($attributes, array_flip([
            'title', 'summary', 'content', 'estimated_minutes', 'status', 'sort_order',
        ])));
        $session->save();

        return $session;
    }

    public function publish(RegimenSession $session): RegimenSession
    {
        $session->status = RegimenSession::STATUS_PUBLISHED;
        $session->save();
        return $session;
    }

    public function archive(RegimenSession $session): RegimenSession
    {
        $session->status = RegimenSession::STATUS_ARCHIVED;
        $session->save();
        return $session;
    }

    public function delete(RegimenSession $session): void
    {
        $session->delete();
    }

    public function applyContentOp(RegimenSession $session, string $op, array $arguments): array
    {
        $content = (string) ($session->content ?? '');

        return match ($op) {
            'append' => $this->appendContent($content, (string) ($arguments['text'] ?? '')),
            'prepend' => $this->prependContent($content, (string) ($arguments['text'] ?? '')),
            'replace_exact' => $this->replaceExact($content, $arguments['old'] ?? null, $arguments['new'] ?? null),
            'upsert_heading' => $this->upsertHeading(
                $content,
                $arguments['heading'] ?? null,
                (string) ($arguments['text'] ?? ''),
                (int) ($arguments['level'] ?? 2),
                (string) ($arguments['mode'] ?? 'append'),
            ),
            default => ['success' => false, 'error' => 'Unbekannte op: ' . $op],
        };
    }

    protected function appendContent(string $content, string $text): array
    {
        $text = rtrim($text);
        if ($text === '') {
            return ['success' => false, 'error' => 'text ist erforderlich fuer append.'];
        }
        $content = rtrim($content);
        $out = $content === '' ? $text : ($content . "\n\n" . $text);
        return ['success' => true, 'content' => $out];
    }

    protected function prependContent(string $content, string $text): array
    {
        $text = rtrim($text);
        if ($text === '') {
            return ['success' => false, 'error' => 'text ist erforderlich fuer prepend.'];
        }
        $content = ltrim($content);
        $out = $content === '' ? $text : ($text . "\n\n" . $content);
        return ['success' => true, 'content' => $out];
    }

    protected function replaceExact(string $content, mixed $old, mixed $new): array
    {
        if ($old === null || $new === null) {
            return ['success' => false, 'error' => 'old und new sind erforderlich fuer replace_exact.'];
        }
        $old = (string) $old;
        $new = (string) $new;

        if ($old === '') {
            return ['success' => false, 'error' => 'old darf nicht leer sein.'];
        }

        $count = substr_count($content, $old);
        if ($count === 0) {
            return ['success' => false, 'error' => 'Der zu ersetzende Block (old) wurde nicht gefunden.'];
        }
        if ($count > 1) {
            return ['success' => false, 'error' => 'Der zu ersetzende Block (old) ist nicht eindeutig (kommt mehrfach vor).'];
        }

        return ['success' => true, 'content' => str_replace($old, $new, $content)];
    }

    protected function upsertHeading(string $content, mixed $heading, string $text, int $level, string $mode): array
    {
        if ($heading === null) {
            return ['success' => false, 'error' => 'heading ist erforderlich fuer upsert_heading.'];
        }
        $heading = trim((string) $heading);
        if ($heading === '') {
            return ['success' => false, 'error' => 'heading darf nicht leer sein.'];
        }
        $text = rtrim($text);
        if ($text === '') {
            return ['success' => false, 'error' => 'text ist erforderlich fuer upsert_heading.'];
        }
        if ($level < 1 || $level > 6) $level = 2;
        $mode = $mode === 'replace' ? 'replace' : 'append';

        $hashes = str_repeat('#', $level);
        $needle = $hashes . ' ' . $heading;

        $pos = strpos($content, $needle);
        if ($pos === false) {
            $out = rtrim($content);
            $block = $needle . "\n\n" . $text;
            $out = $out === '' ? $block : ($out . "\n\n" . $block);
            return ['success' => true, 'content' => $out];
        }

        $afterHeadingPos = $pos + strlen($needle);
        $rest = substr($content, $afterHeadingPos);

        $pattern = '/\n#{1,' . $level . '}\s+/';
        if (preg_match($pattern, $rest, $m, PREG_OFFSET_CAPTURE)) {
            $nextRel = $m[0][1];
            $section = substr($content, $afterHeadingPos, $nextRel);
            $tail = substr($content, $afterHeadingPos + $nextRel);
        } else {
            $section = substr($content, $afterHeadingPos);
            $tail = '';
        }

        if ($mode === 'replace') {
            $newSection = "\n\n" . $text . "\n";
        } else {
            $trimmed = rtrim($section);
            $newSection = ($trimmed === '' ? "\n\n" . $text . "\n" : $trimmed . "\n\n" . $text . "\n");
        }

        $out = substr($content, 0, $afterHeadingPos) . $newSection . ltrim($tail, "\n");
        return ['success' => true, 'content' => $out];
    }

    protected function uniqueSlug(int $topicId, string $base): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $i = 2;

        while (RegimenSession::where('regimen_topic_id', $topicId)->where('slug', $candidate)->exists()) {
            $candidate = $slug . '-' . $i++;
        }

        return $candidate;
    }

    protected function nextSortOrder(int $topicId): int
    {
        return (int) RegimenSession::where('regimen_topic_id', $topicId)->max('sort_order') + 10;
    }
}
