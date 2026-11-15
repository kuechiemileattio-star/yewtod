<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EnrollmentCodeNotification;
use Illuminate\Support\Facades\Cache;

/**
 * Preuve d'accès à la boîte mail avant d'enrôler un nouvel appareil 2FA.
 *
 * Pourquoi : si la 2FA d'un membre a été réinitialisée (ou n'a jamais été
 * configurée), quelqu'un qui connaîtrait seulement son mot de passe pourrait
 * sinon enregistrer SON propre téléphone. Le code email ferme cette porte.
 * Le code est stocké haché, valable 10 minutes, 5 essais maximum.
 */
class TwoFactorEnrollmentCode
{
    private const TTL_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function send(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->key($user), [
            'hash' => hash_hmac('sha256', $code, (string) config('app.key')),
            'attempts' => 0,
        ], now()->addMinutes(self::TTL_MINUTES));

        $user->notifyNow(new EnrollmentCodeNotification($code));
    }

    public function verify(User $user, string $code): bool
    {
        /** @var array{hash: string, attempts: int}|null $entry */
        $entry = Cache::get($this->key($user));
        if ($entry === null || $entry['attempts'] >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (hash_equals($entry['hash'], hash_hmac('sha256', trim($code), (string) config('app.key')))) {
            Cache::forget($this->key($user));

            return true;
        }

        $entry['attempts']++;
        Cache::put($this->key($user), $entry, now()->addMinutes(self::TTL_MINUTES));

        return false;
    }

    private function key(User $user): string
    {
        return '2fa-enroll:'.$user->id;
    }
}
