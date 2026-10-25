<?php
// Contrôleur REST de __SLUG__, exposé sous /api : mêmes modèles que les pages, réponse en JSON. Vide par défaut : /api répond 200.
declare(strict_types=1);

/** @return array<string, callable(): array> clé 'METHODE /chemin' (relatif à /api) → callable retournant un array JSON-able */
function api_endpoints(): array
{
    return [
        // 'GET /items' => fn () => ['items' => items_all()],
    ];
}
