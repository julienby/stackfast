<?php
// Charge tout ce dont __SLUG__ a besoin : dépendances composer, socle (core/), modèles, contrôleurs. Ne route pas.
// Utilisé par index.php et par les tests.
declare(strict_types=1);

define('APP', __DIR__);

if (is_file(APP . '/vendor/autoload.php')) {
    require_once APP . '/vendor/autoload.php';
}
foreach (['core', 'models', 'controllers'] as $dir) {
    foreach (glob(APP . "/$dir/*.php") as $file) {
        require_once $file;
    }
}
