<?php
// Point d'entrée de __SLUG__. Routage lisible : une ligne par action, méthode + chemin → handler.
declare(strict_types=1);

define('APP', __DIR__);
// dirname(__DIR__, 2) en conteneur (DocumentRoot = apps/php-htmx) ; dirname(__DIR__, 3) en local (repo entier).
foreach ([dirname(__DIR__, 2), dirname(__DIR__, 3)] as $root) {
    if (is_file($root . '/stacks/php-htmx/lib/bootstrap.php')) {
        require_once $root . '/stacks/php-htmx/lib/bootstrap.php';
        require_once $root . '/stacks/php-htmx/lib/mcp_api.php';
        break;
    }
}
require APP . '/lib/items.php';
require APP . '/lib/api.php';
require APP . '/lib/mcp.php';

use function Stack\{api_dispatch, bearer_check, csrf_check, csrf_token, mcp_dispatch, method, path, render, session_start_app, url};

if (str_starts_with(path(), '/api')) {
    bearer_check('__SLUG__');
    api_dispatch(api_endpoints());
}
if (str_starts_with(path(), '/mcp')) {
    bearer_check('__SLUG__');
    mcp_dispatch('__SLUG__', mcp_tools());
}

session_start_app('__SLUG__');

$route = method() . ' ' . path();
$store = APP . '/data/items.json';

$body = match (true) {
    $route === 'GET /' => render('home', ['items' => items_list($store), 'csrf' => csrf_token()]),
    $route === 'POST /items' => (function () use ($store) {
        csrf_check();
        items_add($store, (string) ($_POST['label'] ?? ''));
        return render('partials/items', ['items' => items_list($store)]);
    })(),
    default => (function () {
        http_response_code(404);
        return render('home', ['items' => [], 'csrf' => csrf_token(), 'error' => 'Page introuvable']);
    })(),
};

echo $body;
