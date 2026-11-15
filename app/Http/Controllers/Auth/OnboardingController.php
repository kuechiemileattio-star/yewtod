<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\InvitationService;
use App\Services\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Première activation d'un compte invité (cahier § 5.7.4, R1, R2, R4).
 */
class OnboardingController extends Controller
{
    public function __construct(private readonly InvitationService $invitations) {}

    public function show(string $token): View
    {
        $invitation = $this->invitations->findUsable($token);

        return view('auth.onboarding', [
            'invitation' => $invitation,
            'token' => $invitation ? $token : null,
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->invitations->findUsable($token);
        abort_if($invitation === null, 410, 'Ce lien d\'invitation n\'est plus valide.');

        // Seuls ces champs sont lus : un « role_id » ou « status » envoyé par
        // le client est ignoré (R4).
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'password' => PasswordPolicy::rules(null, (string) $request->input('name'), $invitation->email),
        ]);

        $user = $this->invitations->accept($token, $data['name'], $data['bio'] ?? null, $data['password']);

        Auth::login($user);
        $request->session()->regenerate();
        // Le lien d'invitation a prouvé l'accès à la boîte mail : pas de code
        // email supplémentaire avant la configuration de la 2FA.
        $request->session()->put('two_factor.mail_verified', now()->getTimestamp());

        return redirect()->route('security.two-factor')
            ->with('status', 'Compte activé. Dernière étape : configurer la double authentification.');
    }
}
