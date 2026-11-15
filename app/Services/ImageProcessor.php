<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Traitement des images envoyées (cahier § 8.4, § 10 P2).
 *
 * L'image est DÉCODÉE puis RÉENCODÉE en WebP par GD : les métadonnées (EXIF,
 * géolocalisation) disparaissent, et un fichier « polyglotte » (image valide
 * qui cache du HTML/PHP) perd sa charge utile. Le nom est aléatoire.
 * Plusieurs largeurs sont produites pour les attributs srcset.
 */
class ImageProcessor
{
    /** Types autorisés, vérifiés sur le contenu réel (pas l'extension). Pas de SVG. */
    public const MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];

    public const WIDTHS = [480, 960, 1600];

    /**
     * @param  list<int>  $widths
     * @return array{path: string, variants: array<int, string>, width: int, height: int}
     */
    public function store(UploadedFile $file, string $directory, array $widths = self::WIDTHS, string $disk = 'public'): array
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) ?: '';
        if (! in_array($mime, self::MIMES, true)) {
            throw ValidationException::withMessages(['file' => 'Format d\'image non autorisé (JPEG, PNG, WebP, GIF ou AVIF).']);
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw ValidationException::withMessages(['file' => 'Cette image est illisible ou corrompue.']);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $base = trim($directory, '/').'/'.Str::random(40);
        $variants = [];

        foreach ($widths as $target) {
            $w = min($target, $width);
            $h = (int) round($height * $w / max($width, 1));
            $copy = imagecreatetruecolor($w, $h);
            imagealphablending($copy, false);
            imagesavealpha($copy, true);
            imagecopyresampled($copy, $source, 0, 0, 0, 0, $w, $h, $width, $height);
            ob_start();
            imagewebp($copy, null, 82);
            $data = (string) ob_get_clean();
            imagedestroy($copy);

            $path = "{$base}-{$target}.webp";
            Storage::disk($disk)->put($path, $data);
            $variants[$target] = $path;
            if ($w < $target) {
                break; // inutile d'agrandir une petite image
            }
        }
        imagedestroy($source);

        return ['path' => end($variants) ?: '', 'variants' => $variants, 'width' => $width, 'height' => $height];
    }

    /** @param array<int, string>|null $variants */
    public function delete(?string $path, ?array $variants = null, string $disk = 'public'): void
    {
        $paths = array_filter(array_merge([$path], array_values($variants ?? [])));
        if ($path !== null && preg_match('/^(.*)-\d+\.webp$/', $path, $m)) {
            foreach (self::WIDTHS as $w) {
                $paths[] = "{$m[1]}-{$w}.webp";
            }
        }
        Storage::disk($disk)->delete(array_values(array_unique($paths)));
    }

    /** Construit un attribut srcset à partir du chemin de la plus grande variante. */
    public static function srcset(?string $path, string $disk = 'public'): string
    {
        if ($path === null || ! preg_match('/^(.*)-\d+\.webp$/', $path, $m)) {
            return '';
        }

        return collect(self::WIDTHS)
            ->filter(fn (int $w) => Storage::disk($disk)->exists("{$m[1]}-{$w}.webp"))
            ->map(fn (int $w) => Storage::disk($disk)->url("{$m[1]}-{$w}.webp")." {$w}w")
            ->implode(', ');
    }
}
