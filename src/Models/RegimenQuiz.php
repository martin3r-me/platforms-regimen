<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class RegimenQuiz extends Model
{
    protected $table = 'regimen_quizzes';

    public const DEFAULT_PASS_PCT = 70;

    protected $fillable = [
        'uuid',
        'team_id',
        'regimen_session_id',
        'title',
        'pass_pct',
        'shuffle_questions',
    ];

    protected $casts = [
        'pass_pct' => 'integer',
        'shuffle_questions' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = (string) UuidV7::generate();
            }
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RegimenSession::class, 'regimen_session_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(RegimenQuizQuestion::class, 'regimen_quiz_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(RegimenQuizAttempt::class, 'regimen_quiz_id');
    }

    public function passThreshold(): int
    {
        return $this->pass_pct ?: self::DEFAULT_PASS_PCT;
    }

    public function latestAttemptFor(int $userId): ?RegimenQuizAttempt
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->latest('id')
            ->first();
    }

    public function hasPassed(int $userId): bool
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->where('passed', true)
            ->exists();
    }
}
