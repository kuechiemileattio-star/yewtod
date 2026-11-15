<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;

/** R5, R7. */
class InvitationPolicy
{
    public function create(User $actor): bool
    {
        return $actor->isActive() && $actor->hasPermission(Permission::InviteUsers);
    }

    public function revoke(User $actor, Invitation $invitation): bool
    {
        return app(InvitationService::class)->canRevoke($actor, $invitation);
    }
}
