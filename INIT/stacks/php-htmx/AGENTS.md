# Stack php-htmx — règles

> Appartient au hub `stackfast`. **Ne pas éditer sur une instance** : toute modification se fait dans le hub, puis `install.sh` est relancé sur chaque VPS.
> Lu avant l'`AGENTS.md` de l'app. Une ligne n'existe que pour empêcher une erreur déjà observée ou fixer un contrat.

## Forme

- PHP 8.4, `declare(strict_types=1)` partout. Pas de framework, pas de classe si une fonction suffit.
- Un conteneur `php:8.4-apache` pour toutes les apps de la stack. DocumentRoot = `apps/php-htmx`, chaque app est un sous-répertoire `<slug>/`.
- Helpers partagés : `stacks/php-htmx/lib/bootstrap.php`, namespace `Stack\`. Une app fait `use function Stack\{...}`. Pas de copie de helpers dans l'app.
- Dépendance composer : dans `stacks/php-htmx/composer.json` (partagé), avec une ligne dans le `DECISIONS.md` de l'instance. Propose d'abord la version maison.
- Données : JSON à plat dans `data/`, via `json_read`/`json_write` (atomique). SQLite seulement quand l'app est validée et que le volume l'exige : décision écrite.

## URLs et proxy

- L'app ne connaît jamais son domaine. Tout lien passe par `url('/...')`, tout routage par `path()`.
- Le préfixe vient du header `X-Base-Path` posé par le proxy (Caddy ou nginx), sinon du répertoire de l'app. Ne jamais coder `/<slug>/` en dur.
- Passer une app d'un sous-répertoire à un domaine dédié = un bloc dans le proxy, zéro changement de code (`proxy/*.example`).

## Hypermedia (HATEOAS)

- Requête `HX-Request` → fragment (`views/partials/`) ; sinon page complète via `views/layout.php`. `render()` décide.
- Toute action possible est un lien ou un formulaire dans la réponse. Pas de fetch JS maison, pas de JSON pour l'UI.
- htmx 4 et Tailwind par CDN, sans build. HTML sémantique (`form`, `button`, `ul`), pas de div cliquable.

## Agentification

- Métier dans `lib/` en fonctions pures (arguments → retour, exceptions), sans `$_POST`, `$_SESSION`, `echo`. Un handler d'`index.php` fait le pont.
- Une future exposition MCP ou API ne doit ajouter qu'un adaptateur, jamais réécrire le métier.

## Sécurité

- Sortie : `e()` (= `htmlspecialchars` ENT_QUOTES) sur toute valeur dans une vue. Jamais de `<?= $x ?>` brut sauf HTML déjà rendu (`$content`).
- POST d'interface : `csrf_check()` en première ligne du handler. Bearer/API et CSRF ne se mélangent pas.
- Session : `session_start_app($slug)`, cookie propre à l'app. Mots de passe : `password_hash`/`password_verify`. Mot de passe admin initial via `.env` (`<SLUG>_ADMIN_PASSWORD`), hashé au premier usage, jamais stocké en clair.
- Identifiants venant de l'URL : whitelist `[a-z0-9._-]+` avant tout accès fichier. Pas d'upload SVG.
- Secrets dans `.env` de l'instance (gitignoré), lus par `env()`. Jamais dans le code ni dans `data/`.
- `.htaccess` refuse `lib/`, `views/`, `data/`, `tests/` et les `.json|.md|.log|.tmp`. Ne pas le retirer.

## Tests et preuve

- `bin/check php-htmx [slug]` : `php -l` + tous les `tests/*_test.php`. Vert cité en fin de tâche, pas résumé.
- Un test métier sans HTTP par cas d'usage ; un test sur le HTML rendu (fragment attendu, lien ou formulaire présent), pas seulement « 200 ».
- Fin de tâche : diff montré, ce qui n'a pas pu être testé dit explicitement.

## Lessons de la stack

- 2026-09-16 — ne jamais déclarer en autoload `files` un fichier qui charge lui-même `vendor/autoload.php` et qui est aussi `require`d par l'app : boucle de redéclaration (`Cannot redeclare function`). (erreur observée : `bootstrap.php` en autoload `files` + `require` explicite → fatal.)
- 2026-09-16 — toujours garder un guard explicite contre les chaînes vides avant un `hash_equals()` de token : vide == vide passe sinon. (erreur observée : `csrf_check()` acceptait une requête sans cookie ni token.)
- 2026-09-16 — `APACHE_RUN_USER` numérique sans entrée `/etc/passwd` correspondante échoue silencieusement (Apache reste en root) ; toujours remapper un utilisateur nommé existant (`usermod -u`) et le référencer par nom. (erreur observée : `AH02155 getpwuid`, tous les processus apache2 en root malgré `APACHE_RUN_USER=#1000`.)
- format : `- YYYY-MM-DD — règle. (erreur observée : ...)`. Ajoutées dans le hub uniquement.
