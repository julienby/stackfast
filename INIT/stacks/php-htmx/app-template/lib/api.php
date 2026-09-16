<?php
// Endpoints REST de __SLUG__, exposés sous /api. Vide par défaut : /api répond 200 avec une liste vide.
declare(strict_types=1);

/** @return array<string, callable(): array> clé 'METHODE /chemin' (relatif à /api) → callable retournant un array JSON-able */
function api_endpoints(): array
{
    return [
        // 'GET /items' => fn () => ['items' => []],
    ];
}
