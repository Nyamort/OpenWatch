<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class OrganizationAuditEvent extends Model
{
    /**
     * Disable automatic timestamp management (append-only, only created_at).
     */
    public $timestamps = false;

    /**
     * The created_at timestamp column name.
     */
    const CREATED_AT = 'created_at';

    /**
     * No updated_at column (append-only).
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'actor_id',
        'event_type',
        'target_type',
        'target_id',
        'metadata',
        'ip',
        'user_agent',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Override save to enforce append-only semantics.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws RuntimeException
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new RuntimeException('OrganizationAuditEvent records are append-only and cannot be updated.');
        }

        $this->created_at = now();

        return parent::save($options);
    }

    /**
     * Get the organization this event belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
