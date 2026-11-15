<?php

namespace App\Services;

use App\Enums\Permission as PermissionKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Création et édition des rôles (cahier § 4.7, R8).
 */
class RoleManagement
{
    public function __construct(private readonly RoleAssignment $roles) {}

    /** @param list<string> $permissions */
    public function create(User $actor, string $name, string $description, array $permissions): Role
    {
        if (! $actor->hasPermission(PermissionKey::ManageUsers)) {
            throw new AuthorizationException('Vous n\'avez pas la permission de gérer les rôles.');
        }
        $this->assertGrantable($actor, $permissions);

        $slug = Str::slug($name);
        if ($slug === '' || Role::query()->where('slug', $slug)->orWhere('name', $name)->exists()) {
            throw ValidationException::withMessages(['name' => 'Un rôle porte déjà ce nom.']);
        }

        $role = DB::transaction(function () use ($name, $slug, $description, $permissions) {
            $role = Role::query()->create(['name' => $name, 'slug' => $slug, 'description' => $description]);
            $role->permissions()->sync(Permission::query()->whereIn('key', $permissions)->pluck('id'));

            return $role;
        });

        Audit::record('role.created', $role, ['permissions' => $permissions]);

        return $role;
    }

    /** @param list<string> $permissions */
    public function updatePermissions(User $actor, Role $role, array $permissions): void
    {
        if (! $this->roles->canEditRole($actor, $role)) {
            throw new AuthorizationException($role->is_system
                ? 'Seul un Super Admin peut modifier un rôle système.'
                : 'Vous ne pouvez pas modifier ce rôle.');
        }
        if ($role->isSuperAdmin()) {
            throw ValidationException::withMessages(['permissions' => 'Le rôle Super Admin possède toujours toutes les permissions.']);
        }

        $current = $role->permissionKeys();
        // R8 : ajouter OU retirer une permission exige de la posséder.
        $this->assertGrantable($actor, array_values(array_merge(array_diff($permissions, $current), array_diff($current, $permissions))));

        $role->permissions()->sync(Permission::query()->whereIn('key', $permissions)->pluck('id'));
        Audit::record('role.permissions_changed', $role, ['from' => $current, 'to' => array_values($permissions)]);
    }

    public function delete(User $actor, Role $role): void
    {
        if ($role->is_system) {
            throw new AuthorizationException('Un rôle système ne peut pas être supprimé.');
        }
        if (! $this->roles->canEditRole($actor, $role)) {
            throw new AuthorizationException('Vous ne pouvez pas supprimer ce rôle.');
        }
        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'Des membres portent encore ce rôle : changez-leur de rôle avant de le supprimer.']);
        }

        Audit::record('role.deleted', $role, ['name' => $role->name]);
        $role->delete();
    }

    /** @param list<string> $permissions */
    private function assertGrantable(User $actor, array $permissions): void
    {
        $valid = array_map(fn (PermissionKey $p) => $p->value, PermissionKey::cases());
        foreach ($permissions as $key) {
            if (! in_array($key, $valid, true)) {
                throw ValidationException::withMessages(['permissions' => 'Permission inconnue.']);
            }
            if (! $this->roles->canGrant($actor, $key)) {
                throw new AuthorizationException("Vous ne pouvez pas accorder ou retirer une permission que vous ne possédez pas ({$key}).");
            }
        }
    }
}
