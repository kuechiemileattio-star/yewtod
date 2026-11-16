<?php

namespace App\Services;

use App\Contracts\Trackable;
use App\Models\ContentEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;

/**
 * Comptage des vues et téléchargements (cahier § 5.3, § 8.4 anti-spam).
 *
 * Trois garde-fous, dans cet ordre :
 *  1. l'élément doit être publiquement visible (un brouillon n'est jamais compté) ;
 *  2. une seule fois par session et par élément (mémorisé en session) ;
 *  3. un plafond par IP et par minute, pour limiter le gonflement par un robot
 *     qui jetterait ses cookies à chaque requête.
 * Les compteurs restent indicatifs : sans identifier le visiteur, on ne peut
 * pas tout empêcher (le cahier l'admet).
 */
class EventCounter
{
    /** Événements retenus par IP et par minute, toutes publications confondues. */
    public const PER_IP_PER_MINUTE = 30;

    public function record(Model&Trackable $item, string $kind): bool
    {
        if (! in_array($kind, ContentEvent::KINDS, true)) {
            throw new InvalidArgumentException('Type d\'événement inconnu.');
        }
        if (! $item->isPubliclyVisible()) {
            return false;
        }

        $sessionKey = 'counted.'.$kind.'.'.$item->getMorphClass().'.'.$item->getKey();
        if (request()->hasSession() && request()->session()->has($sessionKey)) {
            return false;
        }

        // IP hachée (HMAC) : la clé du limiteur ne conserve pas l'adresse en clair.
        $limiterKey = 'counter:'.Audit::hashIp(request()->ip());
        if (RateLimiter::tooManyAttempts($limiterKey, self::PER_IP_PER_MINUTE)) {
            return false;
        }
        RateLimiter::hit($limiterKey, 60);

        DB::transaction(function () use ($item, $kind) {
            $event = new ContentEvent;
            $event->forceFill([
                'eventable_type' => $item->getMorphClass(),
                'eventable_id' => $item->getKey(),
                'kind' => $kind,
            ])->save();
            // Incrément atomique en SQL (pas de lecture-écriture concurrente).
            $item->newQuery()->whereKey($item->getKey())->increment($kind === 'view' ? 'views_count' : 'downloads_count');
        });

        if (request()->hasSession()) {
            request()->session()->put($sessionKey, true);
        }

        return true;
    }
}
