<?php
// Surface API + MCP partagée par toutes les apps. Chargé par api.php/mcp.php : require + `use function Stack\...`.
// Contrat : actives par défaut, 200 même vides ; un bearer token par app (`<SLUG>_API_TOKEN`) protège les deux.
declare(strict_types=1);

namespace Stack;

/** Coupe la requête en 401 si le bearer token ne correspond pas à env(<SLUG>_API_TOKEN). */
function bearer_check(string $slug): void
{
    $expected = env(strtoupper(preg_replace('/[^a-z0-9]/i', '', $slug)) . '_API_TOKEN', '');
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';
    if ($expected === '' || !hash_equals($expected, $token)) {
        json_response(['error' => 'unauthorized'], 401);
    }
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Dispatch REST minimal, monté sous /api. $endpoints : ['GET /items' => callable, ...], callable
 * reçoit [] et retourne un array JSON-able. Racine /api toujours 200, liste les routes déclarées
 * (vide par défaut). Route inconnue → 404.
 */
function api_dispatch(array $endpoints): never
{
    $sub = '/' . trim(substr(path(), strlen('/api')), '/');
    if ($sub === '/') {
        json_response(['endpoints' => array_keys($endpoints)]);
    }
    $route = method() . ' ' . $sub;
    if (isset($endpoints[$route])) {
        json_response($endpoints[$route]());
    }
    json_response(['error' => 'not_found'], 404);
}

/**
 * Dispatch JSON-RPC 2.0 minimal pour MCP, monté sous /mcp. $tools : ['nom' => ['description' => ...,
 * 'handler' => callable, 'inputSchema' => [...]]]. tools/list et initialize toujours 200 (liste vide
 * par défaut). Méthode inconnue → erreur JSON-RPC -32601.
 */
function mcp_dispatch(string $slug, array $tools): never
{
    $input = json_decode((string) file_get_contents('php://input'), true) ?? [];
    $id = $input['id'] ?? null;
    $method = $input['method'] ?? '';

    $result = match ($method) {
        'initialize' => ['protocolVersion' => '2024-11-05', 'capabilities' => ['tools' => new \stdClass()], 'serverInfo' => ['name' => $slug, 'version' => '1.0.0']],
        'tools/list' => ['tools' => array_map(
            fn (string $name) => ['name' => $name, 'description' => $tools[$name]['description'] ?? '', 'inputSchema' => $tools[$name]['inputSchema'] ?? ['type' => 'object', 'properties' => new \stdClass()]],
            array_keys($tools)
        )],
        'tools/call' => mcp_call($tools, $input['params'] ?? []),
        default => null,
    };

    if ($result === null && $method !== 'tools/call') {
        json_response(['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32601, 'message' => "méthode inconnue : $method"]]);
    }
    json_response(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result]);
}

function mcp_call(array $tools, array $params): array
{
    $name = $params['name'] ?? '';
    if (!isset($tools[$name])) {
        return ['isError' => true, 'content' => [['type' => 'text', 'text' => "outil inconnu : $name"]]];
    }
    $output = ($tools[$name]['handler'])($params['arguments'] ?? []);
    return ['content' => [['type' => 'text', 'text' => is_string($output) ? $output : json_encode($output, JSON_UNESCAPED_UNICODE)]]];
}
