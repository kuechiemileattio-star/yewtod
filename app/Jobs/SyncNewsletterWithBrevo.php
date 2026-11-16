<?php

namespace App\Jobs;

use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Synchronisation avec la liste Brevo (cahier § 13.1.3), en file d'attente
 * (traitée par le cron sur le mutualisé). Sans clé Brevo, la tâche ne fait
 * rien (décision 8) : collecte et double opt-in fonctionnent quand même.
 *
 * - abonné confirmé  : ajouté / mis à jour dans la liste ;
 * - désinscrit ou supprimé : retiré de la liste.
 */
class SyncNewsletterWithBrevo implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(public readonly ?int $subscriberId, public readonly ?string $deletedEmail = null) {}

    public static function enabled(): bool
    {
        return filled(config('services.brevo.key')) && filled(config('services.brevo.list_id'));
    }

    public function handle(): void
    {
        if (! self::enabled()) {
            return;
        }
        $sub = $this->subscriberId ? NewsletterSubscriber::query()->find($this->subscriberId) : null;
        $email = $sub !== null ? $sub->email : $this->deletedEmail;
        if ($email === null) {
            return;
        }
        $listId = (int) config('services.brevo.list_id');
        $http = Http::withHeaders(['api-key' => (string) config('services.brevo.key'), 'accept' => 'application/json'])
            ->timeout(15)->baseUrl((string) config('services.brevo.endpoint'));

        $response = $sub !== null && $sub->status === 'confirmed'
            ? $http->post('/contacts', ['email' => $email, 'listIds' => [$listId], 'updateEnabled' => true])
            : $http->post('/contacts/lists/'.$listId.'/contacts/remove', ['emails' => [$email]]);

        // 404 au retrait = contact absent de Brevo : rien à faire.
        if ($response->failed() && $response->status() !== 404) {
            Log::warning('Synchronisation Brevo en échec', ['status' => $response->status()]);
            $response->throw();
        }
        $sub?->forceFill(['brevo_synced_at' => now()])->save();
    }
}
