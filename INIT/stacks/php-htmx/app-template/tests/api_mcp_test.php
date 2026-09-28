<?php
// Test CLI de la surface /api + /mcp partagée (stacks/php-htmx/lib/mcp_api.php), sans HTTP.
declare(strict_types=1);

define('APP', dirname(__DIR__));
// dirname(APP, 2) en conteneur (DocumentRoot = apps/php-htmx) ; dirname(APP, 3) en local (repo entier).
foreach ([dirname(APP, 2), dirname(APP, 3)] as $root) {
    if (is_file($root . '/stacks/php-htmx/lib/bootstrap.php')) {
        require_once $root . '/stacks/php-htmx/lib/bootstrap.php';
        require_once $root . '/stacks/php-htmx/lib/mcp_api.php';
        break;
    }
}

use function Stack\{mcp_handle, token_env};

$failed = 0;
function ok(bool $cond, string $label): void
{
    global $failed;
    echo ($cond ? 'ok   ' : 'FAIL ') . $label . "\n";
    if (!$cond) { $failed++; }
}

// Même nom que celui écrit par bin/new-app (tr 'a-z-' 'A-Z_').
ok(token_env('mon-app') === 'MON_APP_API_TOKEN', 'token : mon-app → MON_APP_API_TOKEN');
ok(token_env('demo') === 'DEMO_API_TOKEN', 'token : demo → DEMO_API_TOKEN');

// MCP : une notification (sans id) n'a pas de réponse, une méthode inconnue avec id → -32601.
ok(mcp_handle('demo', [], ['jsonrpc' => '2.0', 'method' => 'notifications/initialized']) === null, 'mcp : notification sans réponse');
$r = mcp_handle('demo', [], ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'inconnue']);
ok(($r['error']['code'] ?? null) === -32601 && $r['id'] === 1, 'mcp : méthode inconnue → -32601');
$r = mcp_handle('demo', [], ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']);
ok(($r['result']['tools'] ?? null) === [], 'mcp : tools/list vide par défaut');
$r = mcp_handle('demo', [], ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'x']]);
ok(($r['result']['isError'] ?? null) === true, 'mcp : outil inconnu → isError');

exit($failed ? 1 : 0);
