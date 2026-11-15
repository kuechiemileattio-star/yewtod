<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Notifications\SecurityNotification;
use App\Services\Audit;
use App\Services\PasswordPolicy;
use App\Services\SessionManager;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Réinitialisation par lien email (cahier § 8.3). Le lien est à usage
 * unique et valable 60 minutes (config/auth.php). La 2FA reste exigée
 * à la connexion suivante : la réinitialisation ne connecte pas.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    /** @param array<string, string> $input */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, ['password' => PasswordPolicy::rules($user)])->validate();

        PasswordPolicy::apply($user, $input['password']);
        app(SessionManager::class)->destroyAll($user);

        Audit::record('auth.password_reset', $user, [], $user);
        $user->notify(new SecurityNotification(SecurityNotification::PASSWORD_CHANGED));
    }
}
