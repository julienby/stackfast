# stackfast

Ma façon de coder avec une IA, et les stacks qui vont avec. Ce repo est le **hub** : il ne contient aucune app.

## Quatre niveaux

| Niveau | Où | Contient | Qui l'écrit |
|---|---|---|---|
| **Méthode** | `~/.claude/CLAUDE.md` (Codex : `~/.codex/AGENTS.md`) | comment je travaille, Lessons globales | le hub (`INIT/CLAUDE.global.md`) |
| **Instance** | un repo par VPS | `AGENTS.md`, `bin/`, `proxy/`, `.env`, `apps/` | gabarit du hub, puis le VPS |
| **Stack** | `stacks/<stack>/` dans l'instance | conteneur, helpers partagés, `AGENTS.md` de la stack, `app-template/` | **le hub uniquement**, resynchronisé par `stackfast sync` |
| **App** | `apps/<stack>/<slug>/` | `PRD.md`, `AGENTS.md`, `index.php`, `lib/`, `views/`, `data/`, `tests/` | l'app |

Une app tourne sur `https://web.example.com/<slug>/` derrière le proxy (Caddy ou nginx). Lui donner un domaine = un bloc proxy, zéro changement de code.

## Cinq commandes

```sh
curl -fsSL https://raw.githubusercontent.com/julienby/stackfast/main/INIT/install.sh | sh -s -- mon-vps
cd mon-vps && cp .env.example .env
bin/new-app php-htmx demo      # crée apps/php-htmx/demo depuis le gabarit, écrit le token dans .env, imprime le bloc proxy
bin/check php-htmx demo        # lint + tests CLI ; un hook le relance à chaque fin de tour de Claude
bin/deploy php-htmx            # git pull + docker compose up -d --build
```

Dans Claude Code : `/new-app`, `/feature`, `/retro`.

## Client `stackfast`

`install.sh` clone le hub dans `~/.stackfast` et pose `~/.local/bin/stackfast`. Il le relie ensuite :
`~/.claude/CLAUDE.md` et `~/.codex/AGENTS.md` pointent vers `INIT/CLAUDE.global.md`, chaque skill de `INIT/skills/` vers `~/.claude/skills/<skill>`,
`~/.codex/rapide.config.toml` et `~/.codex/fort.config.toml` vers `INIT/codex/` (profils Codex, effort seulement).
Un fichier déjà présent est sauvegardé en `.bak.<date>`, jamais écrasé.

```sh
stackfast status                  # changements locaux + écart avec GitHub
stackfast pull                    # récupère le hub, refait les liens (VPS : seulement ça)
stackfast push "message"          # commit + pull --rebase + push de ce que j'ai modifié en local
stackfast skill add ~/.claude/skills/ma-skill   # la skill passe dans le hub
stackfast init mon-vps            # crée une instance ; stackfast sync la met à jour
stackfast sync [--force]          # recopie bin/, .claude/ et les stacks ; s'arrête si l'un d'eux a été modifié localement
```

Modifier `~/.claude/CLAUDE.md`, c'est modifier le clone : `stackfast push` le remonte, `stackfast pull` le redescend ailleurs.

## Capitalisation

- une erreur sur **une app** → `apps/<stack>/<slug>/AGENTS.md` ;
- la même sur **deux apps** → `INIT/stacks/<stack>/AGENTS.md` dans ce hub, `stackfast push`, puis `stackfast pull && stackfast sync` sur chaque VPS ;
- sur **deux stacks** → `~/.claude/CLAUDE.md` (lien vers `INIT/CLAUDE.global.md`), puis `stackfast push`.

`/retro` propose la ligne et son niveau ; rien n'est écrit sans accord.

## Stacks

- `php-htmx` : PHP 8.5 Apache, htmx 4 + Tailwind CDN, JSON à plat puis SQLite, tests CLI. Chaque app expose aussi `/api` et `/mcp` par défaut (bearer token `<SLUG>_API_TOKEN`). Voir `INIT/stacks/php-htmx/AGENTS.md`.
- `python` : à venir, même moule.

## Repo

```
INIT/install.sh          installeur idempotent du client (curl | sh)
INIT/stackfast           le client : liens global + skills, pull/push, init/sync d'instance
INIT/skills/<skill>/     mes skills, reliées dans ~/.claude/skills/
INIT/codex/              profils Codex rapide/fort, reliés dans ~/.codex/
INIT/CLAUDE.global.md    la méthode
INIT/instance/           gabarit d'un VPS (copié sans écraser ; bin/ et .claude/ resynchronisés par sync)
INIT/stacks/<stack>/     une stack (copiée en écrasant, sauf modification locale détectée)
DECISIONS.md             arbitrages du hub
docs/PRD.md              le pourquoi de cette organisation
docs/GETTING-STARTED.md  tester en local, sans VPS ni domaine
docs/TUTORIEL.md         tout comprendre, de bout en bout
docs/DEPLOY-VPS.md       déployer en prod (proxy en dehors du repo, config Caddy/nginx en référence)
```
