# __SLUG__ — contrat de l'app

> Lu après `stacks/php-htmx/AGENTS.md` (règles de la stack). Ici : le métier de cette app seulement.
> Le produit est dans `PRD.md`. Ce qui n'y est pas n'existe pas.

## Première session

1. Lis `PRD.md`. Pose les questions ouvertes (section 8) avant de coder.
2. Propose le découpage de la v1 en tâches, une par ligne du périmètre. Attends validation.
3. Vérifie que `stackfast check __SLUG__` est vert avant toute feature.

## Fichiers

Organisation MVC de la stack (détail dans `stacks/php-htmx/AGENTS.md`) : une ressource = `models/x.php` + `controllers/x.php` + `views/x/`, et une ligne par action dans `routes.php`.

- `routes.php` : toutes les routes, `'GET /items/{id}' => 'items_show'`. C'est la table des matières de l'app.
- `models/` : le métier, en fonctions pures sans HTTP. La liste des fonctions doit se lire comme la liste des futurs outils MCP.
- `controllers/` : une fonction par action. `controllers/api.php` (`api_endpoints()`) et `controllers/mcp.php` (`mcp_tools()`) exposent `/api` et `/mcp` : vides par défaut, à remplir dès qu'une donnée ou une action doit être accessible hors de l'UI.
- `views/` : une page par action (`views/items/index.php`), un fragment htmx commence par `_` (`views/items/_list.php`), `layout.php` enveloppe les pages.
- `index.php` et `init.php` : le câblage. On n'y ajoute pas d'actions.
- `data/` : JSON à plat, un fichier par collection, et les sessions (`data/.sessions`). Gitignoré.
- `compose.yml` : le conteneur de l'app (base dans la stack). Un conteneur en plus seulement si l'architecture l'impose, avec une décision ci-dessous.
- `.env` : secrets de cette app seulement (`<SLUG>_API_TOKEN`…). Gitignoré. Après une modif : `stackfast up __SLUG__`.
- `tests/*_test.php` : CLI, `ok(...)` par cas, exit 1 si rouge.

## Décisions de l'app

- _(date — choix, pourquoi, alternative écartée)_

## Lessons de l'app

- _(vide)_ — format : `- YYYY-MM-DD — règle. (erreur observée : ...)`. Si la règle vaut pour toutes les apps php-htmx, elle remonte dans le hub via `/retro`, pas ici.
