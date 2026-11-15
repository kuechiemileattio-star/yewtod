<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

        $this->useMigrationConnection();
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
