<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property string $description
 */
class Permission extends Model
{
    public $timestamps = false;

    // Les permissions sont créées par le seeder de référence, jamais par l'interface.
    protected $fillable = [];

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
