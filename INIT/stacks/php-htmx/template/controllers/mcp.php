<?php
// Contrôleur MCP de __SLUG__, exposé sous /mcp (JSON-RPC 2.0) : les fonctions des modèles deviennent des outils. Vide par défaut.
declare(strict_types=1);

/** @return array<string, array{description: string, handler: callable(array): mixed, inputSchema?: array}> */
function mcp_tools(): array
{
    return [
        // 'lister_items' => ['description' => 'Liste les éléments', 'handler' => fn (array $args) => items_all()],
    ];
}
