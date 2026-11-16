<?php

namespace App\Providers;

use App\Livewire\Dashboard\Collaborations;
use App\Livewire\Dashboard\Moderation;
use App\Models\Book;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        // Hors production, toute erreur de modèle doit sauter aux yeux :
        // attribut inconnu, chargement paresseux (N+1), affectation de masse
        // silencieusement ignorée (cahier § 8.6).
        Model::shouldBeStrict(! $this->app->isProduction());

        // En production, migrate:fresh, migrate:reset, db:wipe… sont bloqués.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        $this->pinApplicationUrl();
        $this->useMigrationConnection();
        $this->publicRateLimits();
        $this->registerPublicSources();
    }

    /**
     * Les livres alimentent le sitemap et la recherche globale sans que le
     * code des publications ne dépende d'eux (sources étiquetées).
     */
    private function registerPublicSources(): void
    {
        $this->app->bind('sources.books.sitemap', fn () => fn () => Book::query()->published()->latestFirst()->limit(5000)->get(['slug', 'updated_at'])
            ->map(fn (Book $b) => ['loc' => $b->publicUrl(), 'lastmod' => $b->updated_at->toAtomString()])->all());
        $this->app->tag(['sources.books.sitemap'], 'sitemap.sources');
        $this->app->bind('sources.books.search', fn () => fn (string $like) => Book::query()->published()
            ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('author', 'like', $like))->limit(5)->get(['slug', 'title', 'author'])
            ->map(fn (Book $b) => ['title' => $b->title, 'kind' => 'Livre · '.$b->author, 'url' => $b->publicUrl()]));
        $this->app->tag(['sources.books.search'], 'search.sources');

        // Pastilles du menu (cahier § 10 P1) : avis en attente, nouvelles collaborations.
        $this->app->bind('badges.public-writes', fn () => fn (User $user) => [
            'dashboard.moderation' => Moderation::pendingFor($user),
            'dashboard.collaborations' => Collaborations::newCount($user),
        ]);
        $this->app->tag(['badges.public-writes'], 'dashboard.badges');
    }

    /**
     * Protection contre l'empoisonnement par l'en-tête Host : toutes les URL
     * absolues (liens de réinitialisation et d'invitation dans les emails,
     * sitemap, RSS, balises canoniques, Open Graph) sont construites à partir
     * d'APP_URL, jamais de l'en-tête Host envoyé par le client. Sans cela, une
     * demande « mot de passe oublié » forgée avec `Host: attaquant.example`
     * enverrait à la victime un lien qui livre son jeton à l'attaquant, et un
     * sitemap empoisonné resterait en cache une heure.
     * Les hôtes inconnus sont en plus refusés par TrustHosts (bootstrap/app.php).
     */
    private function pinApplicationUrl(): void
    {
        $root = rtrim((string) config('app.url'), '/');
        if ($root === '') {
            return;
        }
        URL::forceRootUrl($root);
        if (str_starts_with($root, 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Limitation par IP des URL publiques coûteuses (cahier § 8.4, § 8.6
     * anti-DoS). Les écritures publiques (avis, newsletter, collaborations)
     * ont leurs propres limites (lot 07).
     */
    private function publicRateLimits(): void
    {
        RateLimiter::for('public-download', fn (Request $r) => Limit::perMinute(30)->by('dl|'.$r->ip()));
        RateLimiter::for('public-search', fn (Request $r) => Limit::perMinute(60)->by('search|'.$r->ip()));
        // Formulaires publics : 5 envois par minute et 30 par jour, par formulaire et par IP.
        RateLimiter::for('public-form', fn (Request $r) => [
            Limit::perMinute(5)->by('form|'.$r->route()?->getName().'|'.$r->ip()),
            Limit::perDay(30)->by('form-day|'.$r->route()?->getName().'|'.$r->ip()),
        ]);
        RateLimiter::for('public-feed', fn (Request $r) => Limit::perMinute(60)->by('feed|'.$r->ip()));
    }

    /**
     * Les commandes de structure utilisent la connexion « migrate » (compte
     * yewtod_migrate en local). Si aucun compte de migration n'est configuré
     * (mutualisé LWS), la connexion « migrate » pointe sur l'utilisateur
     * unique : le comportement reste correct.
     */
    private function useMigrationConnection(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            $structural = ['migrate', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback', 'migrate:install', 'migrate:status', 'schema:dump'];

            if (in_array($event->command, $structural, true) && ! $this->app->runningUnitTests()) {
                config(['database.default' => 'migrate']);
                DB::purge('migrate');
            }
        });
    }
}
