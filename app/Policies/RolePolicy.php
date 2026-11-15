<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleAssignment;

/** R5, R8. */
class RolePolicy
{
    public function __construct(private readonly RoleAssignment $roles) {}

    public function create(User $actor): bool
    {
        return $actor->isActive() && $actor->hasPermission(Permission::ManageUsers);
    }

    public function update(User $actor, Role $role): bool
    {
        return $this->roles->canEditRole($actor, $role);
    }

    public function delete(User $actor, Role $role): bool
    {
        return ! $role->is_system && $this->roles->canEditRole($actor, $role);
    }

    public function assign(User $actor, Role $role): bool
    {
        return $this->roles->canAssign($actor, $role);
    }
}
