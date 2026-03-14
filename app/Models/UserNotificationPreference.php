<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    /** @use HasFactory<\Database\Factories\UserNotificationPreferenceFactory> */
    use HasFactory;

    public const CATEGORY_ISSUE_UPDATES = 'issue_updates';

    public const CATEGORY_THRESHOLD_ALERTS = 'threshold_alerts';

    public const CATEGORY_SECURITY = 'security';

    /**
     * Categories that cannot be disabled.
     *
     * @var list<string>
     */
    public const LOCKED_CATEGORIES = [self::CATEGORY_SECURITY];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category',
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
            'enabled' => 'boolean',
        ];
    }

    /**
     * Get the user that owns this preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
