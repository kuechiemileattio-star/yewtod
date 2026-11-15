<?php

use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

// Cahier § 8.3 et bug n° 14 : mot de passe oublié.

it('répond pareil que l\'email existe ou non', function () {
    Notification::fake();
    $user = member();
    $a = $this->post('/mot-de-passe-oublie', ['email' => $user->email])->assertSessionHas('status', PasswordResetLinkController::MESSAGE);
    $b = $this->post('/mot-de-passe-oublie', ['email' => 'inconnu@exemple.test'])->assertSessionHas('status', PasswordResetLinkController::MESSAGE);
    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertCount(1);
});

it('stocke le jeton de réinitialisation haché', function () {
    Notification::fake();
    $user = member();
    $this->post('/mot-de-passe-oublie', ['email' => $user->email]);
    Notification::assertSentTo($user, ResetPassword::class, function ($n) {
        expect(DB::table('password_reset_tokens')->value('token'))->not->toBe($n->token)->toStartWith('$');

        return true;
    });
});

it('réinitialise avec un lien valide, à usage unique, sans connecter, et déconnecte les sessions', function () {
    Notification::fake();
    $user = member();
    DB::table('sessions')->insert(['id' => 'ancienne', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    $this->post('/mot-de-passe-oublie', ['email' => $user->email]);
    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
        $token = $n->token;

        return true;
    });

    $data = ['token' => $token, 'email' => $user->email, 'password' => 'Nouveau-Bois-84?', 'password_confirmation' => 'Nouveau-Bois-84?'];
    $this->post('/reinitialiser-mot-de-passe', $data)->assertRedirect('/login');
    $this->assertGuest();
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);

    $data['password'] = $data['password_confirmation'] = 'Encore-Autre-85?';
    $this->post('/reinitialiser-mot-de-passe', $data)->assertSessionHasErrors('email');
});

it('expire le lien après 60 minutes', function () {
    expect(config('auth.passwords.users.expire'))->toBe(60);
});

it('limite les demandes de lien', function () {
    seedRoles();
    foreach (range(1, 3) as $i) {
        $this->post('/mot-de-passe-oublie', ['email' => "x{$i}@exemple.test"]);
    }
    $this->post('/mot-de-passe-oublie', ['email' => 'y@exemple.test'])->assertStatus(429);
});
