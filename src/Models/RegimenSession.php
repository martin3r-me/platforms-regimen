<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Symfony\Component\Uid\UuidV7;

class RegimenSession extends Model
{
    protected $table = 'regimen_sessions';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

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
        'status',
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

    /**
     * Optionaler Concept-Check dieser Lektion. Existiert er, ist er das Tor
     * zum Abschluss der Lektion (ersetzt das manuelle "Als erledigt markieren").
     */
    public function quiz(): HasOne
    {
        return $this->hasOne(RegimenQuiz::class, 'regimen_session_id');
    }
}
