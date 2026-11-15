<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $email
 * @property int $role_id
 * @property string $token_hash
 * @property InvitationStatus $status
 * @property int|null $invited_by
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $accepted_at
 * @property CarbonImmutable|null $revoked_at
 * @property CarbonImmutable $created_at
 * @property-read Role $role
 * @property-read User|null $inviter
 */
class Invitation extends Model
{
    public const VALIDITY_DAYS = 7;

    // Tout est écrit par InvitationService : rien n'est « fillable » (R4, R7).
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'sent_at' => 'datetime',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Role, $this> */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /** @param Builder<Invitation> $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', InvitationStatus::Pending->value);
    }

    public function isUsable(): bool
    {
        return $this->status === InvitationStatus::Pending && $this->expires_at->isFuture();
    }

    /** Statut affiché : une invitation en attente dont la date est passée est « expirée ». */
    public function displayStatus(): InvitationStatus
    {
        return $this->status === InvitationStatus::Pending && $this->expires_at->isPast()
            ? InvitationStatus::Expired
            : $this->status;
    }
}
