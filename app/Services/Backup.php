<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use ZipArchive;

/**
 * Sauvegardes chiffrées (cahier § 8.5, § 8.6) : base + fichiers privés et
 * publics, en PHP pur (le mutualisé n'autorise pas toujours `exec`/mysqldump
 * depuis le cron).
 *
 * Format « .ybk » : en-tête `YWTDBK1`, sel (16 o), en-tête secretstream
 * (24 o), puis des blocs [longueur 4 o][chiffré]. Clé dérivée de
 * BACKUP_PASSPHRASE par Argon2id (sodium_crypto_pwhash) ; chiffrement
 * authentifié XChaCha20-Poly1305 par blocs : toute altération est détectée.
 * La phrase de passe est DISTINCTE d'APP_KEY et doit être conservée hors du
 * serveur (sans elle, la sauvegarde est illisible).
 */
class Backup
{
    private const MAGIC = 'YWTDBK1';

    private const CHUNK = 1048576;

    public static function enabled(): bool
    {
        return strlen((string) config('services.backup.passphrase')) >= 16;
    }

    public static function directory(): string
    {
        return (string) (config('services.backup.path') ?: storage_path('app/backups'));
    }

    /** Crée une sauvegarde chiffrée et applique la rotation ; renvoie le chemin du fichier. */
    public function create(): string
    {
        if (! self::enabled()) {
            throw new RuntimeException('Sauvegarde désactivée : renseignez BACKUP_PASSPHRASE (16 caractères minimum) dans .env.');
        }
        $dir = self::directory();
        File::ensureDirectoryExists($dir, 0700);
        $stamp = now()->format('Ymd-His');
        $work = $dir.'/.tmp-'.$stamp;
        File::ensureDirectoryExists($work, 0700);

        try {
            $sql = $work.'/base.sql';
            $tables = $this->dumpDatabase($sql);
            $zipPath = $work.'/archive.zip';
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Impossible de créer l\'archive.');
            }
            $zip->addFile($sql, 'base.sql');
            foreach (['private' => storage_path('app/private'), 'public' => storage_path('app/public')] as $name => $root) {
                $this->addDirectory($zip, $root, 'fichiers/'.$name);
            }
            $zip->addFromString('manifeste.json', (string) json_encode([
                'application' => config('app.name'), 'cree_le' => now()->toIso8601String(), 'tables' => $tables,
                'base' => DB::connection()->getDatabaseName(), 'format' => 1,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $zip->close();

            $target = $dir.'/yewtod-'.$stamp.'.ybk';
            $this->encryptFile($zipPath, $target, (string) config('services.backup.passphrase'));
            @chmod($target, 0600);
        } finally {
            File::deleteDirectory($work);
        }

        $this->rotate();

        return $target;
    }

    /** Déchiffre une sauvegarde vers une archive zip (restauration). */
    public function decrypt(string $source, string $zipTarget, ?string $passphrase = null): void
    {
        $passphrase ??= (string) config('services.backup.passphrase');
        $in = fopen($source, 'rb');
        if ($in === false || fread($in, strlen(self::MAGIC)) !== self::MAGIC) {
            throw new RuntimeException('Ce fichier n\'est pas une sauvegarde Yewtod.');
        }
        $salt = (string) fread($in, SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $header = (string) fread($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
        $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($header, $this->key($passphrase, $salt));
        $out = fopen($zipTarget, 'wb');
        $finished = false;
        while (! feof($in) && ($len = fread($in, 4)) !== false && strlen($len) === 4) {
            $chunk = (string) fread($in, (int) unpack('N', $len)[1]);
            $result = sodium_crypto_secretstream_xchacha20poly1305_pull($state, $chunk);
            if ($result === false) {
                fclose($in);
                fclose($out);
                @unlink($zipTarget);
                throw new RuntimeException('Déchiffrement impossible : phrase de passe incorrecte ou fichier altéré.');
            }
            [$plain, $tag] = $result;
            fwrite($out, $plain);
            if ($tag === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL) {
                $finished = true;
                break;
            }
        }
        fclose($in);
        fclose($out);
        if (! $finished) {
            @unlink($zipTarget);
            throw new RuntimeException('Sauvegarde tronquée : le dernier bloc est absent.');
        }
    }

    /**
     * Importe base.sql dans la connexion courante (une instruction par ligne).
     * Réservé à la restauration manuelle, après confirmation.
     */
    public function importSql(string $sqlFile): int
    {
        $count = 0;
        $handle = fopen($sqlFile, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Fichier SQL illisible.');
        }
        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '' || str_starts_with($line, '--')) {
                continue;
            }
            DB::unprepared($line);
            $count++;
        }
        fclose($handle);

        return $count;
    }

    /**
     * Rotation (cahier § 8.6) : 7 dernières quotidiennes, la plus récente de
     * chacune des 4 dernières semaines et des 6 derniers mois.
     *
     * @return list<string> fichiers supprimés
     */
    public function rotate(): array
    {
        $files = collect(File::glob(self::directory().'/yewtod-*.ybk'))
            ->mapWithKeys(fn ($f) => [$f => CarbonImmutable::createFromFormat('Ymd-His', substr(basename($f), 7, 15))])
            ->filter()->sortDesc();
        $keep = $files->keys()->take(7)->all();
        foreach (['o-W' => 4, 'Y-m' => 6] as $format => $n) {
            $keep = array_merge($keep, $files->groupBy(fn ($d) => $d->format($format), true)->take($n)->map(fn ($g) => $g->keys()->first())->values()->all());
        }
        $deleted = [];
        foreach ($files->keys() as $file) {
            if (! in_array($file, $keep, true)) {
                File::delete($file);
                $deleted[] = $file;
            }
        }

        return $deleted;
    }

    /** @return array<string, int> nombre de lignes par table */
    private function dumpDatabase(string $target): array
    {
        $pdo = DB::connection()->getPdo();
        $out = fopen($target, 'wb');
        fwrite($out, '-- Sauvegarde Yewtod SS du '.now()->toIso8601String()."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
        $counts = [];
        foreach (DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'") as $row) {
            $table = (string) array_values((array) $row)[0];
            $quoted = '`'.str_replace('`', '``', $table).'`';
            $create = (array) DB::selectOne('SHOW CREATE TABLE '.$quoted);
            fwrite($out, 'DROP TABLE IF EXISTS '.$quoted.";\n".str_replace("\n", ' ', (string) array_values($create)[1]).";\n");
            $counts[$table] = 0;
            $stmt = $pdo->query('SELECT * FROM '.$quoted, PDO::FETCH_NUM);
            if ($stmt === false) {
                continue;
            }
            while (($values = $stmt->fetch()) !== false) {
                $cells = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $values);
                fwrite($out, 'INSERT INTO '.$quoted.' VALUES ('.implode(',', $cells).");\n");
                $counts[$table]++;
            }
        }
        fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($out);

        return $counts;
    }

    private function addDirectory(ZipArchive $zip, string $root, string $prefix): void
    {
        if (! is_dir($root)) {
            return;
        }
        foreach (File::allFiles($root, true) as $file) {
            if (str_starts_with($file->getRealPath(), self::directory())) {
                continue;
            }
            $zip->addFile($file->getRealPath(), $prefix.'/'.str_replace('\\', '/', $file->getRelativePathname()));
        }
    }

    private function encryptFile(string $source, string $target, string $passphrase): void
    {
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($this->key($passphrase, $salt));
        $in = fopen($source, 'rb');
        $out = fopen($target, 'wb');
        fwrite($out, self::MAGIC.$salt.$header);
        $size = filesize($source) ?: 0;
        $read = 0;
        do {
            $chunk = (string) fread($in, self::CHUNK);
            $read += strlen($chunk);
            $tag = $read >= $size ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE;
            $cipher = sodium_crypto_secretstream_xchacha20poly1305_push($state, $chunk, '', $tag);
            fwrite($out, pack('N', strlen($cipher)).$cipher);
        } while ($tag !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL);
        fclose($in);
        fclose($out);
    }

    private function key(string $passphrase, string $salt): string
    {
        return sodium_crypto_pwhash(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES, $passphrase, $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE, SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13);
    }
}
