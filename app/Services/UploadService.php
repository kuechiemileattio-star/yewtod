<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Envois de fichiers (cahier § 8.4) : type vérifié sur le CONTENU (finfo +
 * signature), taille bornée par usage, nom aléatoire, documents sur le
 * disque PRIVÉ (servis par un contrôleur qui vérifie la visibilité).
 */
class UploadService
{
    public const MAX_COVER_KB = 5 * 1024;

    public const MAX_DOCUMENT_KB = 50 * 1024;

    public const MAX_MEDIA_KB = 20 * 1024;

    public const MAX_ATTACHMENT_KB = 10 * 1024;

    public const MAX_CSV_KB = 2 * 1024;

    /** Types acceptés pour les fichiers téléchargeables (expérimentations, médiathèque). */
    public const DOWNLOAD_MIMES = [
        'application/pdf' => 'pdf', 'application/zip' => 'zip', 'text/csv' => 'csv', 'text/plain' => 'txt',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.oasis.opendocument.text' => 'odt', 'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'application/json' => 'json',
    ];

    public function __construct(private readonly ImageProcessor $images) {}

    /** @return array{path: string, variants: array<int, string>, width: int, height: int} */
    public function cover(UploadedFile $file, string $directory = 'covers'): array
    {
        $this->assertSize($file, self::MAX_COVER_KB);

        return $this->images->store($file, $directory);
    }

    /** @return array{path: string, name: string, size: int} */
    public function pdf(UploadedFile $file, string $directory = 'documents', int $maxKb = self::MAX_DOCUMENT_KB): array
    {
        $this->assertSize($file, $maxKb);
        if (! self::isPdf($file->getRealPath())) {
            throw ValidationException::withMessages(['file' => 'Le fichier n\'est pas un PDF valide.']);
        }

        return $this->putPrivate($file, $directory, 'pdf');
    }

    /** @return array{path: string, name: string, size: int} */
    public function download(UploadedFile $file, string $directory = 'downloads', int $maxKb = self::MAX_DOCUMENT_KB): array
    {
        $this->assertSize($file, $maxKb);
        $mime = $this->mime($file);
        if (! isset(self::DOWNLOAD_MIMES[$mime])) {
            throw ValidationException::withMessages(['file' => 'Type de fichier non autorisé.']);
        }
        if ($mime === 'application/pdf' && ! self::isPdf($file->getRealPath())) {
            throw ValidationException::withMessages(['file' => 'Le fichier n\'est pas un PDF valide.']);
        }

        return $this->putPrivate($file, $directory, self::DOWNLOAD_MIMES[$mime]);
    }

    /** Signature `%PDF-` contrôlée sur les premiers octets (cahier § 5.8). */
    public static function isPdf(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $head = (string) fread($handle, 1024);
        fclose($handle);

        return str_contains($head, '%PDF-') && (new \finfo(FILEINFO_MIME_TYPE))->file($path) === 'application/pdf';
    }

    /**
     * Analyse d'un CSV pour les visualisations : 1re colonne = catégories,
     * colonnes suivantes = séries. Gère guillemets et retours à la ligne dans
     * les champs ; détecte « ; » ou « , ». Bornes : 500 lignes, 20 séries.
     *
     * @return array{labels: list<string>, series: list<array{name: string, values: list<float|null>}>}
     */
    public function csv(UploadedFile $file): array
    {
        $this->assertSize($file, self::MAX_CSV_KB);
        if (! in_array($this->mime($file), ['text/csv', 'text/plain', 'application/csv'], true)) {
            throw ValidationException::withMessages(['file' => 'Le fichier doit être un CSV.']);
        }

        return self::parseCsv((string) file_get_contents($file->getRealPath()));
    }

    /** @return array{labels: list<string>, series: list<array{name: string, values: list<float|null>}>} */
    public static function parseCsv(string $raw): array
    {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $firstLine = strtok($raw, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $raw);
        rewind($handle);
        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false && count($rows) < 501) {
            if ($row === [null]) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($handle);

        if (count($rows) < 2) {
            throw ValidationException::withMessages(['file' => 'Le CSV doit contenir un en-tête et au moins une ligne.']);
        }

        $header = array_slice(array_shift($rows), 0, 21);
        $series = [];
        foreach (array_slice($header, 1) as $i => $name) {
            $series[] = [
                'name' => Str::limit(trim((string) $name), 120, ''),
                'values' => array_map(function (array $row) use ($i) {
                    $v = str_replace([' ', ','], ['', '.'], trim((string) ($row[$i + 1] ?? '')));

                    return is_numeric($v) ? (float) $v : null;
                }, $rows),
            ];
        }

        return [
            'labels' => array_map(fn (array $r) => Str::limit(trim((string) ($r[0] ?? '')), 120, ''), $rows),
            'series' => $series,
        ];
    }

    private function mime(UploadedFile $file): string
    {
        return (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) ?: '';
    }

    private function assertSize(UploadedFile $file, int $maxKb): void
    {
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages(['file' => 'Fichier trop volumineux ('.round($maxKb / 1024).' Mo maximum).']);
        }
    }

    /** @return array{path: string, name: string, size: int} */
    private function putPrivate(UploadedFile $file, string $directory, string $extension): array
    {
        $path = trim($directory, '/').'/'.Str::random(40).'.'.$extension;
        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));
        $name = Str::limit(preg_replace('/[^\pL\pN ._-]/u', '', $file->getClientOriginalName()) ?: 'document.'.$extension, 200, '');

        return ['path' => $path, 'name' => $name, 'size' => (int) $file->getSize()];
    }
}
