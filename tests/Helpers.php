<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

/** Crée les rôles de référence puis un membre (2FA confirmée par défaut). */
function member(string $role = 'contributeur', bool $twoFactor = true, array $attrs = []): User
{
    if (! Role::query()->exists()) {
        (new RolesAndPermissionsSeeder)->run();
    }
    $factory = User::factory()->role($role);
    if ($twoFactor) {
        $factory = $factory->withTwoFactor();
    }

    return $factory->create($attrs);
}

function seedRoles(): void
{
    if (! Role::query()->exists()) {
        (new RolesAndPermissionsSeeder)->run();
    }
}

function role(string $slug): Role
{
    seedRoles();

    return Role::query()->where('slug', $slug)->firstOrFail();
}

/** Simule une confirmation récente du mot de passe (actions sensibles). */
function confirmedPassword(): array
{
    return ['auth.password_confirmed_at' => time()];
}
