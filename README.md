# stackfast

Ma façon de coder avec une IA, et les stacks qui vont avec. Ce repo est le **hub** : il ne contient aucune app.

## Quatre niveaux

| Niveau | Où | Contient | Qui l'écrit |
|---|---|---|---|
| **Méthode** | `~/.claude/CLAUDE.md` (Codex : `~/.codex/AGENTS.md`) | comment je travaille, Lessons globales | le hub (`INIT/CLAUDE.global.md`) |
| **Instance** | un repo par VPS | `AGENTS.md`, `bin/`, `proxy/`, `.env`, `apps/` | gabarit du hub, puis le VPS |
| **Stack** | `stacks/<stack>/` dans l'instance | conteneur, helpers partagés, `AGENTS.md` de la stack, `app-template/` | **le hub uniquement**, resynchronisé par `install.sh` |
| **App** | `apps/<stack>/<slug>/` | `PRD.md`, `AGENTS.md`, `index.php`, `lib/`, `views/`, `data/`, `tests/` | l'app |

Une app tourne sur `https://web.example.com/<slug>/` derrière le proxy (Caddy ou nginx). Lui donner un domaine = un bloc proxy, zéro changement de code.

## Cinq commandes

```sh
curl -fsSL https://raw.githubusercontent.com/julienby/stackfast/main/INIT/install.sh | sh -s -- mon-vps
cd mon-vps && cp .env.example .env
bin/new-app php-htmx demo      # crée apps/php-htmx/demo depuis le gabarit, imprime le bloc proxy
bin/check php-htmx demo        # lint + tests CLI ; un hook le relance à chaque fin de tour de Claude
bin/deploy php-htmx            # git pull + docker compose up -d --build
```

Dans Claude Code : `/new-app`, `/feature`, `/retro`.

## Capitalisation

- une erreur sur **une app** → `apps/<stack>/<slug>/AGENTS.md` ;
- la même sur **deux apps** → `INIT/stacks/<stack>/AGENTS.md` dans ce hub, puis `install.sh` relancé sur chaque VPS ;
- sur **deux stacks** → `INIT/CLAUDE.global.md`.

`/retro` propose la ligne et son niveau ; rien n'est écrit sans accord.

## Stacks

- `php-htmx` : PHP 8.4 Apache, htmx 4 + Tailwind CDN, JSON à plat puis SQLite, tests CLI. Chaque app expose aussi `/api` et `/mcp` par défaut (bearer token `<SLUG>_API_TOKEN`). Voir `INIT/stacks/php-htmx/AGENTS.md`.
- `python` : à venir, même moule.

## Repo

```
INIT/install.sh          installeur idempotent (global + instance + stacks)
INIT/CLAUDE.global.md    la méthode
INIT/instance/           gabarit d'un VPS (copié sans écraser)
INIT/stacks/<stack>/     une stack (copiée en écrasant)
DECISIONS.md             arbitrages du hub
docs/PRD.md              le pourquoi de cette organisation
docs/GETTING-STARTED.md  tester en local, sans VPS ni domaine
docs/DEPLOY-VPS.md       déployer en prod (proxy en dehors du repo, config Caddy/nginx en référence)
```
