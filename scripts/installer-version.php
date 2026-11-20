<?php

/*
 * Installateur d'une version de Yewtod SS sur l'hébergement (décision D77).
 * Envoyé par scripts/deployer-ftp.sh dans <site>/_deploiement/installer.php,
 * puis lancé dans le Terminal web LWS, depuis le dossier du site :
 *
 *   php _deploiement/installer.php yewtod-<commit>.zip <sha256>   # installe
 *   php _deploiement/installer.php --retour                       # revient à la version précédente
 *
 * Pourquoi : le FTP de LWS est bridé ; envoyer 8 000 fichiers un par un est
 * interminable. On envoie UNE archive, puis ce script, côté serveur :
 *   1. vérifie l'empreinte de l'archive (envoi complet et intact) ;
 *   2. met le site en maintenance (même fichier que « php artisan down ») ;
 *   3. extrait l'archive dans _deploiement/nouvelle ;
 *   4. échange chaque élément de premier niveau (app, vendor, public…) :
 *      l'ancien part dans _deploiement/precedente, le nouveau prend sa place
 *      (renommages instantanés, aucun fichier à moitié copié) ;
 *   5. complète storage/ (dossiers, .htaccess) SANS jamais toucher aux
 *      fichiers du site (envois, sauvegardes, journaux), ni au .env ;
 *   6. recrée le lien public/storage.
 * Le site reste en maintenance : migrations, caches et « php artisan up »
 * suivent (commandes affichées à la fin).
 *
 * Autonome : aucune dépendance à Laravel (vendor/ est justement remplacé).
 */

const GARDES = ['.env', 'storage', '_deploiement', '.well-known', 'cgi-bin'];

$dir = __DIR__;
$base = dirname($dir);

function sortie(string $message, int $code = 1): never
{
    fwrite($code === 0 ? STDOUT : STDERR, $message.PHP_EOL);
    exit($code);
}

function info(string $message): void
{
    echo '  '.$message.PHP_EOL;
}

