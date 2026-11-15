<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $platform
 * @property string $url
 * @property int $position
 */
class SocialLink extends Model
{
    protected $fillable = ['platform', 'url', 'position'];

    /** Icône déduite du nom de la plateforme (cahier § 5.9). */
    public function icon(): string
    {
        $p = mb_strtolower($this->platform);

        return match (true) {
            str_contains($p, 'linkedin') => 'linkedin',
            str_contains($p, 'youtube') => 'youtube',
            str_contains($p, 'instagram') => 'instagram',
            str_contains($p, 'facebook') => 'facebook',
            str_contains($p, 'twitter'), $p === 'x', str_contains($p, 'x.com') => 'x',
            str_contains($p, 'bluesky') => 'bluesky',
            str_contains($p, 'mastodon') => 'mastodon',
            str_contains($p, 'tiktok') => 'tiktok',
            default => 'link',
        };
    }
}
