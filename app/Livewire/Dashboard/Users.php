<?php

namespace App\Livewire\Dashboard;

use App\Enums\InvitationStatus;
use App\Enums\Permission as PermissionKey;
use App\Enums\UserStatus;
use App\Livewire\Concerns\ConfirmsPassword;
use App\Livewire\Concerns\Toasts;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Services\InvitationService;
use App\Services\RoleAssignment;
use App\Services\RoleManagement;
use App\Services\UserManagement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Écran « Utilisateurs & rôles » (cahier § 4.7). Toutes les actions passent
 * par des services qui revérifient R1–R8 : cet écran n'est qu'une façade.
 */
#[Layout('layouts::dashboard', ['title' => 'Utilisateurs & rôles'])]
class Users extends Component
{
    use ConfirmsPassword, Toasts, WithPagination;

    #[Url(as: 'onglet')]
    public string $tab = 'membres';

    #[Url(as: 'q')]
    public string $search = '';

    public string $inviteEmail = '';

    public ?int $inviteRole = null;

    public string $roleName = '';

    public string $roleDescription = '';

    /** @var list<string> */
    public array $rolePermissions = [];

    /** @var array<int, list<string>> */
    public array $matrix = [];

    public function mount(): void
    {
        $me = $this->me();
        abort_unless($me->hasPermission(PermissionKey::ManageUsers) || $me->hasPermission(PermissionKey::InviteUsers), 403);
        if (! $me->hasPermission(PermissionKey::ManageUsers)) {
            $this->tab = 'invitations';
        }
        if (! in_array($this->tab, ['membres', 'invitations', 'roles'], true)) {
            $this->tab = 'membres';
        }
        $this->loadMatrix();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function changeRole(int $userId, int $roleId): void
    {
        if (! $this->requirePasswordConfirmation()) {
            return;
        }
        $target = User::query()->findOrFail($userId);
        $role = Role::query()->findOrFail($roleId);
        $this->attempt(fn () => app(UserManagement::class)->changeRole($this->me(), $target, $role), "Rôle de {$target->name} : {$role->name}.");
    }

    public function toggleStatus(int $userId): void
    {
        $target = User::query()->findOrFail($userId);
        $status = $target->status === UserStatus::Active ? UserStatus::Suspended : UserStatus::Active;
        $this->attempt(fn () => app(UserManagement::class)->setStatus($this->me(), $target, $status), "{$target->name} : {$status->label()}.");
    }

    public function deleteUser(int $userId): void
    {
        if (! $this->requirePasswordConfirmation()) {
            return;
        }
        $target = User::query()->findOrFail($userId);
        $this->attempt(fn () => app(UserManagement::class)->delete($this->me(), $target), "Compte de {$target->name} supprimé.");
    }

    public function resetTwoFactor(int $userId): void
    {
        if (! $this->requirePasswordConfirmation()) {
            return;
        }
        $target = User::query()->findOrFail($userId);
        $this->attempt(fn () => app(UserManagement::class)->resetTwoFactor($this->me(), $target), "Double authentification de {$target->name} réinitialisée.");
    }

    public function invite(): void
    {
        $this->validate([
            'inviteEmail' => ['required', 'email:rfc', 'max:254'],
            'inviteRole' => ['required', 'integer', 'exists:roles,id'],
        ], [], ['inviteEmail' => 'adresse email', 'inviteRole' => 'rôle']);

        $role = Role::query()->findOrFail($this->inviteRole);
        if ($this->attempt(fn () => app(InvitationService::class)->invite($this->me(), $this->inviteEmail, $role), "Invitation envoyée à {$this->inviteEmail}.")) {
            $this->reset('inviteEmail', 'inviteRole');
        }
    }

    public function revoke(int $invitationId): void
    {
        $invitation = Invitation::query()->findOrFail($invitationId);
        $this->attempt(fn () => app(InvitationService::class)->revoke($this->me(), $invitation), 'Invitation révoquée.');
    }

    public function createRole(): void
    {
        $this->validate([
            'roleName' => ['required', 'string', 'min:2', 'max:80'],
            'roleDescription' => ['nullable', 'string', 'max:255'],
            'rolePermissions' => ['array'],
            'rolePermissions.*' => ['string'],
        ], [], ['roleName' => 'nom du rôle', 'roleDescription' => 'description']);

        if ($this->attempt(fn () => app(RoleManagement::class)->create($this->me(), $this->roleName, $this->roleDescription, array_values($this->rolePermissions)), "Rôle « {$this->roleName} » créé.")) {
            $this->reset('roleName', 'roleDescription', 'rolePermissions');
            $this->loadMatrix();
        }
    }

    public function saveRole(int $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);
        $this->attempt(fn () => app(RoleManagement::class)->updatePermissions($this->me(), $role, array_values($this->matrix[$roleId] ?? [])), "Permissions de « {$role->name} » enregistrées.");
        $this->loadMatrix();
    }

    public function deleteRole(int $roleId): void
    {
        $role = Role::query()->findOrFail($roleId);
        if ($this->attempt(fn () => app(RoleManagement::class)->delete($this->me(), $role), "Rôle « {$role->name} » supprimé.")) {
            $this->loadMatrix();
        }
    }

    public function render(): View
    {
        $me = $this->me();
        $assignment = app(RoleAssignment::class);

        $members = User::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.addcslashes($this->search, '%_\\').'%')
                ->orWhere('email', 'like', '%'.addcslashes($this->search, '%_\\').'%')))
            ->orderBy('name')->paginate(20);

        return view('livewire.dashboard.users', [
            'me' => $me,
            'members' => $members,
            'assignable' => $assignment->assignableRoles($me),
            'canManage' => fn (User $u) => Gate::forUser($me)->allows('manage', $u),
            'invitations' => Invitation::query()->with(['role', 'inviter'])->latest()->limit(100)->get(),
            'roles' => Role::query()->with('permissions')->withCount('users')->orderByDesc('is_system')->orderBy('name')->get(),
            'permissions' => PermissionKey::cases(),
            'statuses' => InvitationStatus::cases(),
        ]);
    }

    private function loadMatrix(): void
    {
        $this->matrix = Role::query()->with('permissions')->get()
            ->mapWithKeys(fn (Role $r) => [$r->id => $r->permissionKeys()])->all();
    }

    private function me(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
