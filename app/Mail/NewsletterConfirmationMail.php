<?php

namespace App\Mail;

use App\Services\NewsletterService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email de double opt-in (cahier § 13.1) : lien valable 48 h. */
class NewsletterConfirmationMail extends Mailable
{
    public function __construct(public readonly string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirmez votre inscription à la newsletter de '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.newsletter-confirmation', with: [
            'url' => route('newsletter.confirm', ['token' => $this->token]),
            'hours' => NewsletterService::CONFIRM_HOURS,
        ]);
    }
}
