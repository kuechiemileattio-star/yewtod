<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $eventable_type
 * @property int $eventable_id
 * @property string $kind
 * @property CarbonImmutable $created_at
 */
class ContentEvent extends Model
{
    public const KINDS = ['view', 'download'];

    public const UPDATED_AT = null;

    /** Aucune écriture de masse : les événements ne sont créés que par EventCounter. */
    protected $fillable = [];

    /** @return MorphTo<Model, $this> */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }
}
