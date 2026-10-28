<?php
// Modèle : le métier et son stockage (SQLite), en fonctions pures. Aucune notion de HTTP, de session ni d'écho.
// Chaque fonction est un futur outil MCP.
declare(strict_types=1);

use function Core\{db_all, db_one, db_run};

/** @return list<array{id:int,label:string,created_at:string}> du plus récent au plus ancien */
function items_all(): array
{
    return db_all('SELECT id, label, created_at FROM items ORDER BY id DESC');
}

/** Un élément, ou null s'il n'existe pas. */
function items_find(int $id): ?array
{
    return db_one('SELECT id, label, created_at FROM items WHERE id = ?', [$id]);
}

function items_add(string $label): array
{
    $label = trim($label);
    if ($label === '' || mb_strlen($label) > 200) {
        throw new InvalidArgumentException('label : 1 à 200 caractères');
    }
    return items_find(db_run('INSERT INTO items (label) VALUES (?)', [$label]));
}
