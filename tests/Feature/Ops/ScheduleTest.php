<?php

use App\Enums\ContentStatus;
use App\Models\Content;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;

// Décision D73 : cron LWS Perso (toutes les 5 min, 240 s max, sans sous-processus).

function scheduledEvents(): array
{
    return app(Schedule::class)->events();
}

it('exécute toutes les tâches dans le processus du cron (aucun sous-processus, proc_open inutile)', function () {
    expect(scheduledEvents())->not->toBeEmpty();
    foreach (scheduledEvents() as $event) {
        expect($event)->toBeInstanceOf(CallbackEvent::class);
    }
});

it('place chaque heure fixe sur un multiple de 5 minutes (cron LWS toutes les 5 min)', function () {
    foreach (scheduledEvents() as $event) {
        $minute = explode(' ', $event->expression)[0];
        expect($minute === '*' || ((int) $minute) % 5 === 0)->toBeTrue("{$event->description} : minute {$minute}");
    }
});

it('nomme chaque tâche (requis pour éviter les chevauchements entre deux passages)', function () {
    $names = array_map(fn ($e) => $e->description, scheduledEvents());
    expect($names)->toContain('yewtod:battement', 'yewtod:publish-scheduled', 'yewtod:file-attente', 'yewtod:purge', 'yewtod:backup');
});

it('publie les contenus échus et note le passage du cron lors de schedule:run', function () {
    Cache::forget('yewtod:cron:dernier-passage');
    $c = Content::factory()->status(ContentStatus::Draft)->create();
    $c->forceFill(['status' => ContentStatus::Scheduled, 'scheduled_at' => now()->subMinute()])->save();

    $this->artisan('schedule:run')->assertSuccessful();

    expect($c->fresh()->status)->toBe(ContentStatus::Published)
        ->and(Cache::get('yewtod:cron:dernier-passage'))->toBeString();
});

it('affiche l\'état du cron dans la vérification de production', function () {
    Cache::forget('yewtod:cron:dernier-passage');
    $this->artisan('yewtod:verifier-production')->expectsOutputToContain('aucun passage enregistré');

    Cache::forever('yewtod:cron:dernier-passage', now()->subMinutes(3)->toIso8601String());
    $this->artisan('yewtod:verifier-production')->expectsOutputToContain('dernier passage il y a 3 min (OK)');

    Cache::forever('yewtod:cron:dernier-passage', now()->subHour()->toIso8601String());
    $this->artisan('yewtod:verifier-production')->expectsOutputToContain('la tâche cron ne tourne plus');
});
