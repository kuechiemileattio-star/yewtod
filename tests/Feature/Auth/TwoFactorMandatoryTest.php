<?php

use App\Notifications\EnrollmentCodeNotification;
use App\Notifications\SecurityNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;

// Cahier § 8.1 : 2FA obligatoire, configuration, codes de secours.

it('redirige vers la configuration 2FA un membre qui ne l\'a pas activée', function () {
    $user = member(twoFactor: false);
    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('security.two-factor'));
});

it('laisse entrer un membre avec la 2FA confirmée', function () {
    $this->actingAs(member())->get('/dashboard')->assertOk();
});

it('exige une preuve d\'accès à la boîte mail avant d\'enrôler un appareil', function () {
    $user = member(twoFactor: false);
    $this->actingAs($user)->withSession(confirmedPassword())
        ->post(route('security.two-factor.enable'))->assertSessionHasErrors('mail_code');
    expect($user->fresh()->two_factor_secret)->toBeNull();
});

it('enrôle la 2FA après vérification du code email et confirmation TOTP', function () {
    Notification::fake();
    $user = member(twoFactor: false);
    $this->actingAs($user)->post(route('security.two-factor.mail-code'));
    $code = null;
    Notification::assertSentTo($user, EnrollmentCodeNotification::class, function ($n) use (&$code, $user) {
        preg_match('/\*\*(\d{6})\*\*/', implode(' ', $n->toMail($user)->introLines), $m);
        $code = $m[1];

        return true;
    });
    $this->post(route('security.two-factor.mail-verify'), ['mail_code' => $code])->assertSessionHasNoErrors();
    $this->withSession(confirmedPassword())->post(route('security.two-factor.enable'));
    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()->and($user->two_factor_confirmed_at)->toBeNull();

    $otp = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->post(route('security.two-factor.confirm'), ['code' => $otp])->assertSessionHas('recovery_codes');
    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue()
        ->and($user->fresh()->recoveryCodes())->toHaveCount(8);
    Notification::assertSentTo($user, SecurityNotification::class, fn ($n) => $n->kind === SecurityNotification::TWO_FACTOR_ENABLED);
});

it('stocke le secret TOTP et les codes de secours chiffrés', function () {
    $user = member();
    $raw = DB::table('users')->where('id', $user->id)->first();
    expect($raw->two_factor_secret)->not->toMatch('/^[A-Z2-7]{16,}$/')
        ->and(decrypt($raw->two_factor_secret))->toMatch('/^[A-Z2-7]+$/')
        ->and($raw->two_factor_recovery_codes)->not->toContain($user->recoveryCodes()[0]);
});

it('exige une reconfirmation du mot de passe pour régénérer les codes', function () {
    $user = member();
    $this->actingAs($user)->post(route('security.two-factor.regenerate'))->assertRedirect(route('password.confirm'));
});

it('régénère les codes, invalide les anciens et notifie', function () {
    Notification::fake();
    $user = member();
    $old = $user->recoveryCodes();
    $this->actingAs($user)->withSession(confirmedPassword())->post(route('security.two-factor.regenerate'))
        ->assertSessionHas('recovery_codes');
    expect(array_intersect($old, $user->fresh()->recoveryCodes()))->toBe([]);
    Notification::assertSentTo($user, SecurityNotification::class, fn ($n) => $n->kind === SecurityNotification::RECOVERY_CODES_REGENERATED);
});

it('n\'affiche plus les codes de secours après la première fois', function () {
    $user = member();
    $this->actingAs($user)->get(route('security.two-factor'))->assertDontSee($user->recoveryCodes()[0]);
});

it('désactive la 2FA (avec mot de passe confirmé) et réimpose la configuration', function () {
    Notification::fake();
    $user = member();
    $this->actingAs($user)->withSession(confirmedPassword())->delete(route('security.two-factor.disable'));
    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
    $this->get('/dashboard')->assertRedirect(route('security.two-factor'));
    Notification::assertSentTo($user, SecurityNotification::class, fn ($n) => $n->kind === SecurityNotification::TWO_FACTOR_DISABLED);
});
