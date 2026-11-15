<?php

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
        $middleware->web(prepend: [SecurityHeaders::class]);

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
