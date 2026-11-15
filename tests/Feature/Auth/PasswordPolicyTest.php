<?php

use App\Models\User;
use App\Services\PasswordPolicy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

// Cahier § 8.2 : une règle = au moins un test.

function passes(string $password, ?User $user = null, string $name = 'Jeanne Martin', string $email = 'jeanne.martin@exemple.test'): bool
{
    return Validator::make(['password' => $password, 'password_confirmation' => $password], ['password' => PasswordPolicy::rules($user, $name, $email)])->passes();
}

it('accepte un mot de passe conforme', fn () => expect(passes('Ciel-Orange-Velo-93?'))->toBeTrue());
it('exige 12 caractères minimum', fn () => expect(passes('Ab1!xyzwvut'))->toBeFalse());
it('refuse plus de 128 caractères', fn () => expect(passes('Ab1!'.str_repeat('xk', 63)))->toBeFalse());
it('exige une minuscule', fn () => expect(passes('CIEL-ORANGE-VELO-93?'))->toBeFalse());
it('exige une majuscule', fn () => expect(passes('ciel-orange-velo-93?'))->toBeFalse());
it('exige un chiffre', fn () => expect(passes('Ciel-Orange-Velo-xx?'))->toBeFalse());
it('exige un caractère spécial', fn () => expect(passes('CielOrangeVelo93x'))->toBeFalse());
it('refuse le nom de la personne', fn () => expect(passes('Martin-Orange-93?'))->toBeFalse());
it('refuse la partie locale de l\'email', fn () => expect(passes('Xjeanne.martin9!'))->toBeFalse());
it('refuse « yewtod »', fn () => expect(passes('Super-Yewtod-2026!'))->toBeFalse());
it('refuse plus de 3 caractères identiques de suite', fn () => expect(passes('Ciel-Oraaaange-93?'))->toBeFalse());
it('refuse les suites évidentes', function (string $pwd) {
    expect(passes($pwd))->toBeFalse();
})->with(['Ciel-123456-Velo!', 'Ciel-Azerty-Velo9!', 'Ciel-Abcdef-Velo9!']);
it('exige la confirmation', function () {
    expect(Validator::make(['password' => 'Ciel-Orange-Velo-93?', 'password_confirmation' => 'autre'], ['password' => PasswordPolicy::rules()])->passes())->toBeFalse();
});

it('refuse un mot de passe présent dans une fuite connue (HIBP)', function () {
    config(['services.hibp.enabled' => true]);
    $sha = strtoupper(sha1('Ciel-Orange-Velo-93?'));
    Http::fake(['api.pwnedpasswords.com/*' => Http::response(substr($sha, 5).":42\r\n")]);
    expect(passes('Ciel-Orange-Velo-93?'))->toBeFalse();
    Http::assertSent(fn ($r) => str_ends_with($r->url(), '/range/'.substr($sha, 0, 5)));
});

it('refuse les 5 derniers mots de passe', function () {
    $user = member();
    $history = ['Premier-Ciel-81?', 'Deuxieme-Mer-82?', 'Troisieme-Lac-83?'];
    foreach ($history as $p) {
        PasswordPolicy::apply($user->fresh(), $p);
    }
    expect(passes('Premier-Ciel-81?', $user->fresh(), 'Zz', 'zz@exemple.test'))->toBeFalse()
        ->and(passes('Troisieme-Lac-83?', $user->fresh(), 'Zz', 'zz@exemple.test'))->toBeFalse()
        ->and(passes('Nouveau-Bois-84?', $user->fresh(), 'Zz', 'zz@exemple.test'))->toBeTrue();
});

it('ne garde que 5 anciens mots de passe en historique', function () {
    $user = member();
    foreach (range(1, 8) as $i) {
        PasswordPolicy::apply($user->fresh(), "Historique-Mot-{$i}x?");
    }
    expect($user->passwordHistories()->count())->toBe(5);
});

it('stocke les mots de passe en Argon2id', function () {
    $user = member();
    PasswordPolicy::apply($user, 'Ciel-Orange-Velo-93?');
    expect($user->fresh()->password)->toStartWith('$argon2id$');
});
