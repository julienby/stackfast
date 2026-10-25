<?php
// Contrôleur : une fonction par action (<ressource>_<action>). Il lit la requête, appelle le modèle, rend une vue.
// Il n'écrit pas de HTML et ne lit aucun fichier : le HTML est dans views/, les données dans models/.
declare(strict_types=1);

use function Stack\{csrf_check, csrf_token, render};

function items_index(): string
{
    return render('items/index', ['items' => items_all(), 'csrf' => csrf_token()]);
}

function items_show(string $id): string
{
    $item = ctype_digit($id) ? items_find((int) $id) : null;
    if ($item === null) {
        http_response_code(404);
        return render('errors/404');
    }
    return render('items/show', ['item' => $item]);
}

function items_create(): string
{
    csrf_check();
    try {
        items_add((string) ($_POST['label'] ?? ''));
    } catch (InvalidArgumentException $e) {
        return render('items/_list', ['items' => items_all(), 'error' => $e->getMessage()]);
    }
    return render('items/_list', ['items' => items_all()]);
}
