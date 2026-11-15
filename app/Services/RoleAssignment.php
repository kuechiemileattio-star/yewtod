<?php

namespace App\Services;

use App\Enums\Permission as PermissionKey;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Source unique de vérité pour les règles d'attribution R1–R8 (cahier § 6).
 * Les Policies (UserPolicy, RolePolicy, InvitationPolicy) ne font que
 * déléguer ici : une règle n'est écrite qu'une fois.
 */
class RoleAssignment
{
    /**
     * R5 — Non-escalade : on n'attribue un rôle que si l'on possède toutes
     * ses permissions ; le rôle système ne s'attribue que par un Super Admin.
     */
    public function canAssign(User $actor, Role $role): bool
    {
        if (! $actor->isActive()) {
            return false;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        if ($role->is_system || $role->isSuperAdmin()) {
            return false;
        }

        $mine = $actor->role->permissionKeys();

        return array_diff($role->permissionKeys(), $mine) === [];
    }

    /**
     * R1 + R6 — Hiérarchie : on ne modifie, suspend ou supprime un membre que
     * si l'on pourrait lui attribuer son rôle actuel, et jamais soi-même.
     */
    public function canManage(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        return $this->canAssign($actor, $target->role);
    }

    /** R8 — On n'accorde à un rôle qu'une permission que l'on possède. */
    public function canGrant(User $actor, PermissionKey|string $permission): bool
    {
        return $actor->isActive() && $actor->hasPermission($permission);
    }

    /** R8 — Seul un Super Admin modifie un rôle système et ses permissions. */
    public function canEditRole(User $actor, Role $role): bool
    {
        if (! $actor->isActive()) {
            return false;
        }

        if ($role->is_system) {
            return $actor->isSuperAdmin();
        }

        return $actor->hasPermission(PermissionKey::ManageUsers) && $this->canAssign($actor, $role);
    }

    /**
     * Rôles que l'acteur a le droit d'attribuer (sélecteurs de l'interface).
     *
     * @return Collection<int, Role>
     */
    public function assignableRoles(User $actor): Collection
    {
        return Role::query()->with('permissions')->orderBy('name')->get()
            ->filter(fn (Role $role) => $this->canAssign($actor, $role))->values();
    }
}
