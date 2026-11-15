<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Journal d'audit (cahier § 8.5) au-dessus de spatie/laravel-activitylog.
 *
 * Règle absolue : jamais de secret dans le journal. Les clés sensibles sont
 * retirées quoi qu'il arrive (défense en profondeur si un appelant se trompe).
 */
class Audit
{
    private const FORBIDDEN_KEYS = [
        'password', 'password_confirmation', 'current_password', 'token', 'token_hash',
        'two_factor_secret', 'two_factor_recovery_codes', 'remember_token', 'code', 'recovery_code', 'secret',
    ];

    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $event, ?Model $subject = null, array $properties = [], ?Model $causer = null): void
    {
        $properties = self::scrub($properties);
        $properties['ip_hash'] = self::hashIp(request()->ip());

        $log = activity('audit')->event($event)->withProperties($properties);

        if ($subject !== null) {
            $log->performedOn($subject);
        }

        $causer ??= auth()->user();
        if ($causer instanceof Model) {
            $log->causedBy($causer);
        } else {
            $log->causedByAnonymous();
        }

        $log->log($event);
    }

    /**
     * IP hachée avec APP_KEY (cahier § 8.6 « IP hachées ») : permet de
     * corréler des événements sans conserver l'adresse en clair.
     */
    public static function hashIp(?string $ip): ?string
    {
        if ($ip === null) {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private static function scrub(array $properties): array
    {
        $flat = Arr::dot($properties);
        foreach (array_keys($flat) as $key) {
            $last = strtolower((string) last(explode('.', $key)));
            if (in_array($last, self::FORBIDDEN_KEYS, true)) {
                Arr::forget($properties, $key);
            }
        }

        return $properties;
    }
}
