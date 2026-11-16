<?php

namespace App\Support;

/** Formats d'affichage en français. */
class Format
{
    /** Taille de fichier : « 406 o », « 12,4 Ko », « 3,1 Mo ». */
    public static function fileSize(int $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return number_format($size, $i === 0 ? 0 : 1, ',', "\u{202F}")."\u{202F}".$units[$i];
    }
}
