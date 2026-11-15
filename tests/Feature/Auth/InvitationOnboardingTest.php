<?php

use App\Enums\InvitationStatus;
use App\Enums\UserStatus;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Permission;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

// Cahier § 5.7 et règles R2, R4, R5, R7.

function inviteAndCapture(User $actor, string $email, string $roleSlug): string
{
    Mail::fake();
    app(InvitationService::class)->invite($actor, $email, role($roleSlug));
    $token = null;
    Mail::assertSent(InvitationMail::class, function (InvitationMail $m) use (&$token) {
        $token = $m->token;

        return true;
    });

    return $token;
}

const VALID_PASSWORD = 'Ciel-Orange-Velo-93?';

it('stocke le jeton d\'invitation haché, jamais en clair', function () {
    $token = inviteAndCapture(member('administrateur'), 'nouveau@exemple.test', 'editeur');
    $inv = Invitation::sole();
    expect($inv->token_hash)->toBe(hash('sha256', $token))->not->toBe($token)
        ->and(DB::table('invitations')->where('token_hash', $token)->exists())->toBeFalse();
});

it('fixe la validité à 7 jours', function () {
    inviteAndCapture(member('administrateur'), 'a@exemple.test', 'editeur');
    expect(Invitation::sole()->expires_at->diffInDays(now(), true))->toBeGreaterThan(6.9)->toBeLessThan(7.1);
});

it('révoque les invitations précédentes pour le même email', function () {
    $admin = member('administrateur');
    inviteAndCapture($admin, 'a@exemple.test', 'editeur');
    inviteAndCapture($admin, 'a@exemple.test', 'contributeur');
    expect(Invitation::query()->pending()->count())->toBe(1)
        ->and(Invitation::query()->where('status', 'revoked')->count())->toBe(1);
});

it('refuse de réinviter un email déjà membre', function () {
    $admin = member('administrateur');
    $existing = member();
    Mail::fake();
    app(InvitationService::class)->invite($admin, $existing->email, role('editeur'));
})->throws(ValidationException::class);

it('R5 : refuse d\'inviter avec un rôle dont on n\'a pas toutes les permissions', function () {
    $editor = member('editeur');
    $editor->role->permissions()->attach(Permission::where('key', 'invite_users')->value('id'));
    $editor->load('role.permissions');
    Mail::fake();
    app(InvitationService::class)->invite($editor, 'x@exemple.test', role('administrateur'));
})->throws(AuthorizationException::class);

it('R5 : seul un Super Admin invite un Super Admin', function () {
    Mail::fake();
    app(InvitationService::class)->invite(member('administrateur'), 'x@exemple.test', role('super-admin'));
})->throws(AuthorizationException::class);

it('R7 : l\'invitation est émise au nom de son créateur', function () {
    $admin = member('administrateur');
    inviteAndCapture($admin, 'x@exemple.test', 'editeur');
    expect(Invitation::sole()->invited_by)->toBe($admin->id);
});

it('annule l\'invitation si l\'email ne part pas', function () {
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP indisponible'));
    expect(fn () => app(InvitationService::class)->invite(member('administrateur'), 'x@exemple.test', role('editeur')))
        ->toThrow(ValidationException::class);
    expect(Invitation::count())->toBe(0);
});

it('active le compte : rôle de l\'invitation (R4), statut actif (R2), invitation acceptée', function () {
    $admin = member('administrateur');
    $token = inviteAndCapture($admin, 'nouveau@exemple.test', 'editeur');

    $this->post(route('onboarding.store', $token), [
        'name' => 'Nouvelle Membre', 'password' => VALID_PASSWORD, 'password_confirmation' => VALID_PASSWORD,
        'role_id' => role('super-admin')->id, 'status' => 'active', // ignorés (R4)
    ])->assertRedirect(route('security.two-factor'));

    $user = User::where('email', 'nouveau@exemple.test')->sole();
    expect($user->role->slug)->toBe('editeur')
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->invited_by)->toBe($admin->id)
        ->and(Invitation::sole()->status)->toBe(InvitationStatus::Accepted);
    $this->assertAuthenticatedAs($user);
});

it('n\'accepte une invitation qu\'une seule fois', function () {
    $token = inviteAndCapture(member('administrateur'), 'n@exemple.test', 'editeur');
    $data = ['name' => 'Membre', 'password' => VALID_PASSWORD, 'password_confirmation' => VALID_PASSWORD];
    $this->post(route('onboarding.store', $token), $data);
    auth()->logout();
    $this->post(route('onboarding.store', $token), $data)->assertStatus(410);
    expect(User::where('email', 'n@exemple.test')->count())->toBe(1);
});

it('R2 : refuse une invitation expirée ou révoquée', function (string $state) {
    $token = inviteAndCapture(member('administrateur'), 'n@exemple.test', 'editeur');
    $inv = Invitation::sole();
    $state === 'expired'
        ? $inv->forceFill(['expires_at' => now()->subMinute()])->save()
        : $inv->forceFill(['status' => 'revoked'])->save();
    $this->get(route('onboarding.show', $token))->assertSee('plus valide');
    $this->post(route('onboarding.store', $token), ['name' => 'X', 'password' => VALID_PASSWORD, 'password_confirmation' => VALID_PASSWORD])->assertStatus(410);
})->with(['expired', 'revoked']);

it('applique la politique de mot de passe à l\'onboarding', function () {
    $token = inviteAndCapture(member('administrateur'), 'jeanne@exemple.test', 'editeur');
    $this->post(route('onboarding.store', $token), ['name' => 'Jeanne Martin', 'password' => 'court', 'password_confirmation' => 'court'])
        ->assertSessionHasErrors('password');
    expect(User::where('email', 'jeanne@exemple.test')->exists())->toBeFalse();
});

it('permet de révoquer une invitation en attente', function () {
    $admin = member('administrateur');
    inviteAndCapture($admin, 'x@exemple.test', 'editeur');
    app(InvitationService::class)->revoke($admin, Invitation::sole());
    expect(Invitation::sole()->status)->toBe(InvitationStatus::Revoked);
});
