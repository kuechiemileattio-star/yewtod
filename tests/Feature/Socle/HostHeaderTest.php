<?php

use App\Models\Content;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Middleware\TrustHosts;
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
