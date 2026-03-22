<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
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
        'title',
        'fingerprint',
        'type',
        'status',
        'priority',
        'assignee_id',
        'occurrence_count',
        'first_seen_at',
        'last_seen_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurrence_count' => 'integer',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the organization this issue belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the project this issue belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the environment this issue belongs to.
     */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    /**
     * Get the assignee for this issue.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Get the sources for this issue.
     */
    public function sources(): HasMany
    {
        return $this->hasMany(IssueSource::class);
    }

    /**
     * Get the activities for this issue.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(IssueActivity::class);
    }

    /**
     * Get the comments for this issue.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(IssueComment::class);
    }

    /**
     * Scope a query to only open issues.
     */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', 'open');
    }

    /**
     * Scope a query to only resolved issues.
     */
    public function scopeResolved(Builder $query): void
    {
        $query->where('status', 'resolved');
    }

    /**
     * Scope a query to only ignored issues.
     */
    public function scopeIgnored(Builder $query): void
    {
        $query->where('status', 'ignored');
    }

    /**
     * Scope a query to issues belonging to an organization.
     */
    public function scopeForOrg(Builder $query, int $orgId): void
    {
        $query->where('organization_id', $orgId);
    }

    /**
     * Scope a query to issues belonging to a project and environment.
     */
    public function scopeForProject(Builder $query, int $projectId, int $envId): void
    {
        $query->where('project_id', $projectId)->where('environment_id', $envId);
    }
}
