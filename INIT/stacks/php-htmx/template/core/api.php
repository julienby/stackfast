<?php
// Surface machine de l'app : /api (REST, JSON) et /mcp (JSON-RPC 2.0, pour un agent IA). Actives par défaut, 200 même vides.
// Protégées par un seul bearer token : API_TOKEN dans le .env de l'app. Les contrôleurs sont controllers/api.php et mcp.php.
declare(strict_types=1);

namespace Core;

/** 401 si le header Authorization ne porte pas le token de l'app. */
function bearer_check(): void
{
    $expected = env('API_TOKEN', '');
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : '';
    if ($expected === '' || !hash_equals($expected, $token)) {
        throw new HttpError('unauthorized', 401);
    }
}

function json_response(mixed $data, int $status = 200): string
{
    http_response_code($status);
    header('Content-Type: application/json');
    return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * REST minimal sous /api. $endpoints : ['GET /items' => callable], le callable rend un tableau JSON-able.
 * /api seul liste les routes déclarées (vide par défaut). Route inconnue → 404.
 */
function api_dispatch(array $endpoints): string
{
    $sub = '/' . trim(substr(path(), strlen('/api')), '/');
    if ($sub === '/') {
        return json_response(['endpoints' => array_keys($endpoints)]);
    }
    $route = method() . ' ' . $sub;
    if (!isset($endpoints[$route])) {
        throw new HttpError('not_found', 404);
    }
    return json_response($endpoints[$route]());
}

/**
 * JSON-RPC 2.0 minimal pour MCP, sous /mcp. $tools : ['nom' => ['description' => …, 'handler' => callable,
 * 'inputSchema' => […]]]. Notification (sans id) → 202 sans corps. Méthode inconnue → erreur -32601.
 */
function mcp_dispatch(array $tools): string
{
    $input = json_decode((string) file_get_contents('php://input'), true) ?? [];
    $response = mcp_handle($tools, $input);
    if ($response === null) {
        http_response_code(202);
        return '';
    }
    return json_response($response);
}

/** Réponse JSON-RPC à un message MCP, ou null pour une notification. */
function mcp_handle(array $tools, array $input): ?array
{
    if (!array_key_exists('id', $input)) {
        return null;
    }
    $id = $input['id'];
    $method = $input['method'] ?? '';
    $result = match ($method) {
        'initialize' => ['protocolVersion' => '2024-11-05', 'capabilities' => ['tools' => new \stdClass()], 'serverInfo' => ['name' => basename(APP), 'version' => '1.0.0']],
        'tools/list' => ['tools' => array_map(
            fn (string $name) => ['name' => $name, 'description' => $tools[$name]['description'] ?? '', 'inputSchema' => $tools[$name]['inputSchema'] ?? ['type' => 'object', 'properties' => new \stdClass()]],
            array_keys($tools)
        )],
        'tools/call' => mcp_call($tools, $input['params'] ?? []),
        default => null,
    };
    if ($result === null) {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => -32601, 'message' => "méthode inconnue : $method"]];
    }
    return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
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
