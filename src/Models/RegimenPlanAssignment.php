<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

/**
 * Die Delegations-Regel: "Kurs X ist Ziel Y zugewiesen (Pflicht/Empfehlung, von–bis)".
 * Das konkrete Ziel wird über einen AudienceResolver zu Personen aufgelöst
 * (Fan-out in RegimenUserAssignment) — Regimen kennt die Ziel-Quelle nicht.
 */
class RegimenPlanAssignment extends Model
{
    protected $table = 'regimen_plan_assignments';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'uuid',
        'team_id',
        'regimen_plan_id',
        'assigned_by_user_id',
        'target_type',
        'target_id',
        'target_options',
        'is_mandatory',
        'starts_at',
        'due_at',
        'note',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'target_options' => 'array',
        'is_mandatory' => 'boolean',
        'starts_at' => 'date',
        'due_at' => 'date',
        'last_synced_at' => 'datetime',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'assigned_by_user_id');
    }

    public function userAssignments(): HasMany
    {
        return $this->hasMany(RegimenUserAssignment::class, 'regimen_plan_assignment_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
