<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentCodeNotification extends Notification
{
    public function __construct(private readonly string $code) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Code de vérification — '.config('app.name'))
            ->greeting('Bonjour,')
            ->line('Voici votre code pour configurer la double authentification :')
            ->line('**'.$this->code.'**')
            ->line('Il est valable 10 minutes. Si vous n\'êtes pas à l\'origine de cette demande, changez votre mot de passe.')
            ->salutation(config('app.name'));
    }
}
