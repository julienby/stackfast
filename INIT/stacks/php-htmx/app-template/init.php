<?php
// Charge tout ce dont __SLUG__ a besoin : le socle de la stack, les modèles, les contrôleurs. Ne route pas.
// Utilisé par index.php et par les tests : un seul endroit qui sait où est la stack.
declare(strict_types=1);

define('APP', __DIR__);
// dirname(__DIR__, 2) = / en conteneur (app montée en /app) ; dirname(__DIR__, 3) en local (repo entier).
foreach ([dirname(__DIR__, 2), dirname(__DIR__, 3)] as $root) {
    if (is_file($root . '/stacks/php-htmx/lib/stack.php')) {
        require_once $root . '/stacks/php-htmx/lib/stack.php';
        require_once $root . '/stacks/php-htmx/lib/mcp_api.php';
        break;
    }
}
foreach (['models', 'controllers'] as $dir) {
    foreach (glob(APP . "/$dir/*.php") as $file) {
        require_once $file;
    }
}
