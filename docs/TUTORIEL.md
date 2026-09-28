# Tutoriel stackfast — tout comprendre

Ce tutoriel explique **pourquoi** chaque pièce existe, puis **comment** s'en servir.
Pour un test rapide, lis plutôt `GETTING-STARTED.md`.

Plan :

1. L'idée en une page
2. Les quatre niveaux
3. Installer
4. Créer une instance
5. Créer une app avec Claude
6. Anatomie d'une app php-htmx
7. Une requête, de bout en bout
8. `/api` et `/mcp`
9. Vérifier : `bin/check` et le hook
10. Déployer derrière un proxy
11. Faire vivre les règles (Lessons)
12. Synchroniser : `pull`, `push`, `sync`
13. Choisir le modèle
14. Dépannage
15. Aide-mémoire

---

## 1. L'idée en une page

Tu codes avec une IA (Claude Code ou Codex). L'IA fait des erreurs. Tu la corriges.
Sans système, tu la corriges **encore** la semaine suivante, dans un autre projet.

stackfast règle ce problème avec trois choses :

- **Des règles écrites**, dans des fichiers que l'IA lit au début de chaque session.
- **Une preuve automatique** : `bin/check` lance les tests. Un hook le relance à chaque fin de tour.
- **Une boucle** : chaque erreur corrigée devient une règle d'une ligne (une *Lesson*), au bon niveau.

Le repo `stackfast` est le **hub**. Il ne contient aucune app. Il contient :

- ta méthode de travail (`INIT/CLAUDE.global.md`) ;
- le gabarit d'une instance (`INIT/instance/`) ;
- les stacks (`INIT/stacks/php-htmx/`) ;
- le client `stackfast` et son installeur.

---

## 2. Les quatre niveaux

Une règle vit au niveau le plus bas où elle est vraie.

```
Méthode   ~/.claude/CLAUDE.md          → comment tu travailles, partout
  └─ Instance   mon-vps/AGENTS.md      → ce VPS : proxy, réseau, machine
       └─ Stack    stacks/php-htmx/AGENTS.md    → toutes les apps PHP
            └─ App   apps/php-htmx/mon-app/AGENTS.md  → cette app seulement
```

| Niveau | Fichier | Qui l'écrit | Comment ça se propage |
|---|---|---|---|
| Méthode | `~/.claude/CLAUDE.md` (lien vers le hub) | toi, sur ta machine | `stackfast push`, puis `stackfast pull` ailleurs |
| Instance | `mon-vps/AGENTS.md` | toi, dans l'instance | commit dans le repo de l'instance |
| Stack | `mon-vps/stacks/php-htmx/` | **le hub seulement** | `stackfast push` → `pull` + `sync` sur chaque VPS |
| App | `mon-vps/apps/php-htmx/mon-app/` | toi, dans l'instance | commit dans le repo de l'instance |

Point clé : **`stacks/` appartient au hub.** Tu ne l'édites jamais sur un VPS.
Si tu le fais quand même, `stackfast sync` le détecte et s'arrête (section 12).

Codex lit `AGENTS.md`. Claude lit `CLAUDE.md`. Dans l'instance, `CLAUDE.md` est un lien vers `AGENTS.md` : un seul contrat pour les deux outils.

---

## 3. Installer

Sur ta machine et sur chaque VPS :

```sh
curl -fsSL https://raw.githubusercontent.com/julienby/stackfast/main/INIT/install.sh | sh
```

L'installeur fait quatre choses :

