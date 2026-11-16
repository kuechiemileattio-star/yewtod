<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Protections communes aux 4 formulaires publics (cahier § 8.4) :
 * champ piège invisible (honeypot) + Turnstile. La limitation par IP est
 * appliquée par le middleware throttle de chaque route.
 */
class SpamGuard
{
    /** Nom volontairement banal : les robots remplissent tout champ « website ». */
    public const HONEYPOT = 'website';

    public function __construct(private readonly Turnstile $turnstile) {}

    /** @throws ValidationException */
    public function check(Request $request): void
    {
        $trap = $request->input(self::HONEYPOT);
        if ($trap !== null && $trap !== '') {
            // Message neutre : on n'apprend pas au robot quel champ l'a trahi.
            throw ValidationException::withMessages(['form' => 'Votre envoi n\'a pas pu être traité. Réessayez.']);
        }
        if (! $this->turnstile->verify($request->input(Turnstile::FIELD), $request->ip())) {
            throw ValidationException::withMessages(['form' => 'La vérification anti-robot a échoué. Réessayez.']);
        }
    }
}
