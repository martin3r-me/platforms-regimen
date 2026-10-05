<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

/**
 * Eine geplante Einheit an einem konkreten Kalendertag im persönlichen Plan.
 * Trägt einen Snapshot der geplanten Ziele und (nach dem Matching) die Ist-Werte.
 */
class RegimenPlanEntry extends Model
{
    protected $table = 'regimen_plan_entries';

    public const STATUS_PLANNED = 'planned';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_MISSED = 'missed';

    protected $fillable = [
        'uuid',
        'regimen_plan_enrollment_id',
        'user_id',
        'team_id',
        'regimen_plan_id',
        'regimen_session_id',
        'scheduled_date',
        'week',
        'weekday',
        'sort_order',
        'title',
        'kind',
        'target_distance_m',
        'target_duration_s',
        'target_pace_s_per_km',
        'structure',
        'status',
        'completed_at',
        'source',
        'actual_distance_m',
        'actual_duration_s',
        'actual_pace_s_per_km',
        'regimen_activity_id',
        'matched_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_at' => 'datetime',
        'matched_at' => 'datetime',
        'structure' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = (string) UuidV7::generate();
            }
        });
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(RegimenPlanEnrollment::class, 'regimen_plan_enrollment_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(RegimenPlan::class, 'regimen_plan_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(RegimenSession::class, 'regimen_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /** Die gematchte Aktivität (harte FK liegt auf der Activity-Seite). */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(RegimenActivity::class, 'regimen_activity_id');
    }

    public function isRest(): bool
    {
        return $this->kind === RegimenSession::KIND_REST;
    }
}
