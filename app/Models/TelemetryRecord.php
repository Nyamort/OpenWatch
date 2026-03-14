<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class TelemetryRecord extends Model
{
    public const CREATED_AT = null;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'organization_id',
        'project_id',
        'environment_id',
        'record_type',
        'trace_id',
        'group_key',
        'execution_id',
        'payload',
        'recorded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * TelemetryRecord is append-only; updates are not permitted.
     *
     * @throws RuntimeException
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new RuntimeException('TelemetryRecord is append-only.');
        }

        return parent::save($options);
    }

    /**
     * TelemetryRecord is append-only; updates are not permitted.
     *
     * @throws RuntimeException
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new RuntimeException('TelemetryRecord is append-only.');
    }

    /**
     * TelemetryRecord is append-only; deletions are not permitted.
     *
     * @throws RuntimeException
     */
    public function delete(): ?bool
    {
        throw new RuntimeException('TelemetryRecord is append-only.');
    }
}
