# Stack php-htmx — règles

> Appartient au hub `stackfast`. **Ne pas éditer sur une instance** : toute modification se fait dans le hub, puis `stackfast pull && stackfast sync` est lancé sur chaque VPS. Une modif locale bloque `sync` (fichiers listés) ; `sync --force` l'écrase.
> Lu avant l'`AGENTS.md` de l'app. Une ligne n'existe que pour empêcher une erreur déjà observée ou fixer un contrat.

## Forme

- PHP 8.5, `declare(strict_types=1)` partout. Pas de framework, pas de classe si une fonction suffit.
- Un conteneur par app, FrankenPHP (Caddy + PHP), depuis l'image de la stack `sf-<instance>-php-htmx:current`. L'app est montée en `/app`, la lib de la stack en `/stacks/php-htmx/lib` (lecture seule). Base commune (sécurité, limites, santé) : `app.compose.yml` ; branche PHP : `stack.conf`.
- Le conteneur est en lecture seule, sans privilège, avec l'uid de l'hôte : seuls l'app (`/app`) et `/tmp` s'écrivent. Pas de `exec`/`shell_exec`/`proc_open` (désactivés dans `php.ini`) : un besoin système = un conteneur en plus dans le `compose.yml` de l'app, décision écrite.
- Helpers partagés : `stacks/php-htmx/lib/bootstrap.php`, namespace `Stack\`. Une app fait `use function Stack\{...}`. Pas de copie de helpers dans l'app.
- Dépendance composer : dans `stacks/php-htmx/composer.json` (partagé), avec une ligne dans le `DECISIONS.md` de l'instance. Propose d'abord la version maison.
- Données : JSON à plat dans `data/`, via `json_read`/`json_write` (atomique). SQLite seulement quand l'app est validée et que le volume l'exige : décision écrite.

## URLs et proxy

- L'app ne connaît jamais son domaine. Tout lien passe par `url('/...')`, tout routage par `path()`.
- Le préfixe vient du header `X-Base-Path` posé par le proxy de l'instance (`/<slug>` en incubation, `/` sur un domaine). Ne jamais coder `/<slug>/` en dur.
- Passer de `apps.domaine.fr/<slug>/` à `<slug>.domaine.fr` puis à `domaine1.fr` = `stackfast domain <slug> <domaine>`, zéro changement de code.

## Hypermedia (HATEOAS)

- Requête `HX-Request` → fragment (`views/partials/`) ; sinon page complète via `views/layout.php`. `render()` décide.
- Toute action possible est un lien ou un formulaire dans la réponse. Pas de fetch JS maison, pas de JSON pour l'UI.
- htmx 4 et Tailwind par CDN, sans build. HTML sémantique (`form`, `button`, `ul`), pas de div cliquable.

## Agentification

- Métier dans `lib/` en fonctions pures (arguments → retour, exceptions), sans `$_POST`, `$_SESSION`, `echo`. Un handler d'`index.php` fait le pont.
- Une exposition MCP ou API n'ajoute qu'un adaptateur, jamais réécrire le métier.

## API et MCP

- Chaque app expose `/api` (REST) et `/mcp` (JSON-RPC 2.0) par défaut, actives dès `stackfast new`, 200 même vides (jamais 404 tant qu'aucun endpoint/outil n'est déclaré).
- Dispatch partagé dans `stacks/php-htmx/lib/mcp_api.php` (`api_dispatch()`, `mcp_dispatch()`) — ne pas le copier dans une app.
- Une app déclare ses endpoints dans `lib/api.php` (`api_endpoints()`) et ses outils dans `lib/mcp.php` (`mcp_tools()`), sans toucher `index.php`.
- Les deux surfaces sont protégées par un unique bearer token `<SLUG>_API_TOKEN` (`.env` de l'app, généré par `stackfast new`). Header `Authorization: Bearer <token>`.

## Sécurité

- Sortie : `e()` (= `htmlspecialchars` ENT_QUOTES) sur toute valeur dans une vue. Jamais de `<?= $x ?>` brut sauf HTML déjà rendu (`$content`).
- POST d'interface : `csrf_check()` en première ligne du handler. Bearer/API et CSRF ne se mélangent pas.
- Session : `session_start_app($slug)`, cookie propre à l'app. Mots de passe : `password_hash`/`password_verify`. Mot de passe admin initial via `.env` (`<SLUG>_ADMIN_PASSWORD`), hashé au premier usage, jamais stocké en clair.
- Identifiants venant de l'URL : whitelist `[a-z0-9._-]+` avant tout accès fichier. Pas d'upload SVG.
- Secrets dans le `.env` de l'app (gitignoré), lus par `env()`. Jamais dans le code ni dans `data/`.
- Le `Caddyfile` de la stack refuse `lib/`, `views/`, `data/`, `tests/`, `services/`, les fichiers cachés et les `.json|.md|.yml|.ini|.log|.tmp|.sqlite` existants. `.htaccess` n'est plus lu : une règle d'app s'écrit en Caddyfile, avec une décision.
- Correctif PHP publié → `stackfast update` puis `stackfast audit` (procédure : `docs/SECURITE.md` du hub). Jamais de `php_value`/`ini_set` de réglages de sécurité dans une app.

## Tests et preuve

- `stackfast check [slug]` : `php -l` + tous les `tests/*_test.php`, dans l'image de la stack (conteneur jetable, sans réseau). Vert cité en fin de tâche, pas résumé.
- Un test métier sans HTTP par cas d'usage ; un test sur le HTML rendu (fragment attendu, lien ou formulaire présent), pas seulement « 200 ».
- Fin de tâche : diff montré, ce qui n'a pas pu être testé dit explicitement.

## Lessons de la stack

- 2026-09-16 — ne jamais déclarer en autoload `files` un fichier qui charge lui-même `vendor/autoload.php` et qui est aussi `require`d par l'app : boucle de redéclaration (`Cannot redeclare function`). (erreur observée : `bootstrap.php` en autoload `files` + `require` explicite → fatal.)
- 2026-09-16 — toujours garder un guard explicite contre les chaînes vides avant un `hash_equals()` de token : vide == vide passe sinon. (erreur observée : `csrf_check()` acceptait une requête sans cookie ni token.)
- 2026-09-29 — Mettre à jour les tests de rendu avec les vues qu’ils décrivent. (erreur observée : les tests du squelette sont restés après son remplacement.)
- format : `- YYYY-MM-DD — règle. (erreur observée : ...)`. Ajoutées dans le hub uniquement.
