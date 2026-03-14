<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectToken extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectTokenFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'environment_id',
        'token_hash',
        'status',
        'grace_until',
        'rotated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'grace_until' => 'datetime',
            'rotated_at' => 'datetime',
        ];
    }

    /**
     * Get the environment that owns the token.
     */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    /**
     * Scope a query to only include valid tokens (active, or deprecated within grace window).
     */
    public function scopeValid(Builder $query): void
    {
        $query->where(function (Builder $q): void {
            $q->where('status', 'active')
                ->orWhere(function (Builder $inner): void {
                    $inner->where('status', 'deprecated')
                        ->where('grace_until', '>', now());
                });
        });
    }
}
