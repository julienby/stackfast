# stackfast

Ma façon de coder avec une IA, et les recettes d'apps qui vont avec. Ce repo est le **hub** : il ne contient aucune app.
Pour comprendre l'ensemble en une page : [ARCHITECTURE.md](ARCHITECTURE.md).

## Le principe

**Une app = une cuisine autonome** : son dépôt git, son image, son socle, ses libs, sa base SQLite, son contrat pour Claude. Elle se construit, se teste et tourne seule. **Seul le Caddy du serveur est partagé** (TLS, domaines, une route par app).

stackfast n'est pas une dépendance d'exécution. Il a trois rôles :
- **générateur** : les recettes (`php-htmx`), copiées dans l'app à sa création, avec leur version ;
- **boîte à outils** : les mêmes modes opératoires pour toutes les apps (`check`, `update`, `audit`, `backup`…) ;
- **mémoire** : la méthode, les leçons, les skills, partagées en direct entre les apps.

Le code est copié et versionné ; le savoir-faire est partagé.

## Parler au serveur

```sh
curl -fsSL https://raw.githubusercontent.com/julienby/stackfast/main/INIT/install.sh | sh -s -- ~/srv
cd ~/srv && cp .env.example .env                # APPS_HOST, HOST_UID
stackfast new php-htmx monsite                  # une app autonome dans apps/monsite (son dépôt git) → https://apps.domaine.fr/monsite/
cd apps/monsite && claude                       # Claude ne voit que l'app ; /prd, /feature, /retro
stackfast check monsite                         # lint, tests, marqueurs de conflit (le bin/check de l'app)
stackfast domain monsite monsite.fr             # nouvelle adresse, l'ancienne redirige
stackfast ls                                    # apps, santé, adresses
stackfast audit                                 # PHP servi vs dernier correctif, failles composer, sauvegardes
stackfast update                                # faille PHP ou Caddy : reconstruit chaque image, teste, bascule (code inchangé)
stackfast composer monsite require vendor/pkg   # une lib pour cette app seulement
stackfast backup                                # data/ + .env de chaque app, config du serveur, certificats (chiffrable)
stackfast deploy                                # git pull de chaque app, puis tout à jour
```

Aussi : `up`, `down`, `logs`, `shell`, `rollback`, `restore`, `proxy`. `stackfast` sans argument les liste.

## La recette php-htmx

PHP 8.5 sur **FrankenPHP en mode worker** (rechargé à chaud quand un fichier change), **SQLite** (WAL, migrations numérotées), **htmx** et **Tailwind compilé** servis par l'app (aucun CDN), composer pour les libs classiques. MVC en fonctions, sans framework ni classe : `routes.php`, `models/`, `controllers/`, `views/`, `core/`. CSRF sans session, conteneur durci (lecture seule, non root, aucune capability), `/api` et `/mcp` par défaut. Une équipe de sous-agents Claude (commis Sonnet, sous-traitant Haiku, relecteur Opus).

Mesuré sur 1 CPU : environ 3 400 pages d'accueil par seconde (SQLite compris), p99 sous 30 ms, ~45 Mo de RAM par app. Le contrat complet est dans `INIT/stacks/php-htmx/template/AGENTS.md`.

## Client `stackfast`

`install.sh` clone le hub dans `~/.stackfast` et pose `~/.local/bin/stackfast`. Il relie aussi `~/.claude/CLAUDE.md` et `~/.codex/AGENTS.md` à `INIT/CLAUDE.global.md`, et chaque skill de `INIT/skills/`. Un fichier déjà présent est sauvegardé en `.bak.<date>`.

```sh
stackfast status                  # changements locaux + écart avec GitHub
stackfast pull                    # récupère le hub, refait les liens
stackfast push "message"          # commit + pull --rebase + push de ce que j'ai modifié dans le hub
stackfast skill add ~/.claude/skills/ma-skill
stackfast init ~/srv              # crée une instance (dossier serveur) ; stackfast sync met ses outils à jour
```

## Capitalisation

- une erreur sur **une app** → « Leçons de l'app » dans son `AGENTS.md` ;
- valable pour **toutes les apps d'une recette** → `INIT/stacks/<recette>/template/AGENTS.md` dans ce hub (les prochaines apps l'ont ; les existantes via `upgrade`, phase 4) ;
- valable **partout** → `~/.claude/CLAUDE.md` (lien vers `INIT/CLAUDE.global.md`), effet immédiat.

`/retro` propose la ligne et son niveau ; rien n'est écrit sans accord.

## Où en est la construction

Phase 1 (recette autonome, outils serveur) : faite et testée sur Docker. Suite : déploiement sans coupure (2), modules (3), leçons et `upgrade`, agents en `git worktree` (4), migration des anciens projets (5). Voir [ARCHITECTURE.md](ARCHITECTURE.md).

**Docs pas encore à jour** avec les apps autonomes : `docs/TUTORIEL.md`, `docs/tutoriel-visuel.html`, `docs/GETTING-STARTED.md`, `docs/DEPLOY-VPS.md` (elles décrivent l'image partagée par stack et `apps/<stack>/<slug>`). `stackfast agent` est en refonte (phase 4).

## Repo

```
ARCHITECTURE.md          la cible en une page
DECISIONS.md             arbitrages du hub
INIT/install.sh          installeur idempotent du client (curl | sh)
INIT/stackfast           le client : liens, pull/push, init/sync, et les verbes du serveur
INIT/instance/           gabarit du dossier serveur : bin/ (les outils), proxy/ (Caddy), .claude/
INIT/stacks/<recette>/   une recette : stack.json (version) et template/ (l'app autonome copiée par stackfast new)
INIT/skills/  INIT/codex/  INIT/CLAUDE.global.md   la méthode et les outils de travail
docs/                    SECURITE, SAUVEGARDE, AGENTS-ISOLES, DEPLOY-VPS, tutoriels
```
