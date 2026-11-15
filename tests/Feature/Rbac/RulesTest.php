<?php

use App\Enums\UserStatus;
use App\Livewire\Dashboard\Profile;
use App\Livewire\Dashboard\Users;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\SecurityNotification;
use App\Services\InvitationService;
use App\Services\RoleAssignment;
use App\Services\RoleManagement;
use App\Services\UserManagement;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

// Cahier § 6 : une règle R1–R8 = au moins un test.

it('R1 : personne ne modifie son propre rôle, même un Super Admin', function () {
    $sa = member('super-admin');
    app(UserManagement::class)->changeRole($sa, $sa, role('editeur'));
})->throws(AuthorizationException::class);

it('R1 : personne ne modifie son propre statut', function () {
    $admin = member('administrateur');
    app(UserManagement::class)->setStatus($admin, $admin, UserStatus::Suspended);
})->throws(AuthorizationException::class);

it('R3 : email, rôle, statut, invité par ne sont pas assignables en masse', function () {
    $user = member();
    expect(fn () => $user->fill(['email' => 'pirate@exemple.test']))->toThrow(MassAssignmentException::class);
    foreach (['role_id', 'status', 'invited_by', 'created_at', 'id', 'password'] as $field) {
        expect($user->isFillable($field))->toBeFalse();
    }
    expect((new Role)->isFillable('is_system'))->toBeFalse();
});

it('R3 : le profil ignore un email ou un rôle injecté via Livewire', function () {
    $user = member();
    Livewire::actingAs($user)->test(Profile::class)
        ->set('name', 'Nouveau Nom')->call('saveProfile');
    expect(fn () => Livewire::actingAs($user)->test(Profile::class)->set('email', 'x@y.z'))
        ->toThrow(Exception::class);
    expect($user->fresh()->email)->not->toBe('x@y.z')->and($user->fresh()->name)->toBe('Nouveau Nom');
});

it('R5 : non-escalade, on n\'attribue pas un rôle plus puissant que le sien', function () {
    $admin = member('administrateur');
    $editorRole = role('editeur');
    $limited = member('editeur');
    $limited->role->permissions()->attach(Permission::where('key', 'manage_users')->value('id'));
    $limited->load('role.permissions');
    $target = member('contributeur');
    expect(app(RoleAssignment::class)->canAssign($limited->fresh(), role('administrateur')))->toBeFalse()
        ->and(app(RoleAssignment::class)->canAssign($admin, $editorRole))->toBeTrue();
    app(UserManagement::class)->changeRole($limited->fresh(), $target, role('administrateur'));
})->throws(AuthorizationException::class);

it('R5 : le rôle système ne s\'attribue que par un Super Admin', function () {
    $ra = app(RoleAssignment::class);
    expect($ra->canAssign(member('administrateur'), role('super-admin')))->toBeFalse()
        ->and($ra->canAssign(member('super-admin'), role('super-admin')))->toBeTrue();
});

it('R6 : un Administrateur ne peut ni suspendre ni supprimer un Super Admin', function () {
    $admin = member('administrateur');
    $sa = member('super-admin');
    expect(fn () => app(UserManagement::class)->setStatus($admin, $sa, UserStatus::Suspended))->toThrow(AuthorizationException::class);
    expect(fn () => app(UserManagement::class)->delete($admin, $sa))->toThrow(AuthorizationException::class);
    expect($sa->fresh()->status)->toBe(UserStatus::Active);
});

it('R6 : un Administrateur gère un Éditeur', function () {
    $admin = member('administrateur');
    $editor = member('editeur');
    app(UserManagement::class)->setStatus($admin, $editor, UserStatus::Suspended);
    expect($editor->fresh()->status)->toBe(UserStatus::Suspended);
});

it('R1 + R6 : il reste toujours au moins un Super Admin actif', function () {
    $sa1 = member('super-admin');
    $sa2 = member('super-admin');
    app(UserManagement::class)->setStatus($sa1, $sa2, UserStatus::Suspended);
    // sa2 est suspendu : sa1 est le dernier actif. Un autre SA réactivé ne peut pas le rétrograder s'il est le dernier.
    User::whereKey($sa2->id)->update(['status' => 'active']);
    app(UserManagement::class)->changeRole($sa2->fresh(), $sa1, role('editeur'));
    expect(fn () => app(UserManagement::class)->changeRole($sa1->fresh(), $sa2->fresh(), role('editeur')))->toThrow(AuthorizationException::class);
    expect(User::whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))->where('status', 'active')->count())->toBe(1);
});

