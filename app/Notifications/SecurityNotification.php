<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte de sécurité envoyée au membre (cahier § 8.1.8) : 2FA activée ou
 * désactivée, codes régénérés, mot de passe changé, 2FA réinitialisée.
 * Elle ne contient jamais de secret, seulement le fait et la date.
 */
class SecurityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const TWO_FACTOR_ENABLED = 'two_factor_enabled';

    public const TWO_FACTOR_DISABLED = 'two_factor_disabled';

    public const RECOVERY_CODES_REGENERATED = 'recovery_codes_regenerated';

    public const PASSWORD_CHANGED = 'password_changed';

    public const TWO_FACTOR_RESET_BY_ADMIN = 'two_factor_reset_by_admin';

    public function __construct(public readonly string $kind) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $line] = match ($this->kind) {
            self::TWO_FACTOR_ENABLED => ['Double authentification activée', 'La double authentification vient d\'être activée sur votre compte.'],
            self::TWO_FACTOR_DISABLED => ['Double authentification désactivée', 'La double authentification vient d\'être désactivée sur votre compte. Elle vous sera redemandée à la prochaine connexion.'],
            self::RECOVERY_CODES_REGENERATED => ['Nouveaux codes de secours', 'De nouveaux codes de secours ont été générés : les anciens ne fonctionnent plus.'],
            self::PASSWORD_CHANGED => ['Mot de passe modifié', 'Le mot de passe de votre compte vient d\'être modifié. Les autres sessions ont été déconnectées.'],
            self::TWO_FACTOR_RESET_BY_ADMIN => ['Double authentification réinitialisée', 'Un Super Admin a réinitialisé votre double authentification. Vous devrez la configurer à nouveau à votre prochaine connexion.'],
            default => ['Alerte de sécurité', 'Une modification de sécurité a eu lieu sur votre compte.'],
        };

        return (new MailMessage)
            ->subject($subject.' — '.config('app.name'))
            ->greeting('Bonjour,')
            ->line($line)
            ->line('Date : '.now()->setTimezone(config('app.display_timezone'))->translatedFormat('j F Y à H\hi').'.')
            ->line('Si vous n\'êtes pas à l\'origine de cette action, contactez immédiatement un administrateur.')
            ->salutation(config('app.name'));
    }
}
