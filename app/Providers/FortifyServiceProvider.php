<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\SecurityNotification;
use App\Services\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Les routes sont redéclarées dans routes/auth.php (limitation et
        // non-énumération maîtrisées).
        Fortify::ignoreRoutes();
    }

    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('auth.reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()->where('email', Str::lower(trim((string) $request->input('email'))))->first();

            if ($user === null || $user->status === UserStatus::Invited || $user->password === '') {
                // Même coût de calcul qu'une vraie vérification : le temps de
                // réponse ne révèle pas si le compte existe.
                Hash::check((string) $request->input('password'), '$argon2id$v=19$m=65536,t=4,p=1$eFRoa0gvczhBbW9PNlNqdw$J+fMipLj/WJXtH+aDRGeg7ouFFJFuTL4wiZBYa1nefk');

                return null;
            }

            return Hash::check((string) $request->input('password'), $user->password) ? $user : null;
        });

        $this->configureRateLimiting();
        $this->registerAuditListeners();
    }

    /**
     * 5 tentatives par minute et par couple email + IP, puis fenêtres plus
     * longues : l'attente s'allonge à mesure que les échecs s'accumulent.
     */
    private function configureRateLimiting(): void
    {
        $tooMany = function (Request $request, array $headers) {
            $seconds = (int) ($headers['Retry-After'] ?? 60);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "Trop de tentatives. Réessayez dans {$seconds} secondes."])
                ->setStatusCode(429)->withHeaders($headers);
        };

        RateLimiter::for('login', function (Request $request) use ($tooMany) {
            $key = Str::transliterate(Str::lower((string) $request->input('email'))).'|'.$request->ip();

            return [
                Limit::perMinute(5)->by('m:'.$key)->response($tooMany),
                Limit::perMinutes(15, 10)->by('q:'.$key)->response($tooMany),
                Limit::perHour(20)->by('h:'.$key)->response($tooMany),
                Limit::perHour(100)->by('ip:'.$request->ip())->response($tooMany),
            ];
        });

        RateLimiter::for('two-factor', function (Request $request) {
            $key = (string) $request->session()->get('login.id').'|'.$request->ip();

            return [
                Limit::perMinute(5)->by('2fa:'.$key)->response(fn (Request $r, array $h) => redirect()->route('two-factor.login')
                    ->withErrors(['code' => 'Trop d\'essais. Réessayez dans '.($h['Retry-After'] ?? 60).' secondes.'])->setStatusCode(429)->withHeaders($h)),
                Limit::perHour(30)->by('2fah:'.$key),
            ];
        });

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by('pr:'.$request->ip()),
            Limit::perHour(10)->by('prh:'.Str::lower((string) $request->input('email')).'|'.$request->ip()),
        ]);

        RateLimiter::for('onboarding', fn (Request $request) => Limit::perMinute(10)->by('ob:'.$request->ip()));
    }

    private function registerAuditListeners(): void
    {
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                Audit::record('auth.login', $event->user, [], $event->user);
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            // L'email tenté n'est pas un secret ; le mot de passe n'est jamais journalisé.
            Audit::record('auth.failed', $event->user instanceof User ? $event->user : null, [
                'email' => Str::limit(Str::lower((string) ($event->credentials['email'] ?? '')), 254, ''),
            ]);
        });

        Event::listen(Lockout::class, fn (Lockout $event) => Audit::record('auth.lockout', null, [
            'email' => Str::limit(Str::lower((string) $event->request->input('email')), 254, ''),
        ]));

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user instanceof User) {
                Audit::record('auth.logout', $event->user, [], $event->user);
            }
        });

        Event::listen(TwoFactorAuthenticationFailed::class, fn (TwoFactorAuthenticationFailed $e) => Audit::record('auth.two_factor_failed', $e->user instanceof User ? $e->user : null));

        Event::listen(TwoFactorAuthenticationConfirmed::class, function (TwoFactorAuthenticationConfirmed $e) {
            if ($e->user instanceof User) {
                Audit::record('two_factor.enabled', $e->user);
                $e->user->notify(new SecurityNotification(SecurityNotification::TWO_FACTOR_ENABLED));
            }
        });

        Event::listen(TwoFactorAuthenticationDisabled::class, function (TwoFactorAuthenticationDisabled $e) {
            if ($e->user instanceof User) {
                Audit::record('two_factor.disabled', $e->user);
                $e->user->notify(new SecurityNotification(SecurityNotification::TWO_FACTOR_DISABLED));
            }
        });

        Event::listen(RecoveryCodesGenerated::class, function (RecoveryCodesGenerated $e) {
            // Fortify émet aussi cet événement à l'activation initiale ; on ne
            // notifie que les régénérations d'une 2FA déjà confirmée.
            if ($e->user instanceof User && $e->user->two_factor_confirmed_at !== null) {
                Audit::record('two_factor.recovery_codes_regenerated', $e->user);
                $e->user->notify(new SecurityNotification(SecurityNotification::RECOVERY_CODES_REGENERATED));
            }
        });
    }
}
