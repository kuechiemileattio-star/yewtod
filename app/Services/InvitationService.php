<?php

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Enums\Permission;
use App\Enums\UserStatus;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Cycle de vie des invitations (cahier § 5.7, règles R2, R4, R5, R7).
 */
class InvitationService
{
    public function __construct(private readonly RoleAssignment $roles) {}

    public function invite(User $actor, string $email, Role $role): Invitation
    {
        // R5 + R7 : l'invitation est émise au nom de l'acteur, pour un rôle qu'il peut attribuer.
        if (! $actor->hasPermission(Permission::InviteUsers) || ! $this->roles->canAssign($actor, $role)) {
            throw new AuthorizationException('Vous ne pouvez pas inviter un membre avec ce rôle.');
        }

        $email = Str::lower(trim($email));

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'Cette adresse appartient déjà à un membre.']);
        }

        $token = Str::random(64);

        $invitation = DB::transaction(function () use ($actor, $email, $role, $token) {
            // Une seule invitation en attente par email : les précédentes sont révoquées.
            Invitation::query()->pending()->where('email', $email)->lockForUpdate()->get()
                ->each(fn (Invitation $old) => $old->forceFill([
                    'status' => InvitationStatus::Revoked, 'revoked_at' => now(),
                ])->save());

            $invitation = new Invitation;
            $invitation->forceFill([
                'email' => $email,
                'role_id' => $role->id,
                'token_hash' => Invitation::hashToken($token),
                'status' => InvitationStatus::Pending,
                'invited_by' => $actor->id,
                'expires_at' => now()->addDays(Invitation::VALIDITY_DAYS),
            ])->save();

            return $invitation;
        });

        try {
            Mail::to($email)->send(new InvitationMail($invitation, $token, $actor->name));
        } catch (Throwable $e) {
            // Si l'email ne part pas, l'invitation est annulée (cahier § 5.7).
            Log::error('Échec d\'envoi de l\'invitation', ['invitation_id' => $invitation->id, 'error' => $e->getMessage()]);
            $invitation->delete();

            throw ValidationException::withMessages(['email' => 'L\'email d\'invitation n\'a pas pu être envoyé. L\'invitation a été annulée.']);
        }

        $invitation->forceFill(['sent_at' => now()])->save();
        Audit::record('invitation.created', $invitation, ['email' => $email, 'role' => $role->name]);

        return $invitation;
    }

    public function revoke(User $actor, Invitation $invitation): void
    {
        if (! $this->canRevoke($actor, $invitation)) {
            throw new AuthorizationException('Vous ne pouvez pas révoquer cette invitation.');
        }

        DB::transaction(function () use ($invitation) {
            $locked = Invitation::query()->lockForUpdate()->findOrFail($invitation->id);
            if ($locked->status !== InvitationStatus::Pending) {
                throw ValidationException::withMessages(['invitation' => 'Cette invitation n\'est plus en attente.']);
            }
            $locked->forceFill(['status' => InvitationStatus::Revoked, 'revoked_at' => now()])->save();
        });

        Audit::record('invitation.revoked', $invitation, ['email' => $invitation->email]);
    }

    public function canRevoke(User $actor, Invitation $invitation): bool
    {
        if (! $actor->isActive() || ! $actor->hasPermission(Permission::InviteUsers)) {
            return false;
        }

        return $invitation->invited_by === $actor->id || $this->roles->canAssign($actor, $invitation->role);
    }

    /** Retrouve une invitation utilisable à partir du jeton en clair reçu par email. */
    public function findUsable(string $token): ?Invitation
    {
        if (strlen($token) !== 64) {
            return null;
        }

        $hash = Invitation::hashToken($token);
        $invitation = Invitation::query()->with('role')->where('token_hash', $hash)->first();

        // Comparaison en temps constant, en plus de la recherche indexée.
        if ($invitation === null || ! hash_equals($invitation->token_hash, $hash) || ! $invitation->isUsable()) {
            return null;
        }

        return $invitation;
    }

    /**
     * Onboarding : crée le compte puis l'active (R1 exception, R2, R4).
     * Transaction + verrou : deux soumissions simultanées du même lien ne
     * peuvent pas créer deux comptes (usage unique).
     */
    public function accept(string $token, string $name, ?string $bio, string $password): User
    {
        $hash = Invitation::hashToken($token);

        $user = DB::transaction(function () use ($hash, $name, $bio, $password) {
            $invitation = Invitation::query()->where('token_hash', $hash)->lockForUpdate()->first();

            if ($invitation === null || ! hash_equals($invitation->token_hash, $hash) || ! $invitation->isUsable()) {
                throw ValidationException::withMessages(['token' => 'Ce lien d\'invitation n\'est plus valide (expiré, révoqué ou déjà utilisé).']);
            }

            if (User::query()->where('email', $invitation->email)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['token' => 'Un compte existe déjà pour cette adresse.']);
            }

            // R4 : le rôle vient UNIQUEMENT de l'invitation.
            $user = new User(['name' => $name, 'bio' => $bio]);
            $user->forceFill([
                'email' => $invitation->email,
                'role_id' => $invitation->role_id,
                'status' => UserStatus::Invited,
                'invited_by' => $invitation->invited_by,
                'password' => '',
            ]);
            $user->save();
            PasswordPolicy::apply($user, $password);

            // R1 (seule exception) + R2 : invité → actif, invitation → acceptée.
            $user->forceFill(['status' => UserStatus::Active])->save();
            $invitation->forceFill(['status' => InvitationStatus::Accepted, 'accepted_at' => now()])->save();

            return $user;
        });

        Audit::record('invitation.accepted', $user, ['email' => $user->email], $user);

        return $user;
    }
}
