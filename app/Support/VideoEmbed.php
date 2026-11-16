<?php

namespace App\Support;

use App\Rules\HttpUrl;

/**
 * Lecture d'une URL de vidéo saisie dans l'éditeur (cahier § 3 « Vidéos »).
 *
 * YouTube : lecteur « youtube-nocookie » (pas de cookie publicitaire avant
 * lecture) arrêté à 2 minutes avec le paramètre `end=120` (« Aperçu
 * gratuit »), puis bouton « Continuer sur YouTube ». Vimeo : lecteur officiel.
 * MP4/WebM : balise <video>. L'identifiant est extrait par expression
 * régulière stricte : rien d'autre que [A-Za-z0-9_-] n'entre dans l'URL
 * d'intégration.
 */
class VideoEmbed
{
    public const PREVIEW_SECONDS = 120;

    /** @return array{kind: string, embed: string, watch: string, thumb: string|null}|null */
    public static function parse(?string $url): ?array
    {
        if ($url === null || ! HttpUrl::isSafe($url)) {
            return null;
        }

        if (preg_match('~^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            $id = $m[1];

            return [
                'kind' => 'youtube',
                'embed' => 'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0&modestbranding=1&start=0&end='.self::PREVIEW_SECONDS,
                'watch' => 'https://www.youtube.com/watch?v='.$id,
                'thumb' => 'https://i.ytimg.com/vi/'.$id.'/hqdefault.jpg',
            ];
        }

        if (preg_match('~^https?://(?:www\.|player\.)?vimeo\.com/(?:video/)?(\d{6,12})~', $url, $m)) {
            return ['kind' => 'vimeo', 'embed' => 'https://player.vimeo.com/video/'.$m[1].'?dnt=1', 'watch' => 'https://vimeo.com/'.$m[1], 'thumb' => null];
        }

        if (preg_match('~\.(mp4|webm)(\?.*)?$~i', $url)) {
            return ['kind' => 'file', 'embed' => $url, 'watch' => $url, 'thumb' => null];
        }

        return null;
    }
}
