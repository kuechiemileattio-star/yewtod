<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * « Mot de passe oublié » sans énumération de comptes (cahier § 8.3) : la
 * réponse est strictement identique que l'adresse existe ou non, que l'envoi
 * soit limité ou non.
 */
class PasswordResetLinkController extends Controller
{
    public const MESSAGE = 'Si un compte correspond à cette adresse, un lien de réinitialisation valable 60 minutes vient de lui être envoyé.';

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:254']]);
        $email = Str::lower(trim($data['email']));

        $user = User::query()->where('email', $email)->first();
        if ($user !== null && $user->status === UserStatus::Active) {
            Password::broker()->sendResetLink(['email' => $email]);
        }

        return back()->with('status', self::MESSAGE);
    }
}
