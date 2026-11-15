<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /** Mot de passe de test conforme à la politique (jamais utilisé en ligne). */
    public const PASSWORD = 'Correct-Cheval-Pile-42!';

    protected static ?string $hash = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'bio' => null,
            'theme_preference' => 'system',
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (User $user) {
            $user->forceFill([
                'email' => $user->email ?? Str::lower(fake()->unique()->userName()).'@exemple.test',
                'password' => static::$hash ??= Hash::make(self::PASSWORD),
                'status' => $user->status ?? UserStatus::Active,
                'role_id' => $user->role_id ?? Role::query()->where('slug', 'contributeur')->value('id'),
                'password_changed_at' => now(),
            ]);
        });
    }

    public function role(string $slug): static
    {
        return $this->afterMaking(fn (User $u) => $u->forceFill(['role_id' => Role::query()->where('slug', $slug)->value('id')]));
    }

    public function superAdmin(): static
    {
        return $this->role(Role::SUPER_ADMIN);
    }

    public function status(UserStatus $status): static
    {
        return $this->afterMaking(fn (User $u) => $u->forceFill(['status' => $status]));
    }

    public function email(string $email): static
    {
        return $this->afterMaking(fn (User $u) => $u->forceFill(['email' => $email]));
    }

    /** Membre avec une 2FA déjà confirmée (secret de test). */
    public function withTwoFactor(): static
    {
        return $this->afterMaking(fn (User $u) => $u->forceFill([
            'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(collect(range(1, 8))->map(fn () => RecoveryCode::generate())->all())),
            'two_factor_confirmed_at' => now(),
        ]));
    }
}
