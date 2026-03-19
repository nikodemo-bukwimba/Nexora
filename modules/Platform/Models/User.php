<?php

namespace Modules\Platform\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Platform\Traits\HasUlid;

class User extends PlatformModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable,
        Authorizable,
        CanResetPassword,
        HasApiTokens,
        HasFactory,
        HasUlid,
        Notifiable,
        SoftDeletes;

    protected $connection = 'platform';

    protected $fillable = [
        'username',
        'email',
        'password',
        'actor_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'          => 'datetime',
            'two_factor_confirmed_at'    => 'datetime',
            'last_login_at'              => 'datetime',
            'password'                   => 'hashed',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Actor::class);
    }

    public function socialLogins(): HasMany
    {
        return $this->hasMany(UserSocialLogin::class);
    }

    public function platformRoles(): HasMany
    {
        return $this->hasMany(UserPlatformRole::class);
    }

    public function tierAssignments(): HasMany
    {
        return $this->hasMany(UserTierAssignment::class);
    }

    public function orgMemberships(): HasMany
    {
        return $this->hasMany(OrgMembership::class);
    }

    // ── Helpers ───────────────────────────────────────────────

    public function activeTier(): ?UserTierAssignment
    {
        return $this->tierAssignments()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest('starts_at')
            ->first();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
