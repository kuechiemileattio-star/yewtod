<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Toute URL saisie est limitée à http/https (cahier § 8.4, bug n° 12) :
 * `javascript:`, `data:`, `vbscript:`… sont refusés.
 */
class HttpUrl implements ValidationRule
{
    public function __construct(private readonly bool $httpsOnly = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! self::isSafe($value, $this->httpsOnly)) {
            $fail($this->httpsOnly ? 'Le lien doit commencer par https://.' : 'Le lien doit commencer par http:// ou https://.');
        }
    }

    public static function isSafe(mixed $value, bool $httpsOnly = false): bool
    {
        if (! is_string($value) || strlen($value) > 2000 || preg_match('/[\x00-\x1F\x7F\s]/', $value)) {
            return false;
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        $host = parse_url($value, PHP_URL_HOST);

        return in_array($scheme, $httpsOnly ? ['https'] : ['http', 'https'], true)
            && is_string($host) && $host !== ''
            && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
