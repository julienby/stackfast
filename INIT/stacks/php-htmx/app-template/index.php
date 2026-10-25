<?php
// Point d'entrée de __SLUG__ : charge, protège /api et /mcp, démarre la session, route. Les actions sont dans controllers/.
declare(strict_types=1);

require __DIR__ . '/init.php';

use function Stack\{api_dispatch, bearer_check, dispatch, mcp_dispatch, path, session_start_app};

if (str_starts_with(path(), '/api')) {
    bearer_check('__SLUG__');
    api_dispatch(api_endpoints());
}
if (str_starts_with(path(), '/mcp')) {
    bearer_check('__SLUG__');
    mcp_dispatch('__SLUG__', mcp_tools());
}

session_start_app('__SLUG__');
echo dispatch(require APP . '/routes.php');
