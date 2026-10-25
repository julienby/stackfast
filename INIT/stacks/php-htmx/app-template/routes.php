<?php
// Toutes les routes de __SLUG__, lisibles d'un coup d'œil : 'MÉTHODE /chemin' => fonction d'un contrôleur.
// {nom} capte un segment de l'URL et arrive à la fonction comme argument. Une ligne par action.
return [
    'GET /'           => 'items_index',
    'GET /items/{id}' => 'items_show',
    'POST /items'     => 'items_create',
];
