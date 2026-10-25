<?php
// Modèle : le métier et son stockage, en fonctions pures. Aucune notion de HTTP, de session ni d'écho.
// Chaque fonction est un futur outil MCP. $store : fichier JSON, par défaut data/items.json (les tests en passent un autre).
declare(strict_types=1);

use function Stack\{json_read, json_write};

/** @return list<array{id:int,label:string,at:string}> */
function items_all(?string $store = null): array
{
    return json_read($store ?? APP . '/data/items.json', []);
}

/** Un élément, ou null s'il n'existe pas. */
function items_find(int $id, ?string $store = null): ?array
{
    foreach (items_all($store) as $item) {
        if ($item['id'] === $id) {
            return $item;
        }
    }
    return null;
}

function items_add(string $label, ?string $store = null): array
{
    $label = trim($label);
    if ($label === '' || strlen($label) > 200) {
        throw new InvalidArgumentException('label : 1 à 200 caractères');
    }
    $items = items_all($store);
    $item = ['id' => count($items) + 1, 'label' => $label, 'at' => date('c')];
    $items[] = $item;
    json_write($store ?? APP . '/data/items.json', $items);
    return $item;
}
