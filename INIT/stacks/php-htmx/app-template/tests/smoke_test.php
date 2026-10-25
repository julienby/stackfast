<?php
// Test CLI sans framework : `stackfast check __SLUG__`. Chaque `ok(...)` écrit ok/FAIL ; exit 1 si un FAIL.
declare(strict_types=1);

ob_start(); // sortie retenue jusqu'à la fin : les contrôleurs peuvent fixer un code HTTP sans avertissement en CLI
require dirname(__DIR__) . '/init.php';

use function Stack\{base_path, dispatch, path, render, url};

$failed = 0;
function ok(bool $cond, string $label): void
{
    global $failed;
    echo ($cond ? 'ok   ' : 'FAIL ') . $label . "\n";
    if (!$cond) { $failed++; }
}

/** Simule une requête HTTP puis passe par le routeur : la page produite (ou le fragment, si $htmx). */
function request(string $method, string $uri, array $post = [], bool $htmx = false): string
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_POST = $post;
    unset($_SERVER['HTTP_HX_REQUEST']);
    if ($htmx) { $_SERVER['HTTP_HX_REQUEST'] = 'true'; }
    return dispatch(require APP . '/routes.php');
}

// Modèle, sans HTTP.
$store = sys_get_temp_dir() . '/__SLUG___' . bin2hex(random_bytes(4)) . '.json';
ok(items_all($store) === [], 'liste vide au départ');
$item = items_add('  Premier  ', $store);
ok($item['label'] === 'Premier', 'label nettoyé');
ok(count(items_all($store)) === 1, 'élément persisté');
ok(items_find(1, $store)['label'] === 'Premier' && items_find(99, $store) === null, 'items_find : trouvé / absent');
try { items_add('', $store); ok(false, 'label vide refusé'); }
catch (InvalidArgumentException) { ok(true, 'label vide refusé'); }
unlink($store);

// URLs : préfixe déduit du script, puis imposé par le proxy.
$_SERVER['SCRIPT_NAME'] = '/__SLUG__/index.php';
$_SERVER['REQUEST_URI'] = '/__SLUG__/items/3?x=1';
ok(base_path() === '/__SLUG__', 'base_path depuis SCRIPT_NAME');
ok(path() === '/items/3', 'path sans préfixe ni query');
$_SERVER['HTTP_X_BASE_PATH'] = '/';
ok(url('/items') === '/items', 'X-Base-Path / → liens sans préfixe');
unset($_SERVER['HTTP_X_BASE_PATH']);

// Vues : page complète vs fragment htmx, échappement.
$html = render('items/index', ['items' => [['id' => 1, 'label' => '<b>x</b>', 'at' => '']], 'csrf' => 't']);
ok(str_contains($html, '<!doctype html>'), 'page complète hors htmx');
ok(str_contains($html, '&lt;b&gt;x&lt;/b&gt;'), 'label échappé');
ok(str_contains($html, 'hx-post="/__SLUG__/items"'), 'formulaire préfixé');
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$frag = render('items/_list', ['items' => []]);
ok(!str_contains($frag, '<html') && str_contains($frag, 'id="items"'), 'fragment seul sous htmx');
$frag = render('items/_list', ['items' => [], 'error' => 'label : 1 à 200 caractères']);
ok(str_contains($frag, 'label : 1 à 200 caractères'), 'erreur de saisie affichée dans le fragment');
unset($_SERVER['HTTP_HX_REQUEST']);

// Routes : chaque ligne de routes.php mène à un contrôleur.
$page = request('GET', '/');
ok(str_contains($page, '<!doctype html>') && str_contains($page, 'Nouvel élément'), 'GET / : page d\'accueil');
ok(str_contains(request('GET', '/nope'), 'Page introuvable'), 'route inconnue : 404');
ok(str_contains(request('GET', '/items/abc'), 'Page introuvable'), 'GET /items/abc : 404 (id non numérique)');
ok(str_contains(request('GET', '/items/9999'), 'Page introuvable'), 'GET /items/9999 : 404 (inexistant)');

exit($failed ? 1 : 0);
