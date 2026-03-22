<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'active_organization_id',
        'active_project_id',
        'active_environment_id',
        'name',
        'email',
        'password',
        'timezone',
        'locale',
        'display_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active_organization_id' => 'integer',
            'active_project_id' => 'integer',
            'active_environment_id' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'display_preferences' => 'array',
        ];
    }

    /**
     * Get the active organization for the user.
     */
    public function activeOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'active_organization_id');
    }

    /**
     * Get the active project for the user.
     */
    public function activeProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'active_project_id');
    }

    /**
     * Get the active environment for the user.
     */
    public function activeEnvironment(): BelongsTo
    {
        return $this->belongsTo(Environment::class, 'active_environment_id');
    }

    /**
     * Get organization memberships for the user.
     */
    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    /**
     * Get invitations created by this user.
     */
    public function sentOrganizationInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'invited_by_user_id');
    }

    /**
     * Get invitations accepted by this user.
     */
    public function acceptedOrganizationInvitations(): HasMany
    {
        return $this->hasMany(OrganizationInvitation::class, 'accepted_by_user_id');
    }

    /**
     * Get organizations attached to the user.
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members')->withTimestamps();
    }

    /**
     * Get notification preferences for the user.
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(UserNotificationPreference::class);
    }
}
