<?php
// jbh : stockage de mon harnais. Un seul fichier, une base SQLite, un jeton.

const TYPES = ['instructions-md', 'skill'];

function reply(int $status, ?array $data = null): never
{
    http_response_code($status);
    if ($data !== null) {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

function fail(int $status, string $message): never
{
    reply($status, ['error' => $message]);
}

function link_to(string $href, ?string $method = null): array
{
    return $method === null ? ['href' => $href] : ['href' => $href, 'method' => $method];
}

function open_db(string $file): PDO
{
    $db = new PDO("sqlite:$file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY,
        type TEXT NOT NULL,
        name TEXT NOT NULL,
        created_at TEXT NOT NULL,
        UNIQUE (type, name)
    )');
    $db->exec('CREATE TABLE IF NOT EXISTS versions (
        id INTEGER PRIMARY KEY,
        item_id INTEGER NOT NULL REFERENCES items(id) ON DELETE CASCADE,
        number INTEGER NOT NULL,
        created_at TEXT NOT NULL,
        UNIQUE (item_id, number)
    )');
    $db->exec('CREATE TABLE IF NOT EXISTS files (
        version_id INTEGER NOT NULL REFERENCES versions(id) ON DELETE CASCADE,
        path TEXT NOT NULL,
        content TEXT NOT NULL,
        PRIMARY KEY (version_id, path)
    )');
    return $db;
}

function query(PDO $db, string $sql, array $params = []): PDOStatement
{
    $statement = $db->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function valid_name(string $name): bool
{
    return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name) === 1;
}

function valid_file_path(mixed $path): bool
{
    if (!is_string($path) || $path === '' || str_contains($path, "\0") || str_contains($path, '\\')) {
        return false;
    }
    foreach (explode('/', $path) as $part) {
        if ($part === '' || $part === '.' || $part === '..') {
            return false;
        }
    }
    return true;
}

function files_from_body(string $body): array
{
    $data = json_decode($body, true);
    if (!is_array($data) || !isset($data['files']) || !is_array($data['files']) || $data['files'] === []) {
        fail(400, 'le corps doit être {"files":[{"path":"…","content":"…"}]}');
    }
    $files = [];
    foreach ($data['files'] as $file) {
        if (!is_array($file) || !valid_file_path($file['path'] ?? null) || !is_string($file['content'] ?? null)) {
            fail(400, 'chaque fichier a un "path" relatif sûr et un "content" texte');
        }
        if (isset($files[$file['path']])) {
            fail(400, 'chemin en double : ' . $file['path']);
        }
        $files[$file['path']] = $file['content'];
    }
    return $files;
}

function item_href(string $type, string $name): string
{
    return "/items/$type/$name";
}

function find_item(PDO $db, string $type, string $name): array
{
    $item = query($db, 'SELECT * FROM items WHERE type = ? AND name = ?', [$type, $name])->fetch(PDO::FETCH_ASSOC);
    return $item ?: fail(404, 'item inconnu');
}

function find_version(PDO $db, array $item, ?int $number): array
{
    $sql = 'SELECT * FROM versions WHERE item_id = ? ' . ($number === null ? 'ORDER BY number DESC LIMIT 1' : 'AND number = ?');
    $params = $number === null ? [$item['id']] : [$item['id'], $number];
    $version = query($db, $sql, $params)->fetch(PDO::FETCH_ASSOC);
    return $version ?: fail(404, 'version inconnue');
}

function version_files(PDO $db, array $version): array
{
    return query($db, 'SELECT path, content FROM files WHERE version_id = ? ORDER BY path', [$version['id']])
        ->fetchAll(PDO::FETCH_ASSOC);
}

function item_view(PDO $db, array $item, array $version, bool $is_latest): array
{
    $href = item_href($item['type'], $item['name']);
    $links = [
        'self' => link_to($is_latest ? $href : "$href/versions/{$version['number']}"),
        'collection' => link_to('/items'),
        'versions' => link_to("$href/versions"),
    ];
    if ($is_latest) {
        $links['update'] = link_to($href, 'PUT');
        $links['delete'] = link_to($href, 'DELETE');
    } else {
        $links['item'] = link_to($href);
    }
    return [
        'type' => $item['type'],
        'name' => $item['name'],
        'version' => (int) $version['number'],
        'created_at' => $version['created_at'],
        'files' => version_files($db, $version),
        '_links' => $links,
    ];
}

function save_item(PDO $db, string $type, string $name, array $files): never
{
    $db->beginTransaction();
    $item = query($db, 'SELECT * FROM items WHERE type = ? AND name = ?', [$type, $name])->fetch(PDO::FETCH_ASSOC);
    $created = $item === false;
    $now = gmdate('c');
    if ($created) {
        query($db, 'INSERT INTO items (type, name, created_at) VALUES (?, ?, ?)', [$type, $name, $now]);
        $item = ['id' => $db->lastInsertId(), 'type' => $type, 'name' => $name];
    }
    $number = 1 + (int) query($db, 'SELECT COALESCE(MAX(number), 0) FROM versions WHERE item_id = ?', [$item['id']])->fetchColumn();
    query($db, 'INSERT INTO versions (item_id, number, created_at) VALUES (?, ?, ?)', [$item['id'], $number, $now]);
    $version_id = $db->lastInsertId();
    foreach ($files as $path => $content) {
        query($db, 'INSERT INTO files (version_id, path, content) VALUES (?, ?, ?)', [$version_id, $path, $content]);
    }
    $db->commit();
    $version = ['id' => $version_id, 'number' => $number, 'created_at' => $now];
    reply($created ? 201 : 200, item_view($db, $item, $version, true));
}

// --- entrée ---------------------------------------------------------------

$token = getenv('JBH_TOKEN') ?: '';
$db_file = getenv('JBH_DB') ?: '';
if ($token === '' || $db_file === '') {
    fail(500, 'JBH_TOKEN et JBH_DB sont obligatoires');
}

$sent = preg_match('/^Bearer (.+)$/', $_SERVER['HTTP_AUTHORIZATION'] ?? '', $found) ? $found[1] : '';
if (!hash_equals($token, $sent)) {
    header('WWW-Authenticate: Bearer');
    fail(401, 'jeton manquant ou invalide');
}

$db = open_db($db_file);
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = array_map('rawurldecode', array_values(array_filter(explode('/', $path), fn ($part) => $part !== '')));

if ($parts === []) {
    reply(200, ['name' => 'jbh', '_links' => ['self' => link_to('/'), 'items' => link_to('/items')]]);
}

if ($parts[0] !== 'items') {
    fail(404, 'ressource inconnue');
}

if (count($parts) === 1) {
    $type = $_GET['type'] ?? null;
    if ($type !== null && !in_array($type, TYPES, true)) {
        fail(400, 'type inconnu, attendu : ' . implode(', ', TYPES));
    }
    $sql = 'SELECT i.type, i.name, (SELECT MAX(number) FROM versions WHERE item_id = i.id) AS version FROM items i'
        . ($type === null ? '' : ' WHERE i.type = ?') . ' ORDER BY i.type, i.name';
    $rows = query($db, $sql, $type === null ? [] : [$type])->fetchAll(PDO::FETCH_ASSOC);
    $items = array_map(fn ($row) => [
        'type' => $row['type'],
        'name' => $row['name'],
        'version' => (int) $row['version'],
        '_links' => ['self' => link_to(item_href($row['type'], $row['name']))],
    ], $rows);
    reply(200, ['items' => $items, '_links' => ['self' => link_to('/items'), 'root' => link_to('/')]]);
}

if (count($parts) < 3) {
    fail(404, 'ressource inconnue');
}

[, $type, $name] = $parts;
$rest = array_slice($parts, 3);

if ($rest === []) {
    if ($method === 'PUT') {
        if (!in_array($type, TYPES, true)) {
            fail(400, 'type inconnu, attendu : ' . implode(', ', TYPES));
        }
        if (!valid_name($name)) {
            fail(400, 'nom invalide : lettres, chiffres, point, tiret, souligné');
        }
        save_item($db, $type, $name, files_from_body(file_get_contents('php://input')));
    }
    $item = find_item($db, $type, $name);
    if ($method === 'GET') {
        reply(200, item_view($db, $item, find_version($db, $item, null), true));
    }
    if ($method === 'DELETE') {
        query($db, 'DELETE FROM items WHERE id = ?', [$item['id']]);
        reply(204);
    }
    header('Allow: GET, PUT, DELETE');
    fail(405, 'méthode non permise');
}

if ($rest[0] !== 'versions' || count($rest) > 2) {
    fail(404, 'ressource inconnue');
}
if ($method !== 'GET') {
    header('Allow: GET');
    fail(405, 'méthode non permise');
}

$item = find_item($db, $type, $name);
$href = item_href($type, $name);

if (count($rest) === 1) {
    $rows = query($db, 'SELECT number, created_at FROM versions WHERE item_id = ? ORDER BY number', [$item['id']])
        ->fetchAll(PDO::FETCH_ASSOC);
    $versions = array_map(fn ($row) => [
        'number' => (int) $row['number'],
        'created_at' => $row['created_at'],
        '_links' => ['self' => link_to("$href/versions/{$row['number']}")],
    ], $rows);
    reply(200, ['versions' => $versions, '_links' => ['self' => link_to("$href/versions"), 'item' => link_to($href)]]);
}

if (!ctype_digit($rest[1])) {
    fail(404, 'version inconnue');
}
reply(200, item_view($db, $item, find_version($db, $item, (int) $rest[1]), false));
