<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class RegimenTopic extends Model
{
    protected $table = 'regimen_topics';

    protected $fillable = [
        'uuid',
        'team_id',
        'created_by_user_id',
        'slug',
        'title',
        'description',
        'icon',
        'color',
        'sort_order',
    ];

    /** Tailwind-Farbnamen → Hex (500er-Ton), damit color als Akzent nutzbar ist. */
    public const COLOR_HEX = [
        'red' => '#EF4444', 'orange' => '#F97316', 'amber' => '#F59E0B', 'yellow' => '#EAB308',
        'lime' => '#84CC16', 'green' => '#22C55E', 'emerald' => '#10B981', 'teal' => '#14B8A6',
        'cyan' => '#06B6D4', 'sky' => '#0EA5E9', 'blue' => '#3B82F6', 'indigo' => '#6366F1',
        'violet' => '#8B5CF6', 'purple' => '#A855F7', 'fuchsia' => '#D946EF', 'pink' => '#EC4899',
        'rose' => '#F43F5E', 'slate' => '#64748B', 'zinc' => '#71717A', 'gray' => '#6B7280',
    ];

    /** Liefert die Themenfarbe als Hex — akzeptiert Tailwind-Namen oder direkte Hex-Werte. */
    public function hexColor(): string
    {
        if ($this->color && str_starts_with($this->color, '#')) {
            return $this->color;
        }

        return self::COLOR_HEX[$this->color] ?? '#4F46E5';
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = (string) UuidV7::generate();
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(RegimenSession::class, 'regimen_topic_id')->orderBy('sort_order');
    }

    public function publishedSessions(): HasMany
    {
        return $this->sessions()->where('status', RegimenSession::STATUS_PUBLISHED);
    }
}
