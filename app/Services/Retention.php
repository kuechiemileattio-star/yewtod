<?php

namespace App\Services;

use App\Enums\InvitationStatus;
use App\Models\ContentEvent;
use App\Models\Invitation;
use Illuminate\Support\Facades\DB;

/**
 * Durées de conservation (cahier § 8.5, § 8.6 « minimisation et rétention »).
 * Exécuté chaque nuit par `yewtod:purge` ; chaque durée est réglable dans
 * config/yewtod.php (et .env pour les principales).
 */
class Retention
{
    /** Invitations closes (acceptées, expirées, révoquées) gardées 90 jours pour l'historique. */
    public const INVITATIONS_DAYS = 90;

    /** @return array<string, int> */
    public static function run(): array
    {
        $r = config('yewtod.retention');

        // Les invitations en attente échues passent « expirées » (statut cohérent en base)…
        $expired = Invitation::query()->where('status', InvitationStatus::Pending->value)->where('expires_at', '<', now())
            ->update(['status' => InvitationStatus::Expired->value]);
        // … puis les invitations closes anciennes sont supprimées (le jeton haché disparaît avec elles).
        $invitations = Invitation::query()->where('status', '<>', InvitationStatus::Pending->value)
            ->where('updated_at', '<', now()->subDays(self::INVITATIONS_DAYS))->delete();

        return [
            'collaborations' => CollaborationService::purge((int) $r['collaborations_months']),
            'newsletter_en_attente' => NewsletterService::purgePending(),
            'invitations_expirees' => $expired,
            'invitations_supprimees' => (int) $invitations,
            'journal_audit' => DB::table('activity_log')->where('created_at', '<', now()->subMonths((int) $r['activity_log_months']))->delete(),
            'evenements' => ContentEvent::query()->where('created_at', '<', now()->subMonths((int) $r['content_events_months']))->delete(),
        ];
    }
}
