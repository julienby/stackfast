# __SLUG__ — contrat de l'app

> Lu après `stacks/php-htmx/AGENTS.md` (règles de la stack). Ici : le métier de cette app seulement.
> Le produit est dans `PRD.md`. Ce qui n'y est pas n'existe pas.

## Première session

1. Lis `PRD.md`. Pose les questions ouvertes (section 8) avant de coder.
2. Propose le découpage de la v1 en tâches, une par ligne du périmètre. Attends validation.
3. Vérifie que `bin/check php-htmx __SLUG__` est vert avant toute feature.

## Fichiers

- `index.php` : routes, une ligne par action. Handler fin : lit la requête, appelle `lib/`, rend une vue.
- `lib/` : métier, fonctions pures sans HTTP. La liste des fonctions doit se lire comme la liste des futurs outils MCP.
- `lib/api.php` (`api_endpoints()`) et `lib/mcp.php` (`mcp_tools()`) : ce que `/api` et `/mcp` exposent. Vides par défaut, à remplir dès qu'une donnée ou une action de cette app doit être accessible en dehors de l'UI (voir « API et MCP » dans `stacks/php-htmx/AGENTS.md`).
- `views/` : pages ; `views/partials/` : fragments renvoyés aux requêtes htmx.
- `data/` : JSON à plat, un fichier par collection. Gitignoré.
- `tests/*_test.php` : CLI, `ok(...)` par cas, exit 1 si rouge.

## Décisions de l'app

- _(date — choix, pourquoi, alternative écartée)_

## Lessons de l'app

- _(vide)_ — format : `- YYYY-MM-DD — règle. (erreur observée : ...)`. Si la règle vaut pour toutes les apps php-htmx, elle remonte dans le hub via `/retro`, pas ici.
