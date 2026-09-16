<?php
// Outils MCP de __SLUG__, exposés sous /mcp (JSON-RPC 2.0). Vide par défaut : tools/list répond 200, liste vide.
declare(strict_types=1);

/** @return array<string, array{description: string, handler: callable(array): mixed, inputSchema?: array}> */
function mcp_tools(): array
{
    return [
        // 'lister_items' => ['description' => '...', 'handler' => fn (array $args) => [...]],
    ];
}
