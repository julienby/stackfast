<?php
// Tests de la surface machine : /api et /mcp, token, JSON-RPC (bin/check).
declare(strict_types=1);

ob_start();
putenv('DB_PATH=' . sys_get_temp_dir() . '/__SLUG___api_' . bin2hex(random_bytes(4)) . '.sqlite');
putenv('API_TOKEN=jeton-de-test');
require dirname(__DIR__) . '/init.php';

use function Core\{handle, mcp_handle};

$failed = 0;
function ok(bool $cond, string $label): void
{
    global $failed;
    echo ($cond ? 'ok   ' : 'FAIL ') . $label . "\n";
    if (!$cond) { $failed++; }
}

function call(string $uri, string $token = ''): array
{
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $uri;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['HTTP_AUTHORIZATION'] = $token !== '' ? "Bearer $token" : '';
    return json_decode(handle(require APP . '/routes.php'), true) ?? [];
}

// Token : sans lui, rien ; avec lui, la surface répond même vide.
ok((call('/api')['error'] ?? '') === 'unauthorized', '/api sans token : refusé');
ok((call('/api', 'mauvais')['error'] ?? '') === 'unauthorized', '/api avec un mauvais token : refusé');
ok((call('/api', 'jeton-de-test')['endpoints'] ?? null) === [], '/api avec le token : liste vide par défaut');
ok((call('/api/rien', 'jeton-de-test')['error'] ?? '') === 'not_found', '/api/route inconnue : 404');

// MCP : notification sans réponse, méthode inconnue → -32601, liste d'outils vide par défaut.
ok(mcp_handle([], ['jsonrpc' => '2.0', 'method' => 'notifications/initialized']) === null, 'mcp : notification sans réponse');
$r = mcp_handle([], ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'inconnue']);
ok(($r['error']['code'] ?? null) === -32601 && $r['id'] === 1, 'mcp : méthode inconnue → -32601');
$r = mcp_handle(mcp_tools(), ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']);
ok(($r['result']['tools'] ?? null) === [], 'mcp : tools/list vide par défaut');
$r = mcp_handle([], ['jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/call', 'params' => ['name' => 'x']]);
ok(($r['result']['isError'] ?? null) === true, 'mcp : outil inconnu → isError');

@unlink((string) getenv('DB_PATH'));
exit($failed ? 1 : 0);
