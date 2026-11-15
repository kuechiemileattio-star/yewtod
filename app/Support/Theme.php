<?php

namespace App\Support;

use App\Models\User;

/**
 * Préférence de thème (cahier § 9) : compte du membre en priorité, sinon
 * cookie de préférence du visiteur (non chiffré, sans suivi), sinon « système ».
 * La classe est posée côté serveur sur <html> : pas de flash blanc.
 */
class Theme
{
    public const CHOICES = ['light', 'dark', 'system'];

    public static function current(): string
    {
        $user = auth()->user();
        if ($user instanceof User && in_array($user->theme_preference, self::CHOICES, true)) {
            return $user->theme_preference;
        }

        $cookie = request()->cookie('theme');

        return is_string($cookie) && in_array($cookie, self::CHOICES, true) ? $cookie : 'system';
    }

    public static function htmlClass(): string
    {
        return self::current() === 'dark' ? 'dark' : '';
    }
}
