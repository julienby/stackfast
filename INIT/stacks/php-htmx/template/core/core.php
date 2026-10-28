<?php
// Le socle de l'app : requête, routage, vues, CSRF, session à la demande, env, JSON.
// Copié depuis la recette php-htmx (version dans stackfast.json) : il appartient à l'app, il se lit et se modifie ici.
// S'utilise avec `use function Core\{render, url, ...}`.
// Compatible mode worker FrankenPHP : aucun exit, aucun état qui survit à une requête (sauf la connexion SQLite).
declare(strict_types=1);

namespace Core;

/** Erreur HTTP levée n'importe où (contrôleur, CSRF, token) : handle() la transforme en réponse. Code HTTP = getCode(). */
final class HttpError extends \RuntimeException
{
}

/**
 * Traite une requête et renvoie le corps de la réponse. Point d'entrée unique (index.php, mode classique ou worker).
 * /api et /mcp passent par leur contrôleur (bearer token) ; le reste par routes.php.
 */
function handle(array $routes): string
{
    try {
        if (str_starts_with(path(), '/api')) {
            bearer_check();
            return api_dispatch(api_endpoints());
        }
        if (str_starts_with(path(), '/mcp')) {
            bearer_check();
            return mcp_dispatch(mcp_tools());
        }
        return dispatch($routes);
    } catch (HttpError $e) {
        http_response_code($e->getCode());
        if (str_starts_with(path(), '/api') || str_starts_with(path(), '/mcp')) {
            return json_response(['error' => $e->getMessage()], $e->getCode());
        }
        return render($e->getCode() === 404 ? 'errors/404' : 'errors/error', ['message' => $e->getMessage()]);
    } finally {
        // En mode worker, une session ouverte ne doit pas déborder sur la requête suivante.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }
}

/**
 * Route la requête vers une fonction de contrôleur. $routes : ['GET /items/{id}' => 'items_show', ...].
 * Un {nom} capte un segment de l'URL et arrive à la fonction comme argument, dans l'ordre. Rien ne correspond → 404.
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
    throw new HttpError('Page introuvable', 404);
}

/** Préfixe d'URL public, sans slash final ('' à la racine). Posé par le Caddy du serveur, sinon déduit du script. */
function base_path(): string
{
    $p = $_SERVER['HTTP_X_BASE_PATH'] ?? dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    return rtrim($p, '/');
}

/** URL publique d'un chemin de l'app : url('/items') → '/monsite/items' ou '/items' selon l'adresse. */
function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** URL d'un fichier de assets/, avec sa version (date de modification) : cache navigateur d'un an sans risque. */
function asset(string $file): string
{
    $mtime = @filemtime(APP . '/assets/' . $file);
    return url('/assets/' . $file) . ($mtime ? '?v=' . $mtime : '');
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

function method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_htmx(): bool
{
    return ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';
}

function is_https(): bool
{
    return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' || ($_SERVER['HTTPS'] ?? '') === 'on';
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

/** Variable d'environnement (le .env de l'app est chargé dans le conteneur). */
function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    if ($v === false) {
        $v = $_SERVER[$key] ?? $_ENV[$key] ?? null;
    }
    return is_string($v) ? $v : $default;
}

// --- CSRF, sans session : cookie aléatoire + jeton signé (double soumission). Aucune écriture disque. ---

function app_secret(): string
{
    $secret = env('APP_SECRET', '');
    if (strlen($secret) < 32) {
        throw new \RuntimeException('APP_SECRET manquant ou trop court dans .env (32 caractères au moins)');
    }
    return $secret;
}

/** Jeton à mettre dans tout formulaire POST (champ caché `_csrf`). Pose le cookie du navigateur s'il manque. */
function csrf_token(): string
{
    $cookie = $_COOKIE['csrf'] ?? '';
    if (!preg_match('/^[a-f0-9]{32}$/', $cookie)) {
        $cookie = bin2hex(random_bytes(16));
        setcookie('csrf', $cookie, ['path' => base_path() . '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => is_https()]);
        $_COOKIE['csrf'] = $cookie;
    }
    return hash_hmac('sha256', $cookie, app_secret());
}

/** En première ligne de toute action POST d'interface. 403 si le jeton ne correspond pas au cookie du navigateur. */
function csrf_check(): void
{
    $cookie = $_COOKIE['csrf'] ?? '';
    $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($cookie === '' || $sent === '' || !hash_equals(hash_hmac('sha256', $cookie, app_secret()), $sent)) {
        throw new HttpError('Formulaire expiré : recharge la page', 403);
    }
}

// --- Session, seulement quand l'app en a besoin (connexion d'un utilisateur). Jamais pour un visiteur anonyme. ---

function session_start_app(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    // Dans data/ : /tmp du conteneur est effacé à chaque redémarrage.
    $dir = APP . '/data/.sessions';
    if (is_dir($dir) || @mkdir($dir, 0700, true)) {
        session_save_path($dir);
    }
    session_name('sid');
    session_set_cookie_params(['path' => base_path() . '/', 'httponly' => true, 'samesite' => 'Strict', 'secure' => is_https()]);
    session_start();
}

// --- JSON à plat, pour les petits besoins (la base de l'app est SQLite : core/db.php). ---

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
