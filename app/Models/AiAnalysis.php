<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $status
 * @property array<string, mixed>|null $result
 * @property string|null $error
 * @property int|null $requested_by
 * @property-read Model $analyzable
 */
class AiAnalysis extends Model
{
    protected $fillable = [];

    protected $attributes = ['status' => 'pending', 'result' => null, 'error' => null, 'requested_by' => null];

    protected function casts(): array
    {
        return ['result' => 'array'];
    }

    /** @return MorphTo<Model, $this> */
    public function analyzable(): MorphTo
    {
        return $this->morphTo();
    }
}
