<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionKey;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seeder de RÉFÉRENCE (autorisé en production) : permissions et rôles par
 * défaut du cahier (§ 6). Idempotent : un rôle existant n'est pas modifié,
 * pour ne pas écraser les réglages faits depuis le dashboard.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionKey::cases() as $case) {
            $permission = Permission::query()->where('key', $case->value)->first() ?? new Permission;
            $permission->forceFill(['key' => $case->value, 'label' => $case->label(), 'description' => $case->description()])->save();
        }

        $all = array_map(fn (PermissionKey $p) => $p->value, PermissionKey::cases());

        $defaults = [
            ['Super Admin', Role::SUPER_ADMIN, 'Rôle système : toutes les permissions, gère les autres Super Admins.', true, $all],
            ['Administrateur', 'administrateur', 'Toutes les permissions, sans pouvoir toucher aux Super Admins (R5, R6, R8).', false, $all],
            ['Éditeur', 'editeur', 'Rédige, relit et publie les contenus et les livres.', false, ['manage_articles', 'publish_content', 'manage_books', 'manage_media']],
            ['Contributeur', 'contributeur', 'Rédige et soumet ses contenus en relecture ; ne publie pas.', false, ['manage_articles', 'manage_media']],
            ['Modérateur des collaborations', 'moderateur-collaborations', 'Traite les demandes de collaboration.', false, ['manage_collaborations']],
        ];

        foreach ($defaults as [$name, $slug, $description, $system, $permissions]) {
            if (Role::query()->where('slug', $slug)->exists()) {
                continue;
            }

            $role = new Role(['name' => $name, 'slug' => $slug, 'description' => $description]);
            $role->forceFill(['is_system' => $system])->save();
            $role->permissions()->sync(Permission::query()->whereIn('key', $permissions)->pluck('id'));
        }
    }
}
