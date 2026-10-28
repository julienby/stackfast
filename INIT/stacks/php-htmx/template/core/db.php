<?php
// SQLite : la base de l'app (data/app.sqlite, ou DB_PATH). Ouverte une fois par processus (une fois pour toutes en
// mode worker), schéma tenu à jour par migrations/*.sql, numérotées, appliquées dans l'ordre (PRAGMA user_version).
declare(strict_types=1);

namespace Core;

function db(): \PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new \PDO('sqlite:' . env('DB_PATH', APP . '/data/app.sqlite'), null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        // WAL : les lectures ne bloquent pas l'écriture ; busy_timeout : on attend un verrou au lieu d'échouer.
        $pdo->exec('PRAGMA journal_mode = WAL; PRAGMA synchronous = NORMAL; PRAGMA busy_timeout = 5000; PRAGMA foreign_keys = ON');
        migrate($pdo);
    }
    return $pdo;
}

/** Applique les migrations pas encore passées, chacune dans sa transaction. */
function migrate(\PDO $pdo): void
{
    $files = glob(APP . '/migrations/*.sql') ?: [];
    sort($files);
    $done = (int) $pdo->query('PRAGMA user_version')->fetchColumn();
    foreach (array_slice($files, $done) as $i => $file) {
        $pdo->beginTransaction();
        try {
            $pdo->exec((string) file_get_contents($file));
            $pdo->exec('PRAGMA user_version = ' . ($done + $i + 1));
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw new \RuntimeException('migration ' . basename($file) . ' : ' . $e->getMessage(), 0, $e);
        }
    }
}

/** Toutes les lignes : db_all('SELECT * FROM items WHERE done = ?', [0]). Toujours des paramètres, jamais de concaténation. */
function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

/** Une ligne, ou null. */
function db_one(string $sql, array $params = []): ?array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

/** INSERT / UPDATE / DELETE. Renvoie l'id inséré (INSERT) ou le nombre de lignes touchées. */
function db_run(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return str_starts_with(ltrim(strtoupper($sql)), 'INSERT') ? (int) db()->lastInsertId() : $st->rowCount();
}
