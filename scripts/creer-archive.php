<?php

/*
 * Crée l'archive zip du paquet de déploiement (décision D77).
 *
 *   php scripts/creer-archive.php build/paquet build/yewtod-<commit>.zip
 *
 * Fichiers cachés inclus (.htaccess), liens symboliques ignorés (vendor/bin :
 * inutiles en ligne et non pris en charge par le FTP). Affiche l'empreinte
 * SHA-256 de l'archive, vérifiée par l'installateur avant toute extraction.
 */

[$script, $source, $archive] = $argv + [null, null, null];
if (! is_string($source) || ! is_dir($source) || ! is_string($archive)) {
    fwrite(STDERR, "Usage : php scripts/creer-archive.php <dossier> <archive.zip>\n");
    exit(1);
}

$source = rtrim((string) realpath($source), '/');
@unlink($archive);
$zip = new ZipArchive;
if ($zip->open($archive, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    fwrite(STDERR, "Impossible de créer {$archive}\n");
    exit(1);
}

$files = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);
foreach ($iterator as $item) {
    /** @var SplFileInfo $item */
    if ($item->isLink()) {
        continue;
    }
    $relative = substr($item->getPathname(), strlen($source) + 1);
    if ($item->isDir()) {
        $zip->addEmptyDir($relative);
    } else {
        $zip->addFile($item->getPathname(), $relative);
        $files++;
    }
}
$zip->close();

echo $files.' fichiers ; '.round(filesize($archive) / 1048576, 1).' Mo ; sha256 '.hash_file('sha256', $archive).PHP_EOL;
