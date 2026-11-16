<?php

use App\Services\Backup;
use App\Services\BookService;
use App\Services\ContentWorkflow;
use App\Services\Retention;
use App\Support\Format;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

/*
 * Tâches planifiées. Sur le mutualisé LWS, le cron appelle
 * `php artisan schedule:run` chaque minute (décision 12).
 */

Artisan::command('yewtod:publish-scheduled', function () {
    $count = ContentWorkflow::publishDue();
    $books = BookService::publishDue();
    $this->info("{$count} contenu(s) et {$books} livre(s) programmé(s) publié(s).");
})->purpose('Publie les contenus programmés dont la date est passée');

Schedule::command('yewtod:publish-scheduled')->everyMinute()->withoutOverlapping();

// Pas de worker permanent : la file « database » est vidée à chaque minute.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=1')->everyMinute()->withoutOverlapping();

// Purges (cahier § 8.5, § 8.6, § 13.1) : collaborations > 24 mois, inscriptions non
// confirmées > 7 jours, invitations closes, journal d'audit et statistiques anciens.
Artisan::command('yewtod:purge', function () {
    foreach (Retention::run() as $what => $count) {
        $this->line(str_pad(str_replace('_', ' ', $what), 26).$count);
    }
})->purpose('Applique les durées de conservation (RGPD)');

Schedule::command('yewtod:purge')->dailyAt('03:15')->withoutOverlapping();

// Sauvegardes chiffrées quotidiennes (cahier § 8.6). Désactivées proprement sans BACKUP_PASSPHRASE.
Artisan::command('yewtod:backup', function (Backup $backup) {
    if (! Backup::enabled()) {
        $this->warn('Sauvegarde désactivée : renseignez BACKUP_PASSPHRASE (16 caractères minimum) dans .env.');

        return 1;
    }
    $file = $backup->create();
    $this->info('Sauvegarde chiffrée créée : '.basename($file).' ('.Format::fileSize((int) filesize($file)).').');

    return 0;
})->purpose('Crée une sauvegarde chiffrée (base + fichiers) et applique la rotation');

Artisan::command('yewtod:backup:restore {fichier : Chemin du .ybk} {--dossier= : Dossier de destination} {--importer : Importe aussi base.sql dans la base configurée} {--force : Ne demande pas de confirmation (scripts de restauration)}', function (Backup $backup) {
    $dir = $this->option('dossier') ?: storage_path('app/restauration-'.now()->format('Ymd-His'));
    File::ensureDirectoryExists($dir, 0700);
    $zipPath = $dir.'/archive.zip';
    $passphrase = $this->secret('Phrase de passe de la sauvegarde (Entrée = BACKUP_PASSPHRASE du .env)') ?: null;
    $backup->decrypt((string) $this->argument('fichier'), $zipPath, $passphrase);
    $zip = new ZipArchive;
    $zip->open($zipPath);
    $zip->extractTo($dir);
    $zip->close();
    @unlink($zipPath);
    $this->info('Sauvegarde déchiffrée dans '.$dir.' (base.sql, fichiers/, manifeste.json).');

    if ($this->option('importer')) {
        $force = (bool) $this->option('force');
        if (app()->isProduction() && ! $this->confirm('PRODUCTION : la base actuelle sera ÉCRASÉE. Continuer ?')) {
            return 1;
        }
        if (! $force && ! $this->confirm('Importer base.sql dans la base « '.DB::connection()->getDatabaseName().' » (les tables existantes seront remplacées) ?')) {
            return 1;
        }
        $this->info($backup->importSql($dir.'/base.sql').' instruction(s) exécutée(s). Recopiez ensuite fichiers/private et fichiers/public dans storage/app/.');
    }

    return 0;
})->purpose('Déchiffre une sauvegarde et, sur demande, réimporte la base');

Schedule::command('yewtod:backup')->dailyAt('02:30')->withoutOverlapping();
