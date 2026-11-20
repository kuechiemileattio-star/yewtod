<?php

namespace App\Support;

/**
 * Webmail de l'équipe (décision D72). L'URL vient de WEBMAIL_URL ; une valeur
 * vide ou qui n'est pas une URL https désactive proprement la fonction (lien
 * masqué, route en 404) : jamais de redirection vers une adresse arbitraire.
 */
class Webmail
{
    public static function url(): ?string
    {
        $url = trim((string) config('yewtod.webmail_url'));
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return $url;
    }

    public static function enabled(): bool
    {
        return self::url() !== null;
    }
}
