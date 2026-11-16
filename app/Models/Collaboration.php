<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Demande de collaboration (cahier § 2.7, § 5.5). La description est
 * chiffrée en base (cast « encrypted », clé APP_KEY) : une copie de la base
 * seule ne suffit pas à la lire.
 *
 * @property int $id
 * @property string $name
 * @property string|null $organization
 * @property string $email
 * @property string $subject
 * @property string $kind
 * @property string $description
 * @property string $status
 * @property string|null $ip_hash
 * @property int|null $handled_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 * @property-read Collection<int, CollaborationFile> $files
 */
class Collaboration extends Model
{
    public const KINDS = [
        'recherche' => 'Proposition de recherche', 'livre' => 'Proposition de livre', 'article' => 'Proposition d\'article',
        'intervention' => 'Invitation à intervenir', 'partenariat' => 'Partenariat', 'interview' => 'Interview',
        'etude' => 'Participation à une étude', 'generale' => 'Demande générale',
    ];

    public const STATUSES = ['new' => 'Nouveau', 'in_progress' => 'En cours', 'archived' => 'Archivé'];

    /** Le visiteur ne choisit ni le statut ni la date (cahier § 5.5). */
    protected $fillable = ['name', 'organization', 'email', 'subject', 'kind', 'description'];

    protected $attributes = ['status' => 'new', 'organization' => null, 'ip_hash' => null, 'handled_by' => null];

    protected function casts(): array
    {
        return ['description' => 'encrypted'];
    }

    /** @return HasMany<CollaborationFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(CollaborationFile::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