function supprimer(string $path): void
{
    if (is_link($path) || is_file($path)) {
        unlink($path);

        return;
    }
    if (! is_dir($path)) {
        return;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
        /** @var SplFileInfo $item */
        $item->isDir() && ! $item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($path);
}

/** Crée les dossiers manquants et met à jour .htaccess / .gitignore ; ne supprime ni ne remplace aucun autre fichier. */
function completerStorage(string $from, string $to): void
{
    if (! is_dir($to)) {
        mkdir($to, 0755, true);
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
        /** @var SplFileInfo $item */
        $target = $to.substr($item->getPathname(), strlen($from));
        if ($item->isDir()) {
            is_dir($target) || mkdir($target, 0755, true);
        } elseif (in_array($item->getFilename(), ['.htaccess', '.gitignore'], true)) {
            copy($item->getPathname(), $target);
        }
    }
}

function lienStorage(string $base): void
{
    $link = $base.'/public/storage';
    if (file_exists($link) || is_link($link)) {
        return;
    }
    if (function_exists('symlink') && @symlink('../storage/app/public', $link)) {
        info('Lien public/storage recréé.');
    } else {
        info('Lien public/storage absent : lancez « php artisan storage:link ».');
    }
}

function maintenance(string $base): void
{
    if (is_file($base.'/artisan') && is_dir($base.'/storage/framework') && ! is_file($base.'/storage/framework/down')) {
        file_put_contents($base.'/storage/framework/down', json_encode(['except' => [], 'redirect' => null, 'retry' => 60, 'refresh' => null, 'secret' => null, 'status' => 503, 'template' => null]));
        info('Site en maintenance (503).');
    }
}

/** Échange les éléments de premier niveau de $source avec ceux du site ; les anciens partent dans $stock. */
function echanger(string $source, string $base, string $stock): array
{
    $entries = array_values(array_diff(scandir($source) ?: [], ['.', '..']));
    $moved = [];
    foreach ($entries as $entry) {
        if (in_array($entry, GARDES, true) || str_starts_with($entry, '.env')) {
            continue;
        }
        if (file_exists($base.'/'.$entry) || is_link($base.'/'.$entry)) {
            rename($base.'/'.$entry, $stock.'/'.$entry);
        }
        rename($source.'/'.$entry, $base.'/'.$entry);
        $moved[] = $entry;
    }

    return $moved;
}

$args = array_slice($argv, 1);
if (($args[0] ?? '') === '--retour') {
    $prev = $dir.'/precedente';
    if (! is_dir($prev) || ! is_file($prev.'/artisan')) {
        sortie('Aucune version précédente à restaurer.');
    }
    echo 'Retour à la version précédente'.PHP_EOL;
    maintenance($base);
    $cancelled = $dir.'/annulee';
    supprimer($cancelled);
    mkdir($cancelled, 0755);
    $moved = echanger($prev, $base, $cancelled);
    supprimer($prev);
    rename($cancelled, $prev); // la version annulée devient « précédente » : on peut revenir encore
    lienStorage($base);
    info(count($moved).' éléments restaurés : '.implode(', ', $moved));
    echo PHP_EOL.'Si la version annulée avait appliqué des migrations, annulez-les AVANT (php artisan migrate:rollback --step=N) ou restaurez la sauvegarde.'.PHP_EOL;
    echo 'Ensuite : php artisan optimize:clear && php artisan optimize, puis php artisan up'.PHP_EOL;
    exit(0);
}

[$archiveName, $expected] = $args + [null, null];
if (! is_string($archiveName) || ! is_string($expected) || ! preg_match('/^[a-f0-9]{64}$/', $expected)) {
    sortie("Usage : php _deploiement/installer.php <archive.zip> <sha256>\n        php _deploiement/installer.php --retour");
}
$archive = $dir.'/'.basename($archiveName);
if (! is_file($archive)) {
    sortie("Archive introuvable : {$archive}");
}
if (! class_exists(ZipArchive::class)) {
    sortie('Extension PHP « zip » absente : activez-la dans le panneau (Configuration PHP).');
}

echo 'Installation de '.basename($archive).PHP_EOL;
if (! hash_equals($expected, (string) hash_file('sha256', $archive))) {
    sortie('ARRÊT : empreinte de l\'archive incorrecte (envoi incomplet ou altéré). Relancez le déploiement.');
}
info('Empreinte de l\'archive vérifiée.');

$new = $dir.'/nouvelle';
supprimer($new);
mkdir($new, 0755);
$zip = new ZipArchive;
if ($zip->open($archive) !== true || ! $zip->extractTo($new)) {
    sortie('ARRÊT : extraction impossible (espace disque ?). Rien n\'a été modifié sur le site.');
}
$zip->close();
foreach (['artisan', 'public/index.php', 'vendor/autoload.php', '.htaccess', 'VERSION'] as $required) {
    if (! file_exists($new.'/'.$required)) {
        supprimer($new);
        sortie("ARRÊT : archive incomplète ({$required} manquant). Rien n'a été modifié sur le site.");
    }
}
info('Archive extraite.');

maintenance($base);
$prev = $dir.'/precedente';
supprimer($prev);
mkdir($prev, 0755);
$moved = echanger($new, $base, $prev);
// Page d'attente de LWS (première installation) : rangée avec l'ancienne version.
foreach (['default_index.html', 'index.html'] as $page) {
    if (is_file($base.'/'.$page)) {
        rename($base.'/'.$page, $prev.'/'.$page);
        info("Page d'attente {$page} retirée.");
    }
}
if (is_dir($new.'/storage')) {
    completerStorage($new.'/storage', $base.'/storage');
    info('storage/ complété (aucun fichier du site touché).');
}
// La maintenance a pu être activée avant l'échange : on s'assure que le fichier est toujours là.
maintenance($base);
lienStorage($base);
supprimer($new);
unlink($archive);
file_put_contents($dir.'/derniere-installation.txt', date('c').' '.basename($archive).PHP_EOL, FILE_APPEND);

info(count($moved).' éléments remplacés ; version précédente gardée dans _deploiement/precedente.');
echo PHP_EOL.trim((string) file_get_contents($base.'/VERSION')).PHP_EOL;
echo 'Le site est en MAINTENANCE. Suite : migrations (si annoncées), caches, vérification, puis « php artisan up ».'.PHP_EOL;
