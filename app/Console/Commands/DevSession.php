<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Console\Command;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * OUTIL LOCAL pour les captures d'écran automatiques (Playwright) : ouvre
 * une session déjà authentifiée pour un membre de démonstration et affiche
 * la valeur du cookie. Refusé hors environnement local.
 */
class DevSession extends Command
{
    protected $signature = 'yewtod:dev-session {email}';

    protected $description = '[local] Cookie de session authentifiée pour les captures d\'écran';

    protected $hidden = true;

    public function handle(): int
    {
        if (! app()->isLocal()) {
            $this->error('Commande réservée à l\'environnement local.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $this->argument('email'))->firstOrFail();
        $id = Str::random(40);
        $payload = base64_encode((string) json_encode([
            '_token' => Str::random(40),
            'login_web_'.sha1(SessionGuard::class) => $user->id,
            'auth.password_confirmed_at' => time(),
        ]));
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'Playwright', 'payload' => $payload, 'last_activity' => time()]);

        $name = (string) config('session.cookie');
        $value = Crypt::encrypt(CookieValuePrefix::create($name, Crypt::getKey()).$id, false);
        $this->line($name.'='.$value);

        return self::SUCCESS;
    }
}
