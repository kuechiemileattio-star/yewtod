<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Avis d'un lecteur (cahier § 5.4). Le texte n'est JAMAIS modifiable après
 * coup : ni par l'interface, ni par la modération, qui ne change que le statut.
 *
 * @property int $id
 * @property string $reviewable_type
 * @property int $reviewable_id
 * @property string $name
 * @property string $body
 * @property string $status
 * @property string|null $ip_hash
 * @property int|null $moderated_by
 * @property CarbonImmutable|null $moderated_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Content|Book|null $reviewable
 * @property-read User|null $moderator
 */
class Review extends Model
{
    public const STATUSES = ['pending' => 'En attente', 'approved' => 'Approuvé', 'rejected' => 'Rejeté'];

    /** Seuls le nom et le texte viennent du visiteur ; statut, IP, modération sont posés par le serveur. */
    protected $fillable = ['name', 'body'];

    protected $attributes = ['status' => 'pending', 'ip_hash' => null, 'moderated_by' => null, 'moderated_at' => null];

    protected function casts(): array
    {
        return ['moderated_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Le texte et l'auteur d'un avis ne changent plus une fois enregistrés.
        static::updating(function (Review $review) {
            if ($review->isDirty(['name', 'body', 'reviewable_type', 'reviewable_id'])) {
                throw new \LogicException('Le texte d\'un avis n\'est jamais modifiable.');
            }
        });
    }

    /** @return MorphTo<Model, $this> */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Seuls les avis approuvés sont publics (cahier § 8.6 « lecture publique »).
     *
     * @param  Builder<Review>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
