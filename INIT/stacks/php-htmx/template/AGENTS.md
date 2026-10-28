# __SLUG__ — contrat de l'app

> App autonome, née de la recette php-htmx (version dans `stackfast.json`). Tout ce qu'il faut pour la comprendre est dans ce dossier.
> Ordre de lecture : ce fichier, puis `PRD.md` (le produit : ce qui n'y est pas n'existe pas).
> `CLAUDE.md` est un lien vers ce fichier. Une règle n'existe que pour empêcher une erreur déjà observée ou fixer un contrat.

## Première session

1. Lis `PRD.md`. Pose les questions ouvertes avant de coder (`/prd` aide à le remplir).
2. Propose le découpage de la v1 en tâches, une par ligne du périmètre. Attends validation.
3. `bin/check` doit être vert avant toute feature.

## Où est quoi

```
routes.php            toutes les routes : 'GET /items/{id}' => 'items_show'   ← la table des matières
models/<x>.php        M : le métier et son stockage SQLite, fonctions pures (x_all, x_find, x_add…)
controllers/<x>.php   C : une fonction par action (x_index, x_show, x_create, x_update, x_delete)
controllers/api.php   /api (JSON) ; controllers/mcp.php : /mcp (outils pour agents)
views/<x>/index.php   V : une page par action ; views/<x>/_list.php : un `_` = fragment htmx
views/layout.php      la page autour ; views/errors/ : 404 et erreurs
migrations/NNN_*.sql  le schéma, un fichier par étape, jamais modifié une fois appliqué
core/                 le socle : core.php (requête, routage, vues, CSRF, env), db.php (SQLite), api.php
assets/src/app.css    source Tailwind → assets/app.css (compilé) ; assets/vendor/htmx.min.js
tests/*_test.php      tests CLI ; tests/run.sh les lance dans le conteneur
bin/build  bin/check  bin/audit   depuis l'hôte, dans ce dossier (Docker suffit)
index.php  init.php   le câblage : on n'y ajoute pas d'actions
Dockerfile compose.yml Caddyfile php.ini   comment l'app se construit et tourne
data/  .env  vendor/  hors git : base SQLite, secrets, dépendances
```

## Les règles MVC (sans framework, sans classe)

- **Une ressource = trois fichiers** : `models/x.php`, `controllers/x.php`, `views/x/`. Une action = une ligne dans `routes.php`.
- **Le modèle** ne touche jamais `$_POST`, `$_COOKIE`, la session, ni `echo`. Arguments en entrée, valeur ou exception en sortie.
- **Le contrôleur** lit la requête, appelle le modèle, rend une vue. Il n'écrit pas de HTML et ne fait pas de SQL.
- **La vue** reçoit des variables et n'utilise que `e()`, `url()`, `asset()` et des boucles. Elle n'appelle jamais le modèle.
- Les fonctions portent le nom de leur ressource (`items_show`) : un seul espace global, zéro collision, un `grep` retrouve tout.
- Une erreur HTTP se lève : `throw new HttpError('Élément introuvable', 404)`. `handle()` la transforme en page ou en JSON.
- Pas de classe si une fonction suffit, pas de couche « service », pas d'abstraction avant la troisième répétition (décision écrite).

## Données : SQLite

- `Core\db_all()`, `db_one()`, `db_run()` avec des paramètres `?`, **jamais** de valeur concaténée dans une requête.
- Changer le schéma = un nouveau fichier `migrations/NNN_quoi.sql` (le numéro suit le dernier). Il s'applique tout seul au démarrage.
- La base est `data/app.sqlite` (WAL). `stackfast backup` en fait une copie cohérente.

## Interface : htmx + Tailwind

- Requête `HX-Request` → fragment seul ; sinon page complète dans `views/layout.php`. `render()` décide.
- Toute action possible est un lien ou un formulaire dans la réponse. Pas de fetch JS maison, pas de JSON pour l'UI.
- Classes Tailwind dans les vues ; `bin/build` recompile le CSS (le hook de fin de tour le fait). HTML sémantique, pas de div cliquable.
- htmx et le CSS sont servis par l'app (aucun CDN).

## Sécurité

- `e()` sur toute valeur dans une vue. Jamais `<?= $x ?>` brut, sauf `$content` dans le layout.
- Toute action POST d'interface commence par `csrf_check()` ; tout formulaire contient `<input type="hidden" name="_csrf" value="<?= e($csrf) ?>">` avec `$csrf = csrf_token()` donné par le contrôleur. Pas de session pour ça : cookie + jeton signé (`APP_SECRET`).
- Session seulement si l'app connecte des utilisateurs : `session_start_app()` dans l'action qui en a besoin, jamais pour un visiteur anonyme. Mots de passe : `password_hash` / `password_verify`.
- Secrets dans `.env` (jamais dans le code ni dans `data/`), lus par `env()`. Après une modif : `stackfast up __SLUG__`.
- `/api` et `/mcp` exigent `Authorization: Bearer <API_TOKEN>` (`.env`).
- Identifiants venant de l'URL : validés (`ctype_digit`, liste blanche) avant usage. Pas d'upload SVG.
- `exec`, `system`, `proc_open`… sont désactivés : un besoin système = un conteneur en plus dans `compose.yml`, décision écrite.

## Performances et mode worker

- L'app tourne en **mode worker FrankenPHP** (bloc `worker` du `Caddyfile`) : elle reste chargée en mémoire, environ 3 fois plus rapide qu'en mode classique. `watch` recharge les workers quand un fichier change : une modification est visible en une seconde.
- Conséquence : **jamais de `exit` ni `die`** (lever une `HttpError`), jamais de variable `static` ou globale qui garde une donnée d'une requête à l'autre (seule exception : la connexion SQLite dans `db()`).
- Une page = le moins de requêtes SQL possible ; une liste se charge en une requête, pas une par ligne.

## Dépendances

- Composer pour les libs classiques : `stackfast composer __SLUG__ require vendor/paquet`. Une ligne dans les décisions ci-dessous : pourquoi, et quelle alternative maison a été écartée.
- Une extension PHP manquante s'ajoute dans le `Dockerfile` (`install-php-extensions`), avec une décision.

## Vérifier

- `bin/check` : lint, marqueurs de conflit, tous les `tests/*_test.php`, dans un conteneur jetable. Vert cité en fin de tâche, pas résumé.
- Un test du modèle sans HTTP par cas d'usage, un test du HTML rendu (lien, formulaire, fragment attendu), pas seulement « 200 ».
- Fin de tâche : diff montré, ce qui n'a pas pu être testé dit explicitement.

## Équipe

- **Chef** (session principale) : planifie dans `PLAN.md`, découpe, relit, tranche. Opus pour un plat complexe, Sonnet pour un plat simple (`.claude/settings.json`, clé `model`).
- **commis** (Sonnet) : implémente une tâche précise du plan, tests d'abord.
- **sous-traitant** (Haiku) : le mécanique (renommer, traduire, données de test, doc).
- **relecteur** (Opus) : relit un diff contre ce contrat avant commit.

## Décisions de l'app

- _(date — choix, pourquoi, alternative écartée)_

## Leçons de l'app

- _(vide)_ — format : `- AAAA-MM-JJ — règle. (erreur observée : …)`. Une leçon qui vaut pour d'autres apps est proposée au hub par `/retro`.
