<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $password
 */
class PasswordHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['password'];

    protected $hidden = ['password'];
}
