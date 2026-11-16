<?php

namespace App\Services;

use App\Jobs\SyncNewsletterWithBrevo;
use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

/**
 * Newsletter (cahier § 5.6, § 13.1) : double opt-in, désinscription en un
 * clic, aucune énumération, synchronisation Brevo en file d'attente.
 *
 * Jetons : 40 caractères aléatoires envoyés par email, seul leur SHA-256 est
 * stocké. Un jeton lu dans la base ne permet donc rien.
 */
class NewsletterService
{
    public const CONFIRM_HOURS = 48;

    public static function normalize(string $email): string
    {
        return mb_strtolower(preg_replace('/\s+/u', '', $email) ?? '');
    }

    /**
     * Inscription. La réponse affichée au visiteur est TOUJOURS la même,
     * que l'adresse soit nouvelle, en attente, confirmée ou désinscrite.
     */
    public function subscribe(string $rawEmail, ?string $ip): void
    {
        $email = self::normalize($rawEmail);
        Validator::make(['email' => $email], ['email' => ['required', 'string', 'max:254', 'email:rfc']], [], ['email' => 'adresse email'])->validate();

        $token = null;
        DB::transaction(function () use ($email, $ip, &$token) {
            $sub = NewsletterSubscriber::query()->where('email', $email)->lockForUpdate()->first() ?? new NewsletterSubscriber;
            if ($sub->exists && $sub->status === 'confirmed') {
                return; // Déjà abonné : rien à faire, et rien à révéler.
            }
            // Nouvelle adresse, attente ou ancienne désinscription : nouvelle
            // demande de confirmation (jamais de réactivation silencieuse).
            $token = Str::random(40);
            $sub->forceFill([
                'email' => $email,
                'status' => 'pending',
                'confirm_token_hash' => hash('sha256', $token),
                'confirm_expires_at' => now()->addHours(self::CONFIRM_HOURS),
                'ip_hash' => Audit::hashIp($ip),
            ])->save();
        });

        if ($token !== null) {
            try {
                Mail::to($email)->send(new NewsletterConfirmationMail($token));
            } catch (Throwable $e) {
                // Le visiteur voit la même réponse ; l'échec est journalisé sans l'adresse.
                Log::warning('Échec d\'envoi de la confirmation newsletter', ['error' => $e->getMessage()]);
            }
        }
    }

    /** @return string|null Jeton de désinscription en clair (pour la page de confirmation), ou null si lien invalide/expiré. */
    public function confirm(string $token): ?string
    {
        if (strlen($token) !== 40) {
            return null;
        }

        return DB::transaction(function () use ($token) {
            $sub = NewsletterSubscriber::query()->where('confirm_token_hash', hash('sha256', $token))->lockForUpdate()->first();
            if ($sub === null || $sub->status !== 'pending' || $sub->confirm_expires_at === null || $sub->confirm_expires_at->isPast()) {
                return null;
            }
            $unsubscribe = Str::random(40);
            $sub->forceFill([
                'status' => 'confirmed', 'confirmed_at' => now(), 'unsubscribed_at' => null,
                'confirm_token_hash' => null, 'confirm_expires_at' => null,
                'unsubscribe_token_hash' => hash('sha256', $unsubscribe),
            ])->save();
            SyncNewsletterWithBrevo::dispatch($sub->id)->afterCommit();

            return $unsubscribe;
        });
    }

    public function findByUnsubscribeToken(string $token): ?NewsletterSubscriber
    {
        return strlen($token) === 40 ? NewsletterSubscriber::query()->where('unsubscribe_token_hash', hash('sha256', $token))->first() : null;
    }

    public function unsubscribe(string $token): bool
    {
        $sub = $this->findByUnsubscribeToken($token);
        if ($sub === null) {
            return false;
        }
        if ($sub->status !== 'unsubscribed') {
            $sub->forceFill(['status' => 'unsubscribed', 'unsubscribed_at' => now()])->save();
            SyncNewsletterWithBrevo::dispatch($sub->id);
        }

        return true;
    }

    /** Suppression sur demande RGPD (dashboard, `manage_newsletter`). */
    public function delete(User $user, NewsletterSubscriber $sub): void
    {
        if (! $user->isActive() || ! $user->hasPermission('manage_newsletter')) {
            throw new AuthorizationException('Vous ne pouvez pas gérer la newsletter.');
        }
        $email = $sub->email;
        $sub->delete();
        SyncNewsletterWithBrevo::dispatch(null, $email);
        Audit::record('newsletter.deleted', null, ['subscriber_id' => $sub->id]);
    }

    /** Purge des inscriptions jamais confirmées (cahier § 13.1 : 7 jours). */
    public static function purgePending(): int
    {
        return NewsletterSubscriber::query()->where('status', 'pending')
            ->where('created_at', '<', now()->subDays((int) config('yewtod.retention.newsletter_pending_days', 7)))->delete();
    }
}
