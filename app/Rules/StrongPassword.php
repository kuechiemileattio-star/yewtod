<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Règles de composition « maison » de la politique de mot de passe (cahier § 8.2)
 * qui ne sont pas couvertes par Illuminate\Validation\Rules\Password :
 * informations personnelles, motifs faibles, historique des 5 derniers.
 *
 * Longueur, casse, chiffres, symboles et fuites connues (HIBP) sont gérés
 * par PasswordPolicy::rules() avec la règle Password native.
 */
class StrongPassword implements ValidationRule
{
    /** Suites évidentes (clavier et alphabet), comparées sans casse. */
    private const SEQUENCES = [
        '0123456789', '9876543210', 'abcdefghijklmnopqrstuvwxyz', 'zyxwvutsrqponmlkjihgfedcba',
        'azertyuiop', 'qsdfghjklm', 'wxcvbn', 'qwertyuiop', 'asdfghjkl', 'zxcvbnm',
    ];

    public function __construct(
        private readonly ?string $name = null,
        private readonly ?string $email = null,
        private readonly ?User $user = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $lower = Str::lower(Str::ascii($value));

        foreach ($this->personalTokens() as $token) {
            if (str_contains($lower, $token)) {
                $fail('Le mot de passe ne doit contenir ni votre nom, ni votre adresse email, ni « yewtod ».');

                return;
            }
        }

        // Plus de 3 caractères identiques de suite (« aaaa », « 1111 »).
        if (preg_match('/(.)\1{3,}/u', $value) === 1) {
            $fail('Le mot de passe ne doit pas contenir plus de 3 caractères identiques à la suite.');

            return;
        }

        if ($this->containsSequence($lower)) {
            $fail('Le mot de passe ne doit pas contenir de suite évidente (123456, azerty, abcdef…).');

            return;
        }

        if ($this->user !== null && $this->reusesRecentPassword($value)) {
            $fail('Le mot de passe doit être différent de vos 5 derniers mots de passe.');
        }
    }

    /** @return list<string> */
    private function personalTokens(): array
    {
        $tokens = ['yewtod'];
        $name = $this->name ?? $this->user?->name;
        $email = $this->email ?? $this->user?->email;

        foreach (preg_split('/[\s\-_.\']+/', Str::lower(Str::ascii((string) $name))) ?: [] as $part) {
            if (mb_strlen($part) >= 3) {
                $tokens[] = $part;
            }
        }

        $local = Str::lower(Str::ascii(Str::before((string) $email, '@')));
        if (mb_strlen($local) >= 3) {
            $tokens[] = $local;
        }

        return array_values(array_unique($tokens));
    }

    /** Détecte une sous-suite de 6 caractères consécutifs d'une suite connue. */
    private function containsSequence(string $lower): bool
    {
        foreach (self::SEQUENCES as $sequence) {
            $window = min(6, strlen($sequence));
            for ($i = 0; $i + $window <= strlen($sequence); $i++) {
                if (str_contains($lower, substr($sequence, $i, $window))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function reusesRecentPassword(string $value): bool
    {
        if ($this->user === null) {
            return false;
        }

        if ($this->user->password !== '' && Hash::check($value, $this->user->password)) {
            return true;
        }

        return $this->user->passwordHistories()
            ->latest('created_at')->latest('id')->limit(5)->pluck('password')
            ->contains(fn (string $hash) => Hash::check($value, $hash));
    }
}
