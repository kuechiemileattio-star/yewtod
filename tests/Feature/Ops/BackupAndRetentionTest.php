<?php

use App\Enums\InvitationStatus;
use App\Models\Content;
use App\Models\ContentEvent;
use App\Models\Invitation;
use App\Services\Backup;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/*
 * Lot 09 — sauvegardes chiffrées et durées de conservation (cahier § 8.5, § 8.6).
 */

beforeEach(function () {
    $this->dir = storage_path('framework/testing/backups-'.uniqid());
    config(['services.backup.path' => $this->dir, 'services.backup.passphrase' => 'phrase-de-test-suffisamment-longue']);
});
afterEach(fn () => File::deleteDirectory($this->dir));

describe('sauvegardes chiffrées', function () {
    it('se désactive proprement sans phrase de passe', function () {
        config(['services.backup.passphrase' => '']);
        $this->artisan('yewtod:backup')->expectsOutputToContain('Sauvegarde désactivée')->assertFailed();
    });

    it('chiffre la base et les fichiers, puis les restitue à l\'identique', function () {
        Content::factory()->create(['title' => 'Titre à retrouver après restauration']);
        $file = app(Backup::class)->create();
        $raw = (string) file_get_contents($file);
        expect($raw)->toStartWith('YWTDBK1')->not->toContain('Titre à retrouver')->not->toContain('CREATE TABLE');

        $zip = $this->dir.'/out.zip';
        app(Backup::class)->decrypt($file, $zip);
        $archive = new ZipArchive;
        $archive->open($zip);
        $sql = (string) $archive->getFromName('base.sql');
        expect($sql)->toContain('CREATE TABLE `contents`')->toContain('Titre à retrouver après restauration')
            ->and(json_decode((string) $archive->getFromName('manifeste.json'), true)['tables'])->toHaveKey('users');
    });

    it('refuse une mauvaise phrase de passe ou un fichier altéré', function () {
        $file = app(Backup::class)->create();
        expect(fn () => app(Backup::class)->decrypt($file, $this->dir.'/a.zip', 'mauvaise-phrase-de-passe'))->toThrow(RuntimeException::class, 'phrase de passe incorrecte');
        $bytes = (string) file_get_contents($file);
        $bytes[strlen($bytes) - 20] = chr(ord($bytes[strlen($bytes) - 20]) ^ 1);
        file_put_contents($file, $bytes);
        expect(fn () => app(Backup::class)->decrypt($file, $this->dir.'/b.zip'))->toThrow(RuntimeException::class);
    });

    it('applique la rotation 7 jours / 4 semaines / 6 mois', function () {
        File::ensureDirectoryExists($this->dir);
        $start = now()->startOfDay();
        for ($d = 0; $d < 240; $d++) {
            File::put($this->dir.'/yewtod-'.$start->subDays($d)->format('Ymd').'-023000.ybk', 'x');
        }
        app(Backup::class)->rotate();
        $left = count(File::glob($this->dir.'/yewtod-*.ybk'));
        expect($left)->toBeGreaterThanOrEqual(7)->toBeLessThanOrEqual(17);
        expect(File::exists($this->dir.'/yewtod-'.$start->format('Ymd').'-023000.ybk'))->toBeTrue()
            ->and(File::exists($this->dir.'/yewtod-'.$start->subDays(239)->format('Ymd').'-023000.ybk'))->toBeFalse();
    });

    it('est planifiée chaque nuit, comme la purge et la bascule des programmés', function () {
        $commands = collect(app(Schedule::class)->events())->map(fn ($e) => $e->command)->implode(' ');
        expect($commands)->toContain('yewtod:backup')->toContain('yewtod:purge')->toContain('yewtod:publish-scheduled')->toContain('queue:work');
    });
});

describe('durées de conservation', function () {
    it('expire les invitations échues et supprime les invitations closes anciennes', function () {
        $admin = member('super-admin');
        $make = function (InvitationStatus $status, $expires, $updated) use ($admin) {
            $i = new Invitation;
            $i->forceFill(['email' => uniqid().'@x.fr', 'role_id' => role('editeur')->id, 'token_hash' => hash('sha256', uniqid()), 'status' => $status, 'invited_by' => $admin->id, 'expires_at' => $expires, 'sent_at' => now()]);
            $i->save();
            DB::table('invitations')->where('id', $i->id)->update(['updated_at' => $updated]);

            return $i;
        };
        $pendingOld = $make(InvitationStatus::Pending, now()->subDay(), now()->subDays(8));
        $closedOld = $make(InvitationStatus::Revoked, now()->subDays(100), now()->subDays(100));
        $this->artisan('yewtod:purge')->assertSuccessful();
        expect($pendingOld->fresh()->status)->toBe(InvitationStatus::Expired)->and($closedOld->fresh())->toBeNull();
    });

    it('purge le journal d\'audit et les statistiques au-delà de leur durée', function () {
        DB::table('activity_log')->insert(['log_name' => 'audit', 'description' => 'ancien', 'created_at' => now()->subMonths(13), 'updated_at' => now()]);
        $e = new ContentEvent;
        $e->forceFill(['eventable_type' => Content::class, 'eventable_id' => 1, 'kind' => 'view', 'created_at' => now()->subMonths(26)])->save();
        $this->artisan('yewtod:purge')->assertSuccessful();
        expect(DB::table('activity_log')->where('description', 'ancien')->exists())->toBeFalse()->and(ContentEvent::query()->count())->toBe(0);
    });
});
