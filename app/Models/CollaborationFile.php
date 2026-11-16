<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pièce jointe d'une demande (données personnelles) : disque privé, lue
 * uniquement par lien signé de 10 minutes réservé aux gestionnaires.
 *
 * @property int $id
 * @property int $collaboration_id
 * @property string $path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property CarbonImmutable $created_at
 */
class CollaborationFile extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [];

    /** @return BelongsTo<Collaboration, $this> */
    public function collaboration(): BelongsTo
    {
        return $this->belongsTo(Collaboration::class);
    }
}
