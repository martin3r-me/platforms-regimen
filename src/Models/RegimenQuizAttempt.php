<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class RegimenQuizAttempt extends Model
{
    protected $table = 'regimen_quiz_attempts';

    protected $fillable = [
        'uuid',
        'user_id',
        'regimen_quiz_id',
        'team_id',
        'score_pct',
        'passed',
        'answers',
    ];

    protected $casts = [
        'score_pct' => 'integer',
        'passed' => 'boolean',
        'answers' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = (string) UuidV7::generate();
            }
        });
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(RegimenQuiz::class, 'regimen_quiz_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
