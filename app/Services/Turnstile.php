<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloudflare Turnstile vérifié CÔTÉ SERVEUR (cahier § 8.4).
 *
 * Sans clé secrète (décision 8) : la vérification est désactivée et les
 * autres protections (champ piège, limitation par IP, pré-modération)
 * continuent de s'appliquer. Avec une clé, une panne de Cloudflare fait
 * ÉCHOUER la vérification (on préfère refuser un envoi plutôt que d'ouvrir
 * la porte aux robots).
 */
class Turnstile
{
    public const FIELD = 'cf-turnstile-response';

    public static function enabled(): bool
    {
        return filled(config('services.turnstile.secret_key')) && filled(config('services.turnstile.site_key'));
    }

    public function verify(mixed $token, ?string $ip): bool
    {
        if (! self::enabled()) {
            return true;
        }
        if (! is_string($token) || $token === '' || strlen($token) > 2048) {
            return false;
        }
        try {
            $response = Http::asForm()->timeout(8)->post((string) config('services.turnstile.endpoint'), [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Turnstile injoignable', ['error' => $e->getMessage()]);

            return false;
        }

        return $response->ok() && $response->json('success') === true;
    }
}
