<?php

namespace Platform\Regimen\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Symfony\Component\Uid\UuidV7;

class RegimenCategory extends Model
{
    protected $table = 'regimen_categories';

    /** Fallback-Farbe, falls keine gesetzt ist (Platform-Primary = Indigo). */
    public const DEFAULT_COLOR = '#4F46E5';

    protected $fillable = [
        'uuid',
        'team_id',
        'created_by_user_id',
        'slug',
        'title',
        'description',
        'color',
        'code_prefix',
        'icon',
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class, 'team_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_user_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(RegimenPlan::class, 'regimen_category_id');
    }

    public function color(): string
    {
        return $this->color ?: self::DEFAULT_COLOR;
    }
}
