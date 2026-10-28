<?php
// Point d'entrée de __SLUG__. Les routes sont dans routes.php, les actions dans controllers/.
// Mode worker FrankenPHP (bloc `worker` du Caddyfile, par défaut) : l'app reste chargée et sert en boucle.
// Mode classique (sans ce bloc) : un passage par requête. FrankenPHP pose FRANKENPHP_WORKER=1 dans le premier cas.
declare(strict_types=1);

require __DIR__ . '/init.php';

use function Core\handle;

$routes = require APP . '/routes.php';

if (($_SERVER['FRANKENPHP_WORKER'] ?? '') === '1' && function_exists('frankenphp_handle_request')) {
    $serve = static function () use ($routes): void {
        echo handle($routes);
    };
    // Après 500 requêtes le worker s'arrête et FrankenPHP en relance un neuf : aucune fuite mémoire ne s'accumule.
    for ($served = 0; $served < 500 && frankenphp_handle_request($serve); $served++) {
        gc_collect_cycles();
    }
} else {
    echo handle($routes);
}
