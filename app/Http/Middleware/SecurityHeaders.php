<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité HTTP (cahier § 8.4).
 *
 * La CSP interdit tout script inline sans nonce : un XSS stocké qui
 * réussirait à injecter <script> ou un attribut onclick ne s'exécuterait pas.
 * Le nonce est régénéré à chaque requête et transmis à Vite, Livewire et au
 * petit script anti-flash du thème.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('Content-Security-Policy', $this->csp($nonce));
        $headers->set('X-Content-Type-Options', 'nosniff');
        // Double protection anti-clickjacking (anciens navigateurs + CSP).
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->remove('X-Powered-By');
        // L'en-tête X-Powered-By ajouté par PHP lui-même (expose_php) n'est pas dans
        // l'objet Response : on le retire au niveau de PHP. Révéler la version exacte
        // aide un attaquant à cibler une faille connue (constat nikto/nuclei).
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }
        // Nos ressources ne sont pas chargeables par un autre site (fuites par canal auxiliaire).
        $headers->set('Cross-Origin-Resource-Policy', 'same-site');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if (app()->isProduction() || $request->isSecure()) {
            // HSTS seulement en HTTPS : un an, sous-domaines inclus.
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function csp(string $nonce): string
    {
        $hot = Vite::isRunningHot() ? ' '.$this->viteDevOrigin() : '';

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://challenges.cloudflare.com https://www.youtube.com{$hot}",
            // Les styles inline sont tolérés (Alpine/Livewire manipulent
            // l'attribut style) : risque bien moindre que des scripts.
            "style-src 'self' 'nonce-{$nonce}'{$hot}",
            "img-src 'self' data: blob: https://i.ytimg.com https://img.youtube.com",
            "font-src 'self'{$hot}",
            "media-src 'self' https:",
            "connect-src 'self'".($hot !== '' ? $hot.' '.str_replace('http', 'ws', $this->viteDevOrigin()) : ''),
            'frame-src https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com https://challenges.cloudflare.com',
            // pdf.js lance son worker depuis nos propres assets.
            "worker-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        if (app()->isProduction()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }

    private function viteDevOrigin(): string
    {
        $hot = @file_get_contents(public_path('hot'));

        return $hot !== false ? rtrim(trim($hot), '/') : 'http://localhost:5173';
    }
}
