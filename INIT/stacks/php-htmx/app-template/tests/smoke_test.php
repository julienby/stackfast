<?php
// Test CLI sans framework : `bin/check __SLUG__`. Chaque `ok(...)` écrit ok/FAIL ; exit 1 si un FAIL.
declare(strict_types=1);

define('APP', dirname(__DIR__));
// dirname(APP, 2) en conteneur (DocumentRoot = apps/php-htmx) ; dirname(APP, 3) en local (repo entier).
foreach ([dirname(APP, 2), dirname(APP, 3)] as $root) {
    if (is_file($root . '/stacks/php-htmx/lib/bootstrap.php')) {
        require_once $root . '/stacks/php-htmx/lib/bootstrap.php';
        break;
    }
}
require APP . '/lib/items.php';

use function Stack\{base_path, path, render, url};

$failed = 0;
function ok(bool $cond, string $label): void
{
    global $failed;
    echo ($cond ? 'ok   ' : 'FAIL ') . $label . "\n";
    if (!$cond) { $failed++; }
}

// Métier, sans HTTP.
$store = sys_get_temp_dir() . '/__SLUG___' . bin2hex(random_bytes(4)) . '.json';
ok(items_list($store) === [], 'liste vide au départ');
$item = items_add($store, '  Premier  ');
ok($item['label'] === 'Premier', 'label nettoyé');
ok(count(items_list($store)) === 1, 'élément persisté');
try { items_add($store, ''); ok(false, 'label vide refusé'); }
catch (InvalidArgumentException) { ok(true, 'label vide refusé'); }
unlink($store);

// URLs : préfixe déduit d'Apache, puis imposé par le proxy.
$_SERVER['SCRIPT_NAME'] = '/__SLUG__/index.php';
$_SERVER['REQUEST_URI'] = '/__SLUG__/items/3?x=1';
ok(base_path() === '/__SLUG__', 'base_path depuis SCRIPT_NAME');
ok(path() === '/items/3', 'path sans préfixe ni query');
$_SERVER['HTTP_X_BASE_PATH'] = '/';
ok(url('/items') === '/items', 'X-Base-Path / → liens sans préfixe');
unset($_SERVER['HTTP_X_BASE_PATH']);

// Rendu : page complète vs fragment htmx.
$html = render('home', ['items' => [['id' => 1, 'label' => '<b>x</b>', 'at' => '']], 'csrf' => 't']);
ok(str_contains($html, '<!doctype html>'), 'page complète hors htmx');
ok(str_contains($html, '&lt;b&gt;x&lt;/b&gt;'), 'label échappé');
ok(str_contains($html, 'hx-post="/__SLUG__/items"'), 'formulaire préfixé');
$_SERVER['HTTP_HX_REQUEST'] = 'true';
$frag = render('partials/items', ['items' => []]);
ok(!str_contains($frag, '<html') && str_contains($frag, 'id="items"'), 'fragment seul sous htmx');

exit($failed ? 1 : 0);
