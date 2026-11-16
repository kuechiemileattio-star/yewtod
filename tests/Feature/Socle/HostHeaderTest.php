<?php

use App\Http\Middleware\PreventRequestForgery;
use App\Models\Content;
use App\Models\SocialLink;
use App\Services\SiteSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Notification;

/*
 * Empoisonnement par l'en-tête Host (trouvé pendant l'audit dynamique, D66) :
 * aucune URL absolue ne doit reprendre un Host fourni par le client.
 */

it('construit le sitemap avec APP_URL même si l\'en-tête Host est forgé', function () {
    Content::factory()->create();
    $xml = $this->withHeader('Host', 'attaquant.example')->get('/sitemap.xml')->getContent();
    expect($xml)->not->toContain('attaquant.example')->toContain('<loc>'.rtrim(config('app.url'), '/'));
});

it('envoie un lien de réinitialisation pointant vers APP_URL, jamais vers le Host forgé', function () {
    Notification::fake();
    $user = member('editeur');
    $this->withHeader('Host', 'attaquant.example')->post(route('password.email'), ['email' => $user->email]);
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user) {
        $url = $n->toMail($user)->actionUrl;
        expect($url)->toStartWith(rtrim(config('app.url'), '/'))->not->toContain('attaquant');

        return true;
    });
});

it('n\'accepte que l\'hôte d\'APP_URL (TrustHosts, actif hors local)', function () {
    // Le middleware est volontairement inactif pendant les tests unitaires de Laravel :
    // on vérifie ici la liste d'hôtes qu'il appliquera en production.
    $patterns = (new TrustHosts(app()))->hosts();
    $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    $match = fn (string $h) => collect($patterns)->contains(fn ($p) => preg_match('{'.$p.'}i', $h) === 1);
    expect($match($host))->toBeTrue()
        ->and($match('attaquant.example'))->toBeFalse()
        ->and($match($host.'.attaquant.example'))->toBeFalse()
        ->and($match('attaquant.example.'.$host))->toBeFalse();
});

it('ne révèle pas la technologie et ne pose pas de cookie XSRF-TOKEN lisible par JavaScript', function () {
    $res = $this->get('/');
    expect($res->headers->has('X-Powered-By'))->toBeFalse()
        ->and(collect($res->headers->getCookies())->map->getName()->all())->not->toContain('XSRF-TOKEN')
        ->and($res->headers->get('Cross-Origin-Resource-Policy'))->toBe('same-site')
        ->and($res->headers->get('X-Permitted-Cross-Domain-Policies'))->toBe('none');
});

it('vérifie toujours le jeton CSRF sans le cookie XSRF-TOKEN', function () {
    // Le contournement des tests unitaires de Laravel est désactivé le temps de la vérification.
    $mw = new class(app(), app('encrypter')) extends PreventRequestForgery
    {
        protected function runningUnitTests()
        {
            return false;
        }
    };
    $request = Request::create('/newsletter', 'POST', ['email' => 'a@b.fr']);
    $request->setLaravelSession(app('session.store'));
    expect(fn () => $mw->handle($request, fn () => response('ok')))->toThrow(TokenMismatchException::class);
});

it('applique une CSP sans unsafe-inline, scripts ET styles (nonce uniquement)', function () {
    $csp = (string) $this->get('/')->headers->get('Content-Security-Policy');
    expect($csp)->not->toContain('unsafe-inline')->not->toContain('unsafe-eval')
        ->toMatch("/style-src 'self' 'nonce-[A-Za-z0-9]+'/")->toMatch("/script-src 'self' 'nonce-[A-Za-z0-9]+'/");
});

it('ne rend jamais un lien de réseau social non http(s), même présent en base', function () {
    (new SocialLink(['platform' => 'X', 'url' => 'javascript:alert(1)']))->forceFill(['position' => 0])->save();
    (new SocialLink(['platform' => 'LinkedIn', 'url' => 'https://linkedin.com/in/x']))->forceFill(['position' => 1])->save();
    app(SiteSettings::class)->flush();
    $this->get('/meet')->assertDontSee('javascript:alert', false)->assertSee('https://linkedin.com/in/x', false);
});
