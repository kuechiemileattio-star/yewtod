<?php

namespace App\Livewire\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/** Retours d'action homogènes (toasts, cahier § 10) et messages d'erreur clairs (§ 4.7). */
trait Toasts
{
    protected function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    /** Exécute une action métier et traduit les refus en message lisible. */
    protected function attempt(callable $action, string $success): bool
    {
        try {
            $action();
            if ($success !== '') {
                $this->toast($success);
            }

            return true;
        } catch (AuthorizationException $e) {
            $this->toast($e->getMessage() ?: 'Action refusée.', 'error');
        } catch (ValidationException $e) {
            $this->toast(collect($e->errors())->flatten()->first() ?? 'Données invalides.', 'error');
        }

        return false;
    }
}
