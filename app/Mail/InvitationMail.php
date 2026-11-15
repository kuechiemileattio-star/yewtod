<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvitationMail extends Mailable
{
    public function __construct(
        public readonly Invitation $invitation,
        public readonly string $token,
        public readonly string $inviterName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitation à rejoindre l\'équipe de '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.invitation', with: [
            'url' => route('onboarding.show', ['token' => $this->token]),
            'role' => $this->invitation->role->name,
            'expires' => $this->invitation->expires_at->setTimezone(config('app.display_timezone'))->translatedFormat('j F Y à H\hi'),
        ]);
    }
}
