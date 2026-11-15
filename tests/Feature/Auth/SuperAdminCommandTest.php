<?php

use App\Models\User;

// Cahier § 12.4 : création interactive du premier Super Admin.

it('crée le premier Super Admin', function () {
    $this->artisan('yewtod:create-super-admin')
        ->expectsQuestion('Nom affiché', 'Yann Admin')
        ->expectsQuestion('Adresse email', 'admin@exemple.test')
        ->expectsQuestion('Mot de passe (12 caractères min., majuscule, minuscule, chiffre, symbole)', 'Ciel-Orange-Velo-93?')
        ->expectsQuestion('Confirmez le mot de passe', 'Ciel-Orange-Velo-93?')
        ->assertSuccessful();
    expect(User::sole()->isSuperAdmin())->toBeTrue();
});

it('refuse s\'il existe déjà un Super Admin', function () {
    member('super-admin');
    $this->artisan('yewtod:create-super-admin')->assertFailed();
});

it('refuse un mot de passe faible', function () {
    $this->artisan('yewtod:create-super-admin')
        ->expectsQuestion('Nom affiché', 'Yann')
        ->expectsQuestion('Adresse email', 'admin@exemple.test')
        ->expectsQuestion('Mot de passe (12 caractères min., majuscule, minuscule, chiffre, symbole)', 'motdepasse')
        ->expectsQuestion('Confirmez le mot de passe', 'motdepasse')
        ->assertFailed();
    expect(User::count())->toBe(0);
});
