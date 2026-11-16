<?php

use App\Services\BookService;
use App\Services\ContentWorkflow;
use Illuminate\Support\Facades\Artisan;
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
