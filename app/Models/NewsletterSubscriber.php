<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Abonné à la newsletter (cahier § 5.6, § 13.1). Les jetons de confirmation
 * et de désinscription ne sont stockés que HACHÉS (SHA-256).
 *
 * @property int $id
 * @property string $email
 * @property string $status
 * @property string|null $confirm_token_hash
 * @property CarbonImmutable|null $confirm_expires_at
 * @property CarbonImmutable|null $confirmed_at
 * @property string|null $unsubscribe_token_hash
 * @property CarbonImmutable|null $unsubscribed_at
 * @property string|null $ip_hash
 * @property CarbonImmutable|null $brevo_synced_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class NewsletterSubscriber extends Model
{
    public const STATUSES = ['pending' => 'En attente', 'confirmed' => 'Confirmé', 'unsubscribed' => 'Désinscrit'];

    /** Rien n'est affectable en masse : tout passe par NewsletterService. */
    protected $fillable = [];

    protected $attributes = [
        'status' => 'pending', 'confirm_token_hash' => null, 'confirm_expires_at' => null, 'confirmed_at' => null,
        'unsubscribe_token_hash' => null, 'unsubscribed_at' => null, 'ip_hash' => null, 'brevo_synced_at' => null,
    ];

    protected function casts(): array
    {
        return [
            'confirm_expires_at' => 'datetime', 'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime', 'brevo_synced_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
