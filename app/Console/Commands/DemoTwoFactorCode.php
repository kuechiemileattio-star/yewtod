<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

/**
 * Outil LOCAL : affiche le code 2FA du moment d'un compte de démonstration
 *
 * (*@yewtod.test), pour tester chaque rôle sans téléphone. Refusé hors
 * environnement local et pour tout autre compte.
 */
class DemoTwoFactorCode extends Command
{
    protected $signature = 'yewtod:code-demo {email : Compte de démonstration, ex. editeur@yewtod.test}';

    protected $description = 'Affiche le code 2FA courant d\'un compte de démonstration (local uniquement)';

    public function handle(TwoFactorAuthenticationProvider $provider): int
    {
        $email = (string) $this->argument('email');
        if (! app()->environment('local') || ! str_ends_with($email, '@yewtod.test')) {
            $this->error('Commande réservée aux comptes de démonstration, en environnement local.');

            return self::FAILURE;
        }
        $user = User::query()->where('email', $email)->first();
        if ($user === null || $user->two_factor_secret === null) {
            $this->error('Compte de démonstration introuvable ou sans 2FA.');

            return self::FAILURE;
        }
        $secret = decrypt($user->two_factor_secret);
        $google = new Google2FA;
        $this->info('Code actuel : '.$google->getCurrentOtp($secret).' (valable environ 30 secondes)');
        $this->line('Clé à saisir dans une application d\'authentification (facultatif) : '.$secret);

        return self::SUCCESS;
    }
}
