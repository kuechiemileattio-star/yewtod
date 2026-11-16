<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Préproduction à accès protégé (cahier § 12.3) : si PREPROD_USER et
 * PREPROD_PASSWORD_HASH sont définis, tout le site exige une authentification
 * HTTP Basic et n'est jamais indexé. Le mot de passe n'est stocké que haché
 * (password_hash) ; la comparaison du nom est en temps constant.
 * En production, ces variables restent vides : le middleware ne fait rien.
 */
class ProtectPreproduction
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = (string) config('yewtod.preprod.user');
        $hash = (string) config('yewtod.preprod.password_hash');
        if ($user === '' || $hash === '' || $request->is('up')) {
            return $next($request);
        }

        $givenUser = (string) $request->getUser();
        $givenPassword = (string) $request->getPassword();
        if (! hash_equals($user, $givenUser) || ! password_verify($givenPassword, $hash)) {
            return response('Accès réservé à la préproduction.', 401, [
                'WWW-Authenticate' => 'Basic realm="Préproduction Yewtod", charset="UTF-8"',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
