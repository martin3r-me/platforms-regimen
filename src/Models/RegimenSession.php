<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class RegimenSession extends Model
{
    protected $table = 'regimen_sessions';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    // Art der Lauf-Einheit (treibt Match-Logik & Darstellung).
    public const KIND_EASY = 'easy';
    public const KIND_LONG = 'long';
    public const KIND_TEMPO = 'tempo';
    public const KIND_INTERVAL = 'interval';
    public const KIND_RECOVERY = 'recovery';
    public const KIND_REST = 'rest';
    public const KIND_RACE = 'race';

    public const KINDS = [
        self::KIND_EASY => 'Easy Run',
        self::KIND_LONG => 'Long Run',
        self::KIND_TEMPO => 'Tempo',
        self::KIND_INTERVAL => 'Intervalle',
        self::KIND_RECOVERY => 'Regeneration',
        self::KIND_REST => 'Ruhetag',
        self::KIND_RACE => 'Wettkampf',
    ];

    protected $fillable = [
        'uuid',
        'team_id',
        'regimen_topic_id',
        'created_by_user_id',
        'slug',
        'title',
        'summary',
        'content',
        'estimated_minutes',
        'kind',
        'target_distance_m',
        'target_duration_s',
        'target_pace_s_per_km',
        'intensity',
        'structure',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'structure' => 'array',
    ];

    public function kindLabel(): ?string
    {
        return $this->kind ? (self::KINDS[$this->kind] ?? null) : null;
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = (string) UuidV7::generate();
            }
        });
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(RegimenTopic::class, 'regimen_topic_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(
            RegimenPlan::class,
            'regimen_plan_sessions',
            'regimen_session_id',
            'regimen_plan_id'
        )->withPivot('sort_order')->withTimestamps();
    }

    public function progress(): HasMany
    {
        return $this->hasMany(RegimenSessionProgress::class, 'regimen_session_id');
    }

    public function progressFor(int $userId): ?RegimenSessionProgress
    {
        return $this->progress()->where('user_id', $userId)->first();
    }
}
