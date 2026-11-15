<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorEnrollmentCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

/**
 * Configuration de la 2FA TOTP (cahier § 8.1), au-dessus des actions Fortify.
 * Les codes de secours ne sont affichés qu'une fois, juste après leur
 * génération (flash de session), jamais relus ensuite.
 */
class TwoFactorController extends Controller
{
    private const MAIL_PROOF_TTL = 1800;

    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $verifiedAt = (int) $request->session()->get('two_factor.mail_verified', 0);

        return view('dashboard.security.two-factor', [
            'user' => $user,
            'mailVerified' => $verifiedAt > 0 && now()->getTimestamp() - $verifiedAt < self::MAIL_PROOF_TTL,
            'pending' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
            'enabled' => $user->hasTwoFactorEnabled(),
            // SVG généré par le serveur (BaconQrCode), jamais par l'utilisateur.
            'qrSvg' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null ? $user->twoFactorQrCodeSvg() : null,
            'secretKey' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null ? decrypt($user->two_factor_secret) : null,
            'recoveryCodes' => $request->session()->get('recovery_codes'),
        ]);
    }

    public function sendMailCode(Request $request, TwoFactorEnrollmentCode $codes): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $codes->send($user);

        return back()->with('status', 'Un code à 6 chiffres vient d\'être envoyé à votre adresse email.');
    }

    public function verifyMailCode(Request $request, TwoFactorEnrollmentCode $codes): RedirectResponse
    {
        $request->validate(['mail_code' => ['required', 'string', 'size:6']]);
        /** @var User $user */
        $user = $request->user();

        if (! $codes->verify($user, (string) $request->input('mail_code'))) {
            return back()->withErrors(['mail_code' => 'Code incorrect ou expiré.']);
        }

        $request->session()->put('two_factor.mail_verified', now()->getTimestamp());

        return back()->with('status', 'Adresse vérifiée. Vous pouvez configurer votre application d\'authentification.');
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $verifiedAt = (int) $request->session()->get('two_factor.mail_verified', 0);

        if ($user->hasTwoFactorEnabled()) {
            return back();
        }

        if ($verifiedAt === 0 || now()->getTimestamp() - $verifiedAt >= self::MAIL_PROOF_TTL) {
            return back()->withErrors(['mail_code' => 'Vérifiez d\'abord votre adresse email.']);
        }

        $enable($user, true);

        return back();
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:10']]);
        /** @var User $user */
        $user = $request->user();

        // Lève une ValidationException (« code invalide ») si le code est faux.
        $confirm($user, (string) $request->input('code'));

        $user->refresh();
        $request->session()->forget('two_factor.mail_verified');

        return redirect()->route('security.two-factor')
            ->with('recovery_codes', $user->recoveryCodes())
            ->with('status', 'Double authentification activée. Conservez vos codes de secours en lieu sûr.');
    }

    public function regenerate(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->hasTwoFactorEnabled(), 403);

        $generate($user);

        return redirect()->route('security.two-factor')
            ->with('recovery_codes', $user->fresh()?->recoveryCodes())
            ->with('status', 'Nouveaux codes générés : les anciens ne fonctionnent plus.');
    }

    public function destroy(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $disable($user);

        return redirect()->route('security.two-factor')
            ->with('warning', 'Double authentification désactivée : configurez un nouvel appareil pour continuer.');
    }
}
