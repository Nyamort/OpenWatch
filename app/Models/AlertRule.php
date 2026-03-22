<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AlertRule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'project_id',
        'environment_id',
        'name',
        'metric',
        'operator',
        'threshold',
        'window_minutes',
        'enabled',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'threshold' => 'float',
            'enabled' => 'boolean',
        ];
    }

    /**
     * Get the organization this alert rule belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the project this alert rule belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the environment this alert rule belongs to.
     */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    /**
     * Get the recipients for this alert rule.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(AlertRuleRecipient::class);
    }

    /**
     * Get the current state for this alert rule.
     */
    public function state(): HasOne
    {
        return $this->hasOne(AlertState::class);
    }

    /**
     * Get the evaluation history for this alert rule.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(AlertHistory::class);
    }

    /**
     * Scope a query to only include enabled alert rules.
     */
    public function scopeEnabled(Builder $query): void
    {
        $query->where('enabled', true);
    }
}
