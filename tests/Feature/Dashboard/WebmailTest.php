<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\Webmail;
use Illuminate\Support\Facades\DB;

// Décision D72 : lien vers le webmail de l'équipe, réservé à la permission access_webmail.

const WEBMAIL = 'https://mail.yewtod.com/roundcube/';

beforeEach(fn () => config(['yewtod.webmail_url' => WEBMAIL]));

it('affiche le lien Webmail aux administrateurs, dans un nouvel onglet', function () {
    $this->actingAs(member('administrateur'))->get('/dashboard')->assertOk()
        ->assertSee(route('dashboard.webmail'), false)
        ->assertSee('target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('(nouvel onglet)');
});

it('masque le lien aux rôles qui n\'ont pas la permission', function () {
    foreach (['editeur', 'contributeur', 'moderateur-collaborations'] as $role) {
        $this->actingAs(member($role))->get('/dashboard')->assertOk()->assertDontSee(route('dashboard.webmail'), false);
    }
});

it('refuse la redirection côté serveur sans la permission (403)', function () {
    $this->actingAs(member('editeur'))->get('/dashboard/webmail')->assertForbidden();
});

it('exige la double authentification comme le reste du dashboard', function () {
    $this->actingAs(member('administrateur', false))->get('/dashboard/webmail')
        ->assertRedirect('/dashboard/securite/double-authentification');
});

it('refuse un visiteur non connecté', function () {
    $this->get('/dashboard/webmail')->assertRedirect(route('login'));
});

it('redirige vers le webmail et trace l\'ouverture dans le journal d\'audit', function () {
    $admin = member('administrateur');
    $this->actingAs($admin)->get('/dashboard/webmail')->assertRedirect(WEBMAIL);
    expect(DB::table('activity_log')->where('event', 'webmail.opened')->where('causer_id', $admin->id)->exists())->toBeTrue();
});

it('peut être accordée à un autre rôle depuis le dashboard', function () {
    $editor = member('editeur');
    $role = Role::query()->where('slug', 'editeur')->first();
    $role->permissions()->attach(Permission::query()->where('key', 'access_webmail')->value('id'));
    $this->actingAs($editor->fresh())->get('/dashboard/webmail')->assertRedirect(WEBMAIL);
});

it('se désactive proprement sans URL valide (lien masqué, 404)', function (string $url) {
    config(['yewtod.webmail_url' => $url]);
    expect(Webmail::enabled())->toBeFalse();
    $admin = member('administrateur');
    $this->actingAs($admin)->get('/dashboard')->assertOk()->assertDontSee(route('dashboard.webmail'), false);
    $this->actingAs($admin)->get('/dashboard/webmail')->assertNotFound();
})->with([
    'vide' => '',
    'http non chiffré' => 'http://mail.yewtod.com/roundcube/',
    'javascript' => 'javascript:alert(1)',
    'identifiants dans l\'URL' => 'https://user:pass@mail.yewtod.com/',
    'pas une URL' => 'mail.yewtod.com',
]);

it('ajoute la permission sur une base existante et la donne aux administrateurs seulement', function () {
    seedRoles();
    Permission::query()->where('key', 'access_webmail')->delete(); // état d'une base créée avant D72
    $migration = require database_path('migrations/2026_11_20_100100_add_access_webmail_permission.php');
    $migration->up();
    $migration->up(); // idempotente

    $keys = fn (string $slug) => Role::query()->where('slug', $slug)->first()->permissions()->pluck('key')->all();
    expect(Permission::query()->where('key', 'access_webmail')->count())->toBe(1)
        ->and($keys('administrateur'))->toContain('access_webmail')
        ->and($keys('editeur'))->not->toContain('access_webmail')
        ->and($keys('contributeur'))->not->toContain('access_webmail');
});
