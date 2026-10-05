<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

/**
 * Eine absolvierte Aktivität (z.B. aus Garmin). Existiert eigenständig, auch ohne
 * Plan. Verweist nach dem Matching auf die erfüllte geplante Einheit.
 */
class RegimenActivity extends Model
{
    protected $table = 'regimen_activities';

    public const SOURCE_GARMIN = 'garmin';
    public const SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'uuid',
        'user_id',
        'team_id',
        'source',
        'external_id',
        'sport',
        'started_at',
        'distance_m',
        'duration_s',
        'avg_pace_s_per_km',
        'avg_hr',
        'raw',
        'regimen_plan_entry_id',
        'matched_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'matched_at' => 'datetime',
        'raw' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = (string) UuidV7::generate();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function planEntry(): BelongsTo
    {
        return $this->belongsTo(RegimenPlanEntry::class, 'regimen_plan_entry_id');
    }

    public function isMatched(): bool
    {
        return $this->regimen_plan_entry_id !== null;
    }
}
