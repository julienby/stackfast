<?php
// Tests CLI sans framework (bin/check). Chaque `ok(...)` écrit ok/FAIL ; exit 1 si un FAIL.
// Base SQLite temporaire : les tests ne touchent jamais data/app.sqlite.
declare(strict_types=1);

ob_start(); // sortie retenue jusqu'à la fin : les contrôleurs peuvent poser un code HTTP ou un cookie sans avertissement
putenv('DB_PATH=' . sys_get_temp_dir() . '/__SLUG___test_' . bin2hex(random_bytes(4)) . '.sqlite');
if (strlen((string) getenv('APP_SECRET')) < 32) {
    putenv('APP_SECRET=' . str_repeat('t', 32));
}
require dirname(__DIR__) . '/init.php';

use function Core\{base_path, csrf_token, handle, path, render, url};

$failed = 0;
function ok(bool $cond, string $label): void
{
    global $failed;
    echo ($cond ? 'ok   ' : 'FAIL ') . $label . "\n";
    if (!$cond) { $failed++; }
}

/** Simule une requête HTTP complète (comme index.php) : le corps de la réponse. */
function request(string $method, string $uri, array $post = [], array $cookies = [], bool $htmx = false): string
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_POST = $post;
    $_COOKIE = $cookies;
    unset($_SERVER['HTTP_HX_REQUEST'], $_SERVER['HTTP_X_BASE_PATH']);
    if ($htmx) { $_SERVER['HTTP_HX_REQUEST'] = 'true'; }
    return handle(require APP . '/routes.php');
}

// Modèle, sans HTTP.
ok(items_all() === [], 'liste vide au départ (migrations appliquées)');
$item = items_add('  Premier  ');
ok($item['label'] === 'Premier' && $item['id'] === 1, 'label nettoyé, id attribué');
ok(count(items_all()) === 1, 'élément persisté');
ok(items_find(1)['label'] === 'Premier' && items_find(99) === null, 'items_find : trouvé / absent');
try { items_add(''); ok(false, 'label vide refusé'); }
catch (InvalidArgumentException) { ok(true, 'label vide refusé'); }

// URLs : préfixe déduit du script, puis imposé par le Caddy du serveur.
$_SERVER['SCRIPT_NAME'] = '/__SLUG__/index.php';
$_SERVER['REQUEST_URI'] = '/__SLUG__/items/3?x=1';
ok(base_path() === '/__SLUG__', 'base_path depuis SCRIPT_NAME');
ok(path() === '/items/3', 'path sans préfixe ni query');
$_SERVER['HTTP_X_BASE_PATH'] = '/';
ok(url('/items') === '/items', 'X-Base-Path / → liens sans préfixe');
unset($_SERVER['HTTP_X_BASE_PATH']);

// Vues : page complète vs fragment htmx, échappement.
$html = render('items/index', ['items' => [['id' => 1, 'label' => '<b>x</b>', 'created_at' => '']], 'csrf' => 't']);
ok(str_contains($html, '<!doctype html>'), 'page complète hors htmx');
ok(str_contains($html, '&lt;b&gt;x&lt;/b&gt;'), 'label échappé');
ok(str_contains($html, 'hx-post="/__SLUG__/items"'), 'formulaire préfixé');
ok(str_contains($html, 'assets/vendor/htmx.min.js') && str_contains($html, 'assets/app.css'), 'htmx et CSS servis en local');
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$frag = render('items/_list', ['items' => []]);
ok(!str_contains($frag, '<html') && str_contains($frag, 'id="items"'), 'fragment seul sous htmx');
unset($_SERVER['HTTP_HX_REQUEST']);

// Routes : chaque ligne de routes.php mène à un contrôleur.
$page = request('GET', '/');
ok(str_contains($page, 'Nouvel élément') && str_contains($page, 'Premier'), 'GET / : page d\'accueil avec la liste');
ok(str_contains(request('GET', '/items/1'), 'Premier'), 'GET /items/1 : page de l\'élément');
ok(str_contains(request('GET', '/nope'), 'Page introuvable'), 'route inconnue : 404');
ok(str_contains(request('GET', '/items/abc'), 'Page introuvable'), 'GET /items/abc : 404');

// CSRF sans session : le jeton n'est valable qu'avec le cookie du même navigateur.
$cookie = ['csrf' => str_repeat('a', 32)];
$_COOKIE = $cookie;
$token = csrf_token();
ok(session_status() !== PHP_SESSION_ACTIVE, 'aucune session ouverte pour un visiteur anonyme');
$frag = request('POST', '/items', ['label' => 'Deuxième', '_csrf' => $token], $cookie, true);
ok(str_contains($frag, 'Deuxième') && count(items_all()) === 2, 'POST /items avec jeton + cookie : ajouté');
ok(str_contains(request('POST', '/items', ['label' => 'x'], $cookie), 'Formulaire expiré'), 'POST sans jeton : 403');
ok(str_contains(request('POST', '/items', ['label' => 'x', '_csrf' => $token], ['csrf' => str_repeat('b', 32)]), 'Formulaire expiré'), 'jeton d\'un autre navigateur : 403');
ok(count(items_all()) === 2, 'rien d\'ajouté par les POST refusés');

@unlink((string) getenv('DB_PATH'));
exit($failed ? 1 : 0);
