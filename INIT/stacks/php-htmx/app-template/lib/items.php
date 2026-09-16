<?php
// Métier : fonctions pures, aucune notion de HTTP. Chaque fonction est un futur outil MCP.
declare(strict_types=1);

use function Stack\{json_read, json_write};

/** @return list<array{id:int,label:string,at:string}> */
function items_list(string $store): array
{
    return json_read($store, []);
}

function items_add(string $store, string $label): array
{
    $label = trim($label);
    if ($label === '' || strlen($label) > 200) {
        throw new InvalidArgumentException('label : 1 à 200 caractères');
    }
    $items = items_list($store);
    $item = ['id' => count($items) + 1, 'label' => $label, 'at' => date('c')];
    $items[] = $item;
    json_write($store, $items);
    return $item;
}
