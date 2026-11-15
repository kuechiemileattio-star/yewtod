<?php

namespace App\Models;

use App\Enums\Permission as PermissionKey;
use App\Enums\UserStatus;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * Membre du dashboard : authentification + profil.
 *
 * Affectation de masse (cahier § 8.6, R3) : seuls nom, bio et préférence de
 * thème sont « fillable ». Email, rôle, statut, invité par, mot de passe et
 * colonnes 2FA ne s'écrivent que par des services dédiés (forceFill).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property int $role_id
 * @property UserStatus $status
 * @property int|null $invited_by
 * @property string|null $bio
 * @property string|null $avatar_path
 * @property string $theme_preference
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property CarbonImmutable|null $password_changed_at
 * @property CarbonImmutable|null $last_login_at
 * @property CarbonImmutable $created_at
 * @property-read Role $role
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = ['name', 'bio', 'theme_preference'];

    /** Valeurs par défaut identiques à la base (mode strict : attributs toujours définis). */
    protected $attributes = [
        'bio' => null, 'avatar_path' => null, 'invited_by' => null, 'theme_preference' => 'system',
        'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
        'password_changed_at' => null, 'last_login_at' => null, 'remember_token' => null,
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    /** Le rôle et ses permissions sont nécessaires à presque chaque requête. */
    protected $with = ['role.permissions'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => UserStatus::class,
            'two_factor_confirmed_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /** @return HasMany<PasswordHistory, $this> */
    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role->isSuperAdmin();
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasPermission(PermissionKey|string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
