<?php
// Le socle de la stack php-htmx : URLs, routage, vues, JSON, session, CSRF, env.
// Chargé par init.php de chaque app ; on l'utilise avec `use function Stack\...`.
// Contrat : l'app ne connaît que son préfixe d'URL ; le proxy (Caddy ou nginx) pose X-Base-Path.
declare(strict_types=1);

namespace Stack;

// Dépendances composer : celles de la stack (dans l'image), puis celles de l'app si elle en déclare (APP/vendor).
foreach ([__DIR__ . '/../vendor/autoload.php', defined('APP') ? APP . '/vendor/autoload.php' : ''] as $autoload) {
    if ($autoload !== '' && is_file($autoload)) { require_once $autoload; }
}

/** Préfixe d'URL public, sans slash final ('' à la racine). Posé par le proxy, sinon déduit d'Apache. */
function base_path(): string
{
    $p = $_SERVER['HTTP_X_BASE_PATH'] ?? dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    return rtrim($p, '/');
}

/** URL publique d'un chemin de l'app : url('/items') → '/demo/items' ou '/items' selon le proxy. */
function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** Chemin demandé, relatif à l'app, sans query string : '/', '/items', '/items/3'. */
function path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $prefix = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    if ($prefix !== '' && str_starts_with($uri, $prefix)) {
        $uri = substr($uri, strlen($prefix));
    }
    return '/' . trim($uri, '/');
}

/**
 * Route la requête vers une fonction de contrôleur. $routes : ['GET /items/{id}' => 'items_show', ...].
 * Un {nom} capte un segment de l'URL et arrive à la fonction comme argument, dans l'ordre.
 * Aucune route ne correspond → 404.
 */
function dispatch(array $routes): string
{
    $request = method() . ' ' . path();
    foreach ($routes as $pattern => $action) {
        $regex = '#^' . preg_replace('#\{[a-z_]+\}#', '([^/]+)', $pattern) . '$#';
        if (preg_match($regex, $request, $found)) {
            return $action(...array_map('urldecode', array_slice($found, 1)));
        }
    }
    http_response_code(404);
    return render('errors/404');
}

function method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_htmx(): bool
{
    return ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';
}

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Rend views/<view>.php. Requête htmx → fragment seul ; sinon enveloppé dans views/layout.php. */
function render(string $view, array $vars = []): string
{
    $content = capture(APP . "/views/$view.php", $vars);
    if (is_htmx()) {
        return $content;
    }
    return capture(APP . '/views/layout.php', $vars + ['content' => $content]);
}

function capture(string $file, array $vars): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require $file;
    return (string) ob_get_clean();
}

function json_read(string $file, mixed $default = []): mixed
{
    if (!is_file($file)) {
        return $default;
    }
    return json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
}

/** Écriture atomique : fichier temporaire puis rename, jamais de JSON à moitié écrit. */
function json_write(string $file, mixed $data): void
{
    $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, $json . "\n", LOCK_EX) === false || !rename($tmp, $file)) {
        @unlink($tmp);
        throw new \RuntimeException("Écriture impossible : $file");
    }
}

/** Session isolée par app : nom de cookie dédié, limité au préfixe de l'app. */
function session_start_app(string $slug): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // Dans data/ de l'app : /tmp du conteneur est effacé à chaque redémarrage (et à chaque mise à jour de la stack).
    $dir = APP . '/data/.sessions';
    if (is_dir($dir) || @mkdir($dir, 0700, true)) {
        session_save_path($dir);
    }
    session_name(strtoupper(preg_replace('/[^a-z0-9]/i', '', $slug)) . 'SESSID');
    session_set_cookie_params([
        'path' => base_path() . '/',
        'httponly' => true,
        'samesite' => 'Strict',
        'secure' => ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
    ]);
    session_start();
}

function csrf_token(): string
{
    return $_SESSION['_csrf'] ??= bin2hex(random_bytes(16));
}

/** À appeler sur tout POST d'interface. Champ caché `_csrf` attendu. */
function csrf_check(): void
{
    $expected = $_SESSION['_csrf'] ?? '';
    if ($expected === '' || !hash_equals($expected, $_POST['_csrf'] ?? '')) {
        http_response_code(403);
        exit('CSRF');
    }
}

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    if ($v === false) {
        $v = $_SERVER[$key] ?? $_ENV[$key] ?? null;
    }
    return is_string($v) ? $v : $default;
}
