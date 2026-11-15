<?php

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Activitylog\Models\Activity;

// Cahier § 8.3 : connexion, message générique, limitation, 2FA.

it('affiche la page de connexion', function () {
    $this->get('/login')->assertOk()->assertSee('Connexion');
});

it('refuse un mauvais mot de passe avec un message générique', function () {
    $user = member();
    $this->post('/login', ['email' => $user->email, 'password' => 'Mauvais-Mot-2-Passe!'])
        ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
    $this->assertGuest();
});

it('donne le même message pour un email inconnu (pas d\'énumération)', function () {
    seedRoles();
    $this->post('/login', ['email' => 'inconnu@exemple.test', 'password' => 'Mauvais-Mot-2-Passe!'])
        ->assertSessionHasErrors(['email' => 'Identifiants incorrects.']);
});

it('demande le code TOTP après le mot de passe quand la 2FA est active', function () {
    $user = member();
    $this->post('/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD])
        ->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

it('accepte un code TOTP valide puis redirige vers la page demandée', function () {
    $user = member();
    $this->get('/dashboard')->assertRedirect('/login');
    $this->post('/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);
    $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

it('refuse un code TOTP déjà utilisé (anti-rejeu)', function () {
    $user = member();
    $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->post('/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);
    $this->post('/two-factor-challenge', ['code' => $code]);
    $this->post('/logout');
    $this->post('/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);
    $this->post('/two-factor-challenge', ['code' => $code]);
    $this->assertGuest();
});

it('accepte un code de secours une seule fois', function () {
    $user = member();
    $recovery = $user->recoveryCodes()[0];
    $this->post('/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);
    $this->post('/two-factor-challenge', ['recovery_code' => $recovery]);
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->recoveryCodes())->not->toContain($recovery);
});

it('limite à 5 tentatives par minute et par couple email + IP', function () {
    $user = member();
    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => 'Mauvais-Mot-2-Passe!']);
    }
    $this->post('/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD])->assertStatus(429);
    $this->assertGuest();
});

it('limite à 5 essais par minute l\'écran du code 2FA', function () {
    $user = member();
    $this->post('/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);
    foreach (range(1, 5) as $i) {
        $this->post('/two-factor-challenge', ['code' => '000000']);
    }
    $this->post('/two-factor-challenge', ['code' => '000000'])->assertStatus(429);
});

it('régénère l\'identifiant de session à la connexion', function () {
    $user = member(twoFactor: false);
    $this->startSession();
    $before = session()->getId();
    $this->post('/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);
    expect(session()->getId())->not->toBe($before);
});

it('bloque un membre suspendu avec le message « accès suspendu »', function () {
    $user = member(attrs: []);
    $user->forceFill(['status' => UserStatus::Suspended])->save();
    $this->actingAs($user)->get('/dashboard')->assertForbidden()->assertSee('Accès suspendu');
    $this->assertGuest();
});

it('trace les connexions réussies et échouées sans mot de passe', function () {
    $user = member(twoFactor: false);
    $this->post('/login', ['email' => $user->email, 'password' => 'Mauvais-Mot-2-Passe!']);
    $this->post('/login', ['email' => $user->email, 'password' => UserFactory::PASSWORD]);
    $logs = Activity::all();
    expect($logs->pluck('event'))->toContain('auth.failed', 'auth.login');
    expect($logs->toJson())->not->toContain('Mauvais-Mot-2-Passe')->not->toContain(UserFactory::PASSWORD);
});

afterEach(fn () => RateLimiter::clear('x'));
