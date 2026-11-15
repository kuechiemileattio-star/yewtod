<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;
use App\Services\RoleAssignment;

/** R1, R3, R6 : délègue à RoleAssignment (source unique des règles). */
class UserPolicy
{
    public function __construct(private readonly RoleAssignment $roles) {}

    public function viewAny(User $actor): bool
    {
        return $actor->isActive() && $actor->hasPermission(Permission::ManageUsers);
    }

    /** Changer le rôle ou le statut, supprimer : R1 (pas soi-même) + R6 (hiérarchie). */
    public function manage(User $actor, User $target): bool
    {
        return $this->viewAny($actor) && $this->roles->canManage($actor, $target);
    }

    public function resetTwoFactor(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $this->manage($actor, $target);
    }

    public function viewAuditLog(User $actor): bool
    {
        return $actor->isActive() && $actor->isSuperAdmin();
    }
}