it('refuse de suspendre le dernier Super Admin actif', function () {
    $sa1 = member('super-admin');
    $sa2 = member('super-admin');
    $sa2->forceFill(['status' => UserStatus::Suspended])->save();
    $sa3 = member('super-admin');
    $sa3->forceFill(['status' => UserStatus::Suspended])->save();
    // sa1 est le seul actif ; on simule un acteur SA actif hypothétique en réactivant sa3 juste en mémoire.
    $actor = $sa3->fresh();
    $actor->status = UserStatus::Active;
    app(UserManagement::class)->setStatus($actor, $sa1, UserStatus::Suspended);
})->throws(ValidationException::class);

it('R7 : une invitation ne peut être révoquée que par son auteur ou un supérieur', function () {
    Mail::fake();
    $admin = member('administrateur');
    $inv = app(InvitationService::class)->invite($admin, 'r7@exemple.test', role('editeur'));
    $other = member('moderateur-collaborations');
    $other->role->permissions()->attach(Permission::where('key', 'invite_users')->value('id'));
    expect(app(InvitationService::class)->canRevoke($other->fresh(), $inv))->toBeFalse()
        ->and(app(InvitationService::class)->canRevoke($admin, $inv))->toBeTrue();
});

it('R8 : seul un Super Admin modifie un rôle système', function () {
    seedRoles();
    $sys = Role::create(['name' => 'Système test', 'slug' => 'systeme-test', 'description' => '']);
    $sys->forceFill(['is_system' => true])->save();
    expect(fn () => app(RoleManagement::class)->updatePermissions(member('administrateur'), $sys, ['manage_books']))->toThrow(AuthorizationException::class);
    app(RoleManagement::class)->updatePermissions(member('super-admin'), $sys, ['manage_books']);
    expect($sys->fresh()->permissionKeys())->toBe(['manage_books']);
});

it('R8 : on n\'accorde à un rôle qu\'une permission que l\'on possède', function () {
    $limited = member('editeur');
    $limited->role->permissions()->attach(Permission::where('key', 'manage_users')->value('id'));
    app(RoleManagement::class)->create($limited->fresh(), 'Rôle pirate', '', ['manage_settings']);
})->throws(AuthorizationException::class);

it('R8 : un rôle système ne peut pas être supprimé ni perdre son caractère système', function () {
    expect(fn () => app(RoleManagement::class)->delete(member('super-admin'), role('super-admin')))->toThrow(AuthorizationException::class);
    $r = role('super-admin');
    expect(fn () => $r->fill(['is_system' => false]))->toThrow(MassAssignmentException::class);
});

it('refuse de supprimer un rôle encore porté', function () {
    member('editeur');
    app(RoleManagement::class)->delete(member('super-admin'), role('editeur'));
})->throws(ValidationException::class);

it('affiche un message clair quand une action est refusée', function () {
    $admin = member('administrateur');
    $sa = member('super-admin');
    Livewire::actingAs($admin)->withQueryParams([])->test(Users::class)
        ->call('toggleStatus', $sa->id)
        ->assertDispatched('toast', type: 'error', message: 'Ce membre a un rôle supérieur au vôtre : action refusée.');
});

it('réinitialisation 2FA : réservée au Super Admin, journalisée et notifiée', function () {
    Notification::fake();
    $sa = member('super-admin');
    $editor = member('editeur');
    expect(fn () => app(UserManagement::class)->resetTwoFactor(member('administrateur'), $editor))->toThrow(AuthorizationException::class);
    app(UserManagement::class)->resetTwoFactor($sa, $editor);
    expect($editor->fresh()->hasTwoFactorEnabled())->toBeFalse();
    Notification::assertSentTo($editor, SecurityNotification::class);
    expect(Activity::where('event', 'two_factor.reset_by_admin')->exists())->toBeTrue();
});

it('supprimer un membre qui a invité met « invité par » à NULL (bug n° 10)', function () {
    $sa = member('super-admin');
    $inviter = member('administrateur');
    $invited = member('editeur');
    $invited->forceFill(['invited_by' => $inviter->id])->save();
    app(UserManagement::class)->delete($sa, $inviter);
    expect($invited->fresh()->invited_by)->toBeNull();
});
