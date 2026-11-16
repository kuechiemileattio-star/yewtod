<?php

use App\Http\Middleware\EnsureActiveMember;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\ProtectPreproduction;
use App\Http\Middleware\RequirePermission;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // En-têtes de sécurité sur toutes les réponses web.
        $middleware->web(prepend: [SecurityHeaders::class, ProtectPreproduction::class]);

        // Le cookie de thème est écrit par le navigateur : il n'est pas chiffré
        // (valeur fermée light/dark/system, revalidée côté serveur).
        $middleware->encryptCookies(except: ['theme']);

        // Hôtes acceptés : celui d'APP_URL (et ses sous-domaines). Toute requête
        // portant un autre en-tête Host est rejetée hors environnement local.
        $middleware->trustHosts(at: fn () => array_filter([
            '^'.preg_quote((string) parse_url((string) config('app.url'), PHP_URL_HOST)).'$',
        ]), subdomains: false);

        $middleware->alias([
            'member' => EnsureActiveMember::class,
            '2fa' => EnsureTwoFactorEnabled::class,
            'permission' => RequirePermission::class,
        ]);

        // Après connexion, retour à la page demandée (cahier § 5.7.6).
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        // Proxys de confiance : AUCUN par défaut. Faire confiance à « * »
        // permettrait de falsifier l'IP (X-Forwarded-For) et donc de
        // contourner la limitation par IP. À renseigner seulement si LWS
        // place un proxy identifié devant PHP (TRUSTED_PROXIES=ip1,ip2).
        $proxies = array_filter(explode(',', (string) env('TRUSTED_PROXIES', '')));
        if ($proxies !== []) {
            $middleware->trustProxies(at: $proxies, headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
