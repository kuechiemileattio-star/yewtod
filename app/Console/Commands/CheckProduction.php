<?php

namespace App\Console\Commands;

use App\Jobs\SyncNewsletterWithBrevo;
use App\Services\Backup;
use App\Services\Turnstile;
use App\Support\Webmail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Vérification de la configuration avant/après une mise en ligne
 * (cahier § 8.4 « Secrets », § 12.3). Lancée par le script de déploiement.
 * Code de sortie 1 si une exigence bloquante n'est pas remplie.
 */
class CheckProduction extends Command
{
    protected $signature = 'yewtod:verifier-production';

    protected $description = 'Vérifie la configuration de production (debug, HTTPS, cookies, clés, dossiers)';

    public function handle(): int
    {
        $blocking = [
            'Extensions PHP requises'.$this->missingExtensions() => $this->missingExtensions() === '',
            'APP_ENV=production' => app()->isProduction(),
            'APP_DEBUG=false' => ! config('app.debug'),
            'APP_KEY définie' => filled(config('app.key')),
            'APP_URL en https' => str_starts_with((string) config('app.url'), 'https://'),
            'SESSION_SECURE_COOKIE=true' => (bool) config('session.secure'),
            'Base de données joignable' => $this->databaseReachable(),
            'storage/ inscriptible' => is_writable(storage_path('framework')) && is_writable(storage_path('logs')),
            'Lien public/storage présent' => is_link(public_path('storage')) || is_dir(public_path('storage')),
            'Assets compilés (public/build)' => is_file(public_path('build/manifest.json')),
            'File d\'attente « database »' => config('queue.default') === 'database',
            'Sauvegardes configurées (BACKUP_PASSPHRASE)' => Backup::enabled(),
            'Site ouvert au public (PREPROD_USER vide)' => ! app()->isProduction() || blank(config('yewtod.preprod.user')),
        ];
        $optional = [
            'Emails (SMTP)' => config('mail.default') !== 'log' && filled(config('mail.mailers.smtp.host')),
            'Newsletter Brevo (BREVO_API_KEY, BREVO_LIST_ID)' => SyncNewsletterWithBrevo::enabled(),
            'Anti-robots Turnstile (clés site et secrète)' => Turnstile::enabled(),
            'Webmail de l\'équipe (WEBMAIL_URL en https)' => Webmail::enabled(),
            'Mentions légales (LEGAL_PUBLISHER, LEGAL_DIRECTOR)' => filled(config('yewtod.legal.publisher')) && filled(config('yewtod.legal.director')),
        ];

        $failed = 0;
        foreach ($blocking as $label => $ok) {
            $this->line(($ok ? '<info>  OK  </info>' : '<error> ÉCHEC </error>').' '.$label);
            $failed += $ok ? 0 : 1;
        }
        $this->newLine();
        foreach ($optional as $label => $ok) {
            $this->line(($ok ? '<info>  OK  </info>' : '<comment> DÉSACTIVÉ </comment>').' '.$label.($ok ? '' : ' — la fonction se désactive proprement'));
        }
        $this->newLine();
        $this->line('Cron (tâche planifiée LWS) : '.$this->lastCronRun());
        $this->newLine();
        $failed > 0 ? $this->error("{$failed} exigence(s) bloquante(s) non remplie(s).") : $this->info('Configuration de production conforme.');

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** Extensions utilisées par le projet (images, sauvegardes chiffrées, base, archives…). */
    private function missingExtensions(): string
    {
        $missing = array_filter(
            ['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'gd', 'sodium', 'zip', 'curl', 'dom', 'xml'],
            fn (string $ext) => ! extension_loaded($ext),
        );

        return $missing === [] ? '' : ' — manquantes : '.implode(', ', $missing);
    }

    /** Dernier passage de `schedule:run`, noté par la tâche « yewtod:battement ». */
    private function lastCronRun(): string
    {
        try {
            $last = Cache::get('yewtod:cron:dernier-passage');
        } catch (\Throwable) {
            return 'inconnu (cache inaccessible)';
        }
        if (! is_string($last)) {
            return 'aucun passage enregistré : vérifiez la tâche cron du panneau LWS';
        }
        $minutes = (int) Carbon::parse($last)->diffInMinutes(now());

        return $minutes <= 10 ? "dernier passage il y a {$minutes} min (OK)" : "dernier passage il y a {$minutes} min : la tâche cron ne tourne plus ?";
    }

    private function databaseReachable(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
