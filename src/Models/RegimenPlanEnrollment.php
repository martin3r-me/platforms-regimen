<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class RegimenPlanEnrollment extends Model
{
    protected $table = 'regimen_plan_enrollments';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'uuid',
        'user_id',
        'regimen_plan_id',
        'team_id',
        'status',
        'enrolled_at',
        'completed_at',
        'last_session_id',
        'last_activity_at',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (!$model->uuid) {
                $model->uuid = (string) UuidV7::generate();
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(RegimenPlan::class, 'regimen_plan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function lastSession(): BelongsTo
    {
        return $this->belongsTo(RegimenSession::class, 'last_session_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