1. Il clone le hub dans `~/.stackfast` (ou fait `git pull` s'il existe déjà).
2. Il pose `~/.local/bin/stackfast`, un lien vers le client.
3. Il crée les liens :
   - `~/.claude/CLAUDE.md` → `~/.stackfast/INIT/CLAUDE.global.md`
   - `~/.codex/AGENTS.md` → le même fichier
   - chaque skill de `INIT/skills/` → `~/.claude/skills/<nom>`
4. Si tu donnes un dossier en argument, il crée une instance dedans.

Un fichier déjà présent n'est **jamais** écrasé. Il est renommé en `.bak.<date>`.

Pourquoi des liens et pas des copies ? Parce qu'avec un lien, éditer `~/.claude/CLAUDE.md` **c'est** éditer le hub. `git status` dans `~/.stackfast` te montre ce qui a changé. Avec des copies, tu ne sais plus ce qui a bougé, ni où.

Sur un VPS, le hub est en **lecture seule** : tu y fais `stackfast pull`, jamais `push`.

Pour développer le hub lui-même, réutilise ton clone :

```sh
STACKFAST_HOME="$PWD" sh INIT/install.sh
```

---

## 4. Créer une instance

Une instance = un VPS = un repo git.

```sh
stackfast init /var/www/mon-vps
cd /var/www/mon-vps
cp .env.example .env
```

`init` copie `INIT/instance/` **sans écraser** les fichiers présents, puis lance `sync` pour poser les stacks, puis `git init`.

Contenu d'une instance :

```
mon-vps/
├── AGENTS.md          contrat du VPS (CLAUDE.md = lien vers lui)
├── DECISIONS.md       une ligne par arbitrage
├── .env               secrets (gitignoré), copié depuis .env.example
├── bin/
│   ├── new-app        crée une app depuis le gabarit
│   ├── check          lint + tests
│   └── deploy         git pull + docker compose up
├── .claude/
│   ├── settings.json  déclare le hook Stop
│   ├── hooks/verify.sh
│   └── commands/      /new-app, /feature, /retro
├── proxy/             exemples Caddy et nginx
├── stacks/php-htmx/   posé par sync, appartient au hub
└── apps/php-htmx/     tes apps
```

Réglages de `.env` :

| Variable | Rôle |
|---|---|
| `PROXY_NETWORK` | réseau Docker du proxy (défaut `caddy-net`) |
| `HOST_UID`, `HOST_GID` | ton utilisateur (`id -u`, `id -g`). Apache tourne sous cet UID : les fichiers de `data/` t'appartiennent. |
| `PHP_HTMX_CONTAINER` | nom du conteneur |
| `<SLUG>_API_TOKEN` | un par app, ajouté par `bin/new-app` |

Piège connu : `init` ne remplace jamais un fichier existant. Si le hub change `INIT/instance/AGENTS.md`, ton instance garde l'ancien. Reporte la modif à la main si tu la veux.

---

## 5. Créer une app avec Claude

Dans l'instance, lance Claude Code, puis :

```
/new-app php-htmx mon-app
```

Claude suit `.claude/commands/new-app.md` :

1. Il lance `bin/new-app php-htmx mon-app`.
   - Le gabarit est copié dans `apps/php-htmx/mon-app/`, `__SLUG__` est remplacé.
   - Le token `MON_APP_API_TOKEN` est ajouté à `.env`.
   - Le bloc proxy à coller est affiché.
2. Il lance `bin/check php-htmx mon-app`. Le gabarit doit être vert.
3. Il ouvre `PRD.md` et te pose les questions, **une par une**. Il ne remplit rien seul.
4. Il suit « Première session » de l'`AGENTS.md` de l'app.

Ensuite, une feature à la fois :

```
/feature ajouter une date d'échéance aux items
```

`/feature` impose l'ordre :

1. La feature est-elle dans le `PRD.md` ? Sinon, stop : hors périmètre.
2. Plan dans `PLAN.md`, tu valides.
3. **Tests d'abord** : le métier sans HTTP, puis le HTML rendu.
4. Le minimum de code pour passer les tests.
5. `bin/check` vert, sortie citée, diff montré, ce qui n'est pas testé est dit.
6. `/retro`.

---

## 6. Anatomie d'une app php-htmx

```
apps/php-htmx/mon-app/
├── PRD.md          ce que l'app doit faire (v1)
├── AGENTS.md       règles propres à l'app + Lessons
├── index.php       routage : une ligne par action
├── lib/
│   ├── items.php   métier : fonctions pures
│   ├── api.php     endpoints REST (vide au départ)
│   └── mcp.php     outils MCP (vide au départ)
├── views/
│   ├── layout.php  page complète
│   ├── home.php
│   └── partials/   fragments htmx
├── data/           JSON (jamais servi par Apache)
└── tests/          *_test.php, lancés par bin/check
```

La règle la plus importante : **le métier vit dans `lib/`, en fonctions pures.**
Pas de `$_POST`, pas de `$_SESSION`, pas d'`echo`. Arguments en entrée, valeur ou exception en sortie.

Pourquoi ? Parce qu'une fonction pure :

- se teste sans serveur (`php tests/items_test.php`) ;
- se branche sur trois surfaces sans réécriture : l'interface htmx, `/api`, `/mcp`.

`index.php` ne fait que le pont. Un handler lit la requête, appelle `lib/`, rend une vue.

Les helpers communs viennent de la stack (`stacks/php-htmx/lib/bootstrap.php`, namespace `Stack\`) :

| Helper | Rôle |
|---|---|
| `url('/items')` | lien avec le bon préfixe |
| `path()` | chemin demandé, sans préfixe ni query |
| `method()` | `GET`, `POST`… |
| `render('home', [...])` | page complète, ou fragment si requête htmx |
| `e($x)` | échappement HTML, sur **toute** valeur dans une vue |
| `csrf_token()`, `csrf_check()` | protection des formulaires |
| `json_read()`, `json_write()` | données, écriture atomique |
| `env('CLE')` | lecture de `.env` |
| `session_start_app($slug)` | session avec un cookie propre à l'app |

Une app ne copie jamais ces helpers. Elle fait `use function Stack\{url, render, ...}`.

---

## 7. Une requête, de bout en bout

Tu tapes `https://web.example.com/mon-app/` dans le navigateur.

```
navigateur
   │  GET /mon-app/
   ▼
proxy (Caddy / nginx)      efface le header X-Base-Path venant du client
   │
   ▼
conteneur php:8.5-apache   DocumentRoot = apps/php-htmx
   │  .htaccess : tout vers index.php, sauf lib/ views/ data/ tests/ (403)
   ▼
mon-app/index.php
   │  path() = "/"   method() = "GET"
   │  route "GET /" → items_list() → render('home')
   ▼
render() : pas de header HX-Request → layout.php + home.php (page complète)
```

Tu ajoutes un item. Le formulaire porte `hx-post`. htmx envoie `POST /mon-app/items` avec le header `HX-Request: true`.

```
index.php → route "POST /items"
   │  csrf_check()          première ligne, toujours
   │  items_add($store, …)  métier pur
   ▼
render() : HX-Request présent → seulement views/partials/items.php
```

htmx remplace la liste dans la page. Pas de JSON, pas de JavaScript maison. La réponse HTML **contient** les actions possibles (liens, formulaires). C'est l'hypermedia.

### Le préfixe et `X-Base-Path`

L'app ne connaît jamais son domaine ni son préfixe.

| Accès | Le proxy envoie | `url('/items')` donne |
|---|---|---|
| `web.example.com/mon-app/` | rien (header effacé) | `/mon-app/items` |
| `mon-app.example.com/` | `X-Base-Path: /` | `/items` |

Sans header, `base_path()` déduit le préfixe du chemin du script. Passer une app sur son propre domaine = un bloc dans le proxy, **zéro** changement de code.

Le proxy doit **toujours** effacer ou écraser ce header. Sinon un client choisit lui-même les liens de la page.

---

## 8. `/api` et `/mcp`

Chaque app expose deux surfaces machine, actives dès la création :

- `/api` : REST, JSON.
- `/mcp` : JSON-RPC 2.0, pour un agent IA (Model Context Protocol).

Les deux répondent 200 même vides. Un client découvre toujours la surface.

### Le token

Un seul bearer token par app protège les deux surfaces. Son nom suit une règle fixe :

```
slug en majuscules, "-" devient "_", puis "_API_TOKEN"
mon-app → MON_APP_API_TOKEN
```

`bin/new-app` l'écrit dans `.env`. Le conteneur lit `.env` au démarrage : après un changement, relance `bin/deploy php-htmx`.

```sh
TOKEN=$(grep MON_APP_API_TOKEN .env | cut -d= -f2)
curl https://web.example.com/mon-app/api                                   # 401
curl -H "Authorization: Bearer $TOKEN" https://web.example.com/mon-app/api # 200
```

Token vide ou absent dans `.env` → 401 pour tout le monde. C'est voulu.

### Ajouter un endpoint REST

Dans `lib/api.php`, sans toucher `index.php` :

```php
function api_endpoints(): array
{
    $store = APP . '/data/items.json';
    return [
        'GET /items' => fn () => ['items' => items_list($store)],
    ];
}
```

La clé est `MÉTHODE /chemin`, relatif à `/api`. Le callable rend un tableau, sérialisé en JSON.

### Ajouter un outil MCP

Dans `lib/mcp.php` :

```php
function mcp_tools(): array
{
    $store = APP . '/data/items.json';
    return [
        'lister_items' => [
            'description' => 'Liste les items de mon-app',
            'handler' => fn (array $args) => items_list($store),
        ],
    ];
}
```

Test à la main :

```sh
curl -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"jsonrpc":"2.0","id":1,"method":"tools/list"}' \
  https://web.example.com/mon-app/mcp
```

Remarque : les deux exemples appellent `items_list()`, la même fonction que l'interface. C'est le bénéfice du métier pur : une surface de plus = un adaptateur de trois lignes.

Le dispatch lui-même vit dans la stack (`stacks/php-htmx/lib/mcp_api.php`). Une notification JSON-RPC (sans `id`) reçoit 202 sans corps. Une méthode inconnue reçoit l'erreur `-32601`.

---

## 9. Vérifier : `bin/check` et le hook

```sh
bin/check                   # toutes les stacks
bin/check php-htmx          # une stack
bin/check php-htmx mon-app  # une app
```

`bin/check` fait deux choses :

1. `php -l` sur chaque fichier PHP de la stack et de l'app.
2. Chaque `tests/*_test.php`.

Sortie : `CHECK OK` et exit 0, ou `CHECK FAILED` et exit 1.

Si `php` n'est pas installé sur l'hôte (cas d'un VPS), `bin/check` lance php **dans le conteneur**. Il traduit chaque chemin : `apps/php-htmx/...` devient `/var/www/html/...`. Le conteneur doit donc tourner.

### Le hook de fin de tour

`.claude/settings.json` déclare un hook `Stop`. À chaque fin de tour, Claude Code lance `.claude/hooks/verify.sh` :

| Situation | Résultat |
|---|---|
| rien de changé dans `apps/` ni `stacks/` | rien ne tourne |
| changement, tests verts | rien à signaler |
| changement, tests rouges | exit 2 : Claude est bloqué et lit la sortie |
| déjà bloqué une fois dans ce tour | Claude peut s'arrêter (pas de boucle) |

Ainsi Claude ne peut pas dire « terminé » avec des tests rouges sans que tu le voies.

Limite : le hook lit `git status`. Un changement déjà commité pendant le tour échappe au hook. Lance `bin/check` avant de commiter.

---

## 10. Déployer derrière un proxy

### Le conteneur

Un seul conteneur `php:8.5-apache` sert **toutes** les apps de la stack.

| Hôte | Conteneur | Mode |
|---|---|---|
| `apps/php-htmx/` | `/var/www/html` | lecture-écriture |
| `stacks/php-htmx/lib/` | `/var/www/stacks/php-htmx/lib` | lecture seule |

Les deux viennent de volumes. Modifier une app ou la lib ne demande **pas** de rebuild. L'image ne contient que PHP, Apache, `sqlite3` et le `vendor/` composer.

Au démarrage, `entrypoint.sh` donne à l'utilisateur `www-data` ton `HOST_UID`/`HOST_GID`. Apache écrit donc `data/` avec tes droits.

Le conteneur n'a **aucun port publié**. Il rejoint seulement le réseau Docker du proxy (`PROXY_NETWORK`).

### Déployer

```sh
docker network create caddy-net   # une fois par machine
bin/deploy php-htmx
```

`bin/deploy` fait `git pull --ff-only` (si l'instance a un remote), puis `docker compose up -d --build`.

### Le proxy

Deux modes, modèles dans `proxy/` :

- **Sous-répertoire** : `web.example.com/mon-app/`. Rien à faire de plus.
- **Domaine dédié** : colle le bloc affiché par `bin/new-app`. Il envoie `X-Base-Path: /`.

---

## 11. Faire vivre les règles (Lessons)

Une ligne de règle n'existe que pour empêcher une erreur **déjà observée**, ou pour fixer un contrat. Pas de règle « au cas où ».

Format :

```
- 2026-09-28 — règle. (erreur observée : ...)
```

Exemple réel, dans `stacks/php-htmx/AGENTS.md` :

```
- 2026-09-16 — toujours garder un guard explicite contre les chaînes vides
  avant un hash_equals() de token : vide == vide passe sinon.
  (erreur observée : csrf_check() acceptait une requête sans cookie ni token.)
```

### La boucle

1. Claude se trompe, tu le corriges (ou un test échoue).
2. En fin de tâche, Claude propose la Lesson en une ligne et demande « ok ? ».
3. `/retro` est le filet : il pose les quatre questions (erreur ? règle ? niveau ? ligne à élaguer ?).
4. Tu choisis le niveau :

| L'erreur touche… | Écris la Lesson dans |
|---|---|
| une app | `apps/<stack>/<slug>/AGENTS.md` |
| deux apps de la même stack | `INIT/stacks/<stack>/AGENTS.md` **dans le hub** |
| deux stacks, ou tous tes projets | `~/.claude/CLAUDE.md` |
| une particularité du VPS | `mon-vps/AGENTS.md` |

Une Lesson **remonte** quand elle se répète. Une ligne qui ne sert à rien est **élaguée** au `/retro`. Le CLAUDE global reste sous ~80 lignes.

---

## 12. Synchroniser : `pull`, `push`, `sync`

Trois commandes, trois cibles différentes.

| Commande | Où la lancer | Ce qu'elle modifie |
|---|---|---|
| `stackfast pull` | n'importe où | le clone `~/.stackfast`, puis les liens |
| `stackfast push "msg"` | ta machine | commit de tout le clone, `pull --rebase`, push |
| `stackfast sync` | dans une instance | `stacks/` de l'instance, rien d'autre |

Piège : `pull` ne touche **pas** l'instance. Après un `pull`, fais `sync` dans chaque instance :

```sh
stackfast pull
cd /var/www/mon-vps && stackfast sync
```

### `push` et deux machines

Tu édites le CLAUDE global sur ton portable et sur ton fixe. Les deux poussent.
`push` fait `pull --rebase` avant de pousser : ton commit passe au-dessus de l'autre. Pas de blocage.

Si les deux ont modifié **la même ligne**, git s'arrête au milieu du rebase. `push` affiche alors :

```
stackfast : pull --rebase a échoué dans ~/.stackfast. Si conflit : corrige les fichiers, git add, git rebase --continue, puis git push (ou git rebase --abort)
```

Tu résous à la main :

```sh
cd ~/.stackfast
git status                # fichiers en conflit
# corrige les fichiers
git add <fichier>
git rebase --continue
git push
```

### `sync` et le manifeste

Chaque `sync` écrit `stacks/<stack>/.stackfast-sums` : la somme sha256 de chaque fichier posé.
La sync suivante recalcule et compare.

| Cas | Résultat |
|---|---|
| Première sync (pas de manifeste) | passe |
| Seul le hub a changé | passe : les nouveaux fichiers remplacent les anciens |
| Un fichier modifié, ajouté ou supprimé **à la main** dans `stacks/` | stop, fichiers listés, rien n'est effacé |
| `sync --force` | écrase tout |
| Fichier supprimé du hub | supprimé aussi dans l'instance |
| `stacks/<stack>/vendor/` | jamais touché, jamais vérifié |

Tu as fait une correction à la main sur le VPS et la sync bloque ? Deux choix :

- **La garder** : reporte-la dans `~/.stackfast/INIT/stacks/...` sur ta machine, `stackfast push`, puis sur le VPS `stackfast pull && stackfast sync --force`.
- **La jeter** : `stackfast sync --force`.

---

## 13. Choisir le modèle

Le CLAUDE global définit trois niveaux :

| Niveau | Pour | Claude | Codex |
|---|---|---|---|
| rapide | renommage, docs, logs, grep large, tests sur un motif existant | Haiku 4.5 | `--profile rapide` |
| standard (défaut) | feature du PRD, bug reproduit par un test | Sonnet 5 | modèle par défaut |
| fort | architecture, plan, bug non reproduit, revue | Opus 5.5 | `--profile fort` |

Quand changer :

- **Monter** : le même `bin/check` reste rouge après 2 corrections, le hook bloque 2 fois de suite, ou une hypothèse est renversée.
- **Descendre** : la tâche devient mécanique et une commande vérifie le résultat.

Aucun outil ne change de modèle tout seul.
Claude délègue à un sous-agent avec le bon modèle, puis reprend la main.
Codex s'arrête et écrit « coincé → `--profile fort` ». Tu relances.

Le hub fournit les profils Codex : `INIT/codex/rapide.config.toml` (effort `low`) et `INIT/codex/fort.config.toml` (effort `high`).
`stackfast link` les relie dans `~/.codex/`. Ils règlent seulement l'effort de raisonnement, pas le modèle.
Format Codex 0.154 : un fichier `<profil>.config.toml` par profil. Une table `[profiles.x]` dans `config.toml` est refusée.

---

## 14. Dépannage

| Symptôme | Cause probable | Action |
|---|---|---|
| `/api` répond 401 avec le bon token | token absent du conteneur | `grep <SLUG>_API_TOKEN .env`, puis `bin/deploy php-htmx` |
| `curl` répond `000` juste après le deploy | Apache démarre | attends 2 s, relance |
| `stackfast sync` s'arrête | modif à la main dans `stacks/` | section 12 : reporter ou `--force` |
| `stackfast push` s'arrête en plein rebase | même ligne modifiée ailleurs | résoudre dans `~/.stackfast`, `git rebase --continue`, `git push` |
| `bin/check` : lint FAIL sur un VPS | pas de php local et conteneur arrêté | `bin/deploy php-htmx`, puis relance |
| fichiers de `data/` appartenant à root | `HOST_UID`/`HOST_GID` faux | corrige `.env` (`id -u`, `id -g`), redéploie |
| `Cannot redeclare function` | un fichier chargé deux fois (autoload + require) | voir la Lesson du 2026-09-16 dans la stack |
| page sans style ni préfixe correct | `X-Base-Path` mal posé par le proxy | compare avec `proxy/caddy/` ou `proxy/nginx/` |
| un autre conteneur `php-htmx` disparaît au deploy | deux instances sur la même machine partagent le nom de projet compose `php-htmx` | corrigé : le nom du projet compose suit `PHP_HTMX_CONTAINER`. Donne une valeur distincte par instance. Instance déjà déployée sous un autre nom : une fois, `docker compose -p php-htmx --env-file .env -f stacks/php-htmx/docker-compose.yml down`, puis `bin/deploy php-htmx` |
| `~/.claude/CLAUDE.md` n'est plus un lien | un outil l'a réécrit | `stackfast link` (l'ancien est sauvegardé en `.bak.*`) |

---

## 15. Aide-mémoire

```sh
# Machine locale
curl -fsSL .../INIT/install.sh | sh        # installer / mettre à jour
stackfast status                           # quoi pousser ?
stackfast push "message"                   # remonter méthode / stacks / skills
stackfast skill add ~/.claude/skills/x     # une skill passe dans le hub

# VPS
stackfast pull                             # hub à jour
stackfast init /var/www/mon-vps            # nouvelle instance
cd /var/www/mon-vps && stackfast sync      # stacks à jour
bin/new-app php-htmx mon-app               # nouvelle app + token
bin/check php-htmx mon-app                 # preuve
bin/deploy php-htmx                        # mise en ligne

# Claude Code, dans l'instance
/new-app php-htmx mon-app
/feature <description>
/retro
```

Les trois réflexes :

1. **Le métier dans `lib/`**, pur. Tout le reste est un adaptateur.
2. **Pas de « terminé » sans `CHECK OK`** cité.
3. **Chaque correction devient une ligne**, au plus bas niveau où elle est vraie.
