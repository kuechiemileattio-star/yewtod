<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 2FA obligatoire pour tout membre, sans exception (cahier § 8.1.1) :
 * tant qu'elle n'est pas confirmée, seule la page de configuration est accessible.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->hasTwoFactorEnabled()) {
            if ($request->expectsJson()) {
                abort(403, 'Double authentification requise.');
            }

            return redirect()->route('security.two-factor')
                ->with('warning', 'La double authentification est obligatoire pour accéder au tableau de bord.');
        }

        return $next($request);
    }
}
