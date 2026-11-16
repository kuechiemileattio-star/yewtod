<?php

namespace App\Support;

use App\Services\HtmlSanitizer;

/**
 * Corps d'une section prêt à afficher (cahier § 2.4) :
 *  - HTML (éditeur riche) : RE-nettoyé à l'affichage par HTML Purifier.
 *    Il l'a déjà été à l'enregistrement ; ce second passage est une défense
 *    en profondeur au cas où une ligne aurait été écrite en base autrement
 *    (seeder, ancienne version, intervention manuelle).
 *  - Texte brut : structuré automatiquement (lignes courtes = intertitres,
 *    lignes « • » ou « - » = puces), chaque morceau étant ÉCHAPPÉ.
 */
class TextStructurer
{
    public static function toHtml(?string $text): string
    {
        $text = (string) $text;
        if (trim($text) === '') {
            return '';
        }
        if ($text !== strip_tags($text)) {
            return app(HtmlSanitizer::class)->clean($text);
        }

        $html = '';
        /** @var list<string> $list */
        $list = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if (preg_match('/^[•\-\*]\s+(.+)$/u', $line, $m)) {
                $list[] = $m[1];

                continue;
            }
            $html .= self::listHtml($list);
            $list = [];
            if ($line === '') {
                continue;
            }
            $short = mb_strlen($line) <= 70 && ! preg_match('/[.!?…:;,]$/u', $line);
            $html .= $short ? '<h3>'.e($line).'</h3>' : '<p>'.e($line).'</p>';
        }
        $html .= self::listHtml($list);

        return $html;
    }

    /** @param list<string> $items */
    private static function listHtml(array $items): string
    {
        return $items === [] ? '' : '<ul>'.implode('', array_map(fn ($li) => '<li>'.e($li).'</li>', $items)).'</ul>';
    }
}
