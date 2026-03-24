<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Environment extends Model
{
    /** @use HasFactory<\Database\Factories\EnvironmentFactory> */
    use HasFactory, HasSlug;

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate()
            ->extraScope(fn (Builder $query) => $query->where('project_id', $this->project_id));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'slug',
        'color',
        'url',
        'status',
        'archived_at',
        'last_ingested_at',
        'health_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
            'last_ingested_at' => 'datetime',
        ];
    }

    /**
     * Get the project that owns the environment.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the tokens for the environment.
     */
    public function projectTokens(): HasMany
    {
        return $this->hasMany(ProjectToken::class);
    }

    /**
     * Scope a query to only include active (non-archived) environments.
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}
