<?php

namespace App\Support;

/**
 * Export CSV sûr (cahier § 4.5, bug n° 11).
 *
 * - Séparateur « ; » (Excel en français), guillemets doublés, retours à la
 *   ligne conservés DANS la cellule entre guillemets : plus de colonnes décalées.
 * - Injection de formule : toute cellule commençant par = + - @ (ou par une
 *   tabulation / un retour chariot, qui peuvent précéder une formule) est
 *   préfixée d'une apostrophe, ce qui force Excel et LibreOffice à la lire
 *   comme du texte.
 * - BOM UTF-8 pour que les accents s'affichent correctement dans Excel.
 */
class SafeCsv
{
    public const BOM = "\xEF\xBB\xBF";

    public static function cell(mixed $value): string
    {
        $text = (string) $value;
        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            $text = "'".$text;
        }

        return '"'.str_replace('"', '""', $text).'"';
    }

    /** @param list<mixed> $cells */
    public static function row(array $cells): string
    {
        return implode(';', array_map(self::cell(...), $cells))."\r\n";
    }
}
