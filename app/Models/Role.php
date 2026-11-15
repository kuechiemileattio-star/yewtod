<?php

namespace App\Models;

use App\Enums\Permission as PermissionKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property bool $is_system
 * @property-read Collection<int, Permission> $permissions
 */
class Role extends Model
{
    public const SUPER_ADMIN = 'super-admin';

    // `is_system` est volontairement absent : il n'est jamais modifiable (R8).
    protected $fillable = ['name', 'slug', 'description'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /** @return BelongsToMany<Permission, $this> */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === self::SUPER_ADMIN;
    }

    /** @return list<string> */
    public function permissionKeys(): array
    {
        return $this->permissions->pluck('key')->values()->all();
    }

    public function hasPermission(PermissionKey|string $permission): bool
    {
        $key = $permission instanceof PermissionKey ? $permission->value : $permission;

        return $this->isSuperAdmin() || in_array($key, $this->permissionKeys(), true);
    }
}
