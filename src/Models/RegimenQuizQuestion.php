<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class RegimenQuizQuestion extends Model
{
    protected $table = 'regimen_quiz_questions';

    public const TYPE_SINGLE = 'single';
    public const TYPE_MULTIPLE = 'multiple';

    protected $fillable = [
        'uuid',
        'regimen_quiz_id',
        'type',
        'prompt',
        'explanation',
        'sort_order',
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

    public function options(): HasMany
    {
        return $this->hasMany(RegimenQuizOption::class, 'regimen_quiz_question_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function isMultiple(): bool
    {
        return $this->type === self::TYPE_MULTIPLE;
    }

    /**
     * @return array<int, int>
     */
    public function correctOptionIds(): array
    {
        return $this->options
            ->where('is_correct', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
