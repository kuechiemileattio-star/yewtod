<?php

namespace App\Livewire\Concerns;

/**
 * Actions sensibles depuis Livewire (cahier § 8.1.6) : si la dernière
 * confirmation du mot de passe date de plus de 15 minutes, on renvoie vers
 * l'écran de confirmation, puis on revient sur la page.
 */
trait ConfirmsPassword
{
    protected function passwordRecentlyConfirmed(): bool
    {
        $confirmedAt = (int) session('auth.password_confirmed_at', 0);

        return time() - $confirmedAt < (int) config('auth.password_timeout', 900);
    }

    protected function requirePasswordConfirmation(): bool
    {
        if ($this->passwordRecentlyConfirmed()) {
            return true;
        }

        session()->put('url.intended', url()->previous());
        $this->redirectRoute('password.confirm');

        return false;
    }
}
