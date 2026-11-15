<?php

namespace App\Services;

use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SecurityNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Actions sur les membres (cahier § 4.7, R1, R5, R6, § 8.1.7).
 * Chaque action vérifie elle-même les règles : l'interface n'est jamais la
 * seule barrière.
 */
class UserManagement
{
    public function __construct(private readonly RoleAssignment $roles) {}

    public function changeRole(User $actor, User $target, Role $role): void
    {
        $this->authorizeManage($actor, $target);
        if (! $this->roles->canAssign($actor, $role)) {
            throw new AuthorizationException('Vous ne pouvez pas attribuer ce rôle (vous n\'en possédez pas toutes les permissions).');
        }

        DB::transaction(function () use ($target, $role) {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            if ($locked->isSuperAdmin() && ! $role->isSuperAdmin()) {
                $this->ensureAnotherActiveSuperAdmin($locked);
            }
            $locked->forceFill(['role_id' => $role->id])->save();
        });

        Audit::record('user.role_changed', $target, ['from' => $target->role->name, 'to' => $role->name]);
    }

    public function setStatus(User $actor, User $target, UserStatus $status): void
    {
        $this->authorizeManage($actor, $target);
        if ($status === UserStatus::Invited) {
            throw ValidationException::withMessages(['status' => 'Statut non autorisé.']);
        }

        DB::transaction(function () use ($target, $status) {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            if ($status !== UserStatus::Active && $locked->isSuperAdmin()) {
                $this->ensureAnotherActiveSuperAdmin($locked);
            }
            $locked->forceFill(['status' => $status])->save();
            if ($status === UserStatus::Suspended) {
                app(SessionManager::class)->destroyAll($locked);
            }
        });

        Audit::record('user.status_changed', $target, ['status' => $status->value]);
    }

    public function delete(User $actor, User $target): void
    {
        $this->authorizeManage($actor, $target);

        DB::transaction(function () use ($target) {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            if ($locked->isSuperAdmin()) {
                $this->ensureAnotherActiveSuperAdmin($locked);
            }
            Audit::record('user.deleted', $locked, ['email' => $locked->email, 'role' => $locked->role->name]);
            app(ImageProcessor::class)->delete($locked->avatar_path);
            $locked->delete();
        });
    }

    /** § 8.1.7 : seul un Super Admin réinitialise la 2FA d'un membre (soumis à R6). */
    public function resetTwoFactor(User $actor, User $target): void
    {
        if (! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Seul un Super Admin peut réinitialiser une double authentification.');
        }
        $this->authorizeManage($actor, $target);

        $target->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null])->save();
        app(SessionManager::class)->destroyAll($target);

        Audit::record('two_factor.reset_by_admin', $target);
        $target->notify(new SecurityNotification(SecurityNotification::TWO_FACTOR_RESET_BY_ADMIN));
    }

    private function authorizeManage(User $actor, User $target): void
    {
        if (! $actor->hasPermission(Permission::ManageUsers)) {
            throw new AuthorizationException('Vous n\'avez pas la permission de gérer les membres.');
        }
        if ($actor->is($target)) {
            throw new AuthorizationException('Vous ne pouvez pas modifier votre propre rôle, statut ou compte depuis cet écran.');
        }
        if (! $this->roles->canManage($actor, $target)) {
            throw new AuthorizationException('Ce membre a un rôle supérieur au vôtre : action refusée.');
        }
    }

    /** Il doit toujours rester au moins un Super Admin actif (conséquence de R1 + R6). */
    private function ensureAnotherActiveSuperAdmin(User $target): void
    {
        $others = User::query()
            ->where('role_id', $target->role_id)->where('status', UserStatus::Active->value)
            ->whereKeyNot($target->id)->lockForUpdate()->count();

        if ($others === 0) {
            throw ValidationException::withMessages(['user' => 'Impossible : il doit rester au moins un Super Admin actif.']);
        }
    }
}
