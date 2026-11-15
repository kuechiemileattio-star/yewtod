<?php

namespace App\Services;

use App\Models\PasswordHistory;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Politique de mot de passe unique (cahier § 8.2), appliquée à l'onboarding,
 * au changement, à la réinitialisation et à la création du Super Admin.
 */
class PasswordPolicy
{
    public const HISTORY = 5;

    /**
     * @return list<string|Rule|ValidationRule|Password>
     */
    public static function rules(?User $user = null, ?string $name = null, ?string $email = null): array
    {
        $password = Password::min(12)->max(128)->letters()->mixedCase()->numbers()->symbols();

        if (config('services.hibp.enabled')) {
            // k-anonymat : seuls 5 caractères du SHA-1 sont envoyés à HIBP.
            $password = $password->uncompromised();
        }

        return ['required', 'string', 'confirmed', $password, new StrongPassword($name, $email, $user)];
    }

    /**
     * Enregistre un nouveau mot de passe : historique (5 derniers), date de
     * changement, invalidation du « se souvenir de moi ».
     */
    public static function apply(User $user, string $plain): void
    {
        DB::transaction(function () use ($user, $plain) {
            if ($user->exists && $user->password !== '') {
                $user->passwordHistories()->save(new PasswordHistory(['password' => $user->password]));
            }

            $user->forceFill([
                'password' => $plain,
                'password_changed_at' => now(),
                'remember_token' => null,
            ])->save();

            $keep = $user->passwordHistories()->latest('created_at')->latest('id')->limit(self::HISTORY)->pluck('id');
            $user->passwordHistories()->whereNotIn('id', $keep)->delete();
        });
    }
}
