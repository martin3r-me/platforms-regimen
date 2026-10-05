<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class RegimenPlan extends Model
{
    protected $table = 'regimen_plans';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const LEVEL_BEGINNER = 'beginner';
    public const LEVEL_INTERMEDIATE = 'intermediate';
    public const LEVEL_ADVANCED = 'advanced';

    public const LEVELS = [
        self::LEVEL_BEGINNER => 'Einsteiger',
        self::LEVEL_INTERMEDIATE => 'Fortgeschritten',
        self::LEVEL_ADVANCED => 'Profi',
    ];

    // Plan-Typ (Sportart-Gattung). Erweiterbar — erste relevante Typen:
    public const TYPE_RUNNING = 'running';     // Laufplan
    public const TYPE_EQUIPMENT = 'equipment'; // Fitnessgeräte-Plan

    public const TYPES = [
        self::TYPE_RUNNING => 'Laufplan',
        self::TYPE_EQUIPMENT => 'Fitnessgeräte-Plan',
    ];

    public const DEFAULT_TYPE = self::TYPE_RUNNING;

    /** Fallback-Cover-Farbe (Platform-Primary), falls weder Kategorie noch Override gesetzt sind. */
    public const DEFAULT_COLOR = '#4F46E5';

    protected $fillable = [
        'uuid',
        'team_id',
        'regimen_category_id',
        'created_by_user_id',
        'slug',
        'title',
        'code',
        'level',
        'description',
        'icon',
        'color',
        'target_audience',
        'type',
        'status',
        'public',
        'sort_order',
    ];

    protected $casts = [
        'public' => 'boolean',
    ];

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(RegimenCategory::class, 'regimen_category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(RegimenPlanEnrollment::class, 'regimen_plan_id');
    }

    public function enrollmentFor(int $userId): ?RegimenPlanEnrollment
    {
        return $this->enrollments()->where('user_id', $userId)->first();
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(RegimenCertificate::class, 'regimen_plan_id');
    }

    public function certificateFor(int $userId): ?RegimenCertificate
    {
        return $this->certificates()->where('user_id', $userId)->first();
    }

    /**
     * Cover-Farbe: Plan-Override > Kategorie-Farbe > Default.
     * Treibt den typografischen Kurs-Cover-Verlauf und das Kategorie-Label.
     */
    public function coverColor(): string
    {
        if ($this->color && str_starts_with($this->color, '#')) {
            return $this->color;
        }

        return $this->relationLoaded('category')
            ? ($this->category?->color ?: self::DEFAULT_COLOR)
            : ($this->category()->first()?->color ?: self::DEFAULT_COLOR);
    }

    public function levelLabel(): ?string
    {
        return $this->level ? (self::LEVELS[$this->level] ?? null) : null;
    }

    public function typeLabel(): ?string
    {
        return $this->type ? (self::TYPES[$this->type] ?? null) : null;
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(
            RegimenSession::class,
            'regimen_plan_sessions',
            'regimen_plan_id',
            'regimen_session_id'
        )
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderBy('regimen_plan_sessions.sort_order');
    }

    /**
     * Nur veröffentlichte Lektionen — die für Lernende sichtbare, zählende Menge.
     * Entwürfe eines Kurses (noch nicht geschrieben) bleiben außen vor, damit
     * Fortschritt & Zertifikat gegen die fertigen Inhalte rechnen.
     */
    public function publishedSessions(): BelongsToMany
    {
        return $this->sessions()->where('regimen_sessions.status', RegimenSession::STATUS_PUBLISHED);
    }

    public function progressFor(int $userId): array
    {
        $sessions = $this->publishedSessions()->get(['regimen_sessions.id']);
        $total = $sessions->count();

        if ($total === 0) {
            return ['total' => 0, 'completed' => 0, 'pct' => 0];
        }

        $completed = RegimenSessionProgress::query()
            ->where('user_id', $userId)
            ->whereIn('regimen_session_id', $sessions->pluck('id'))
            ->where('status', RegimenSessionProgress::STATUS_COMPLETED)
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'pct' => (int) round($completed / $total * 100),
        ];
    }
}
