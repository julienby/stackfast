# stackfast

Ma façon de coder avec une IA, et les stacks qui vont avec. Ce repo est le **hub** : il ne contient aucune app.

## Quatre niveaux

| Niveau | Où | Contient | Qui l'écrit |
|---|---|---|---|
| **Méthode** | `~/.claude/CLAUDE.md` (Codex : `~/.codex/AGENTS.md`) | comment je travaille, Lessons globales | le hub (`INIT/CLAUDE.global.md`) |
| **Instance** | un repo par VPS | `AGENTS.md`, `bin/`, `proxy/` (Caddy), `.env`, `apps/` | gabarit du hub, puis le VPS |
| **Stack** | `stacks/<stack>/` dans l'instance | image, base du conteneur, helpers partagés, `AGENTS.md` de la stack, `app-template/` | **le hub uniquement**, resynchronisé par `stackfast sync` |
| **App** | `apps/<stack>/<slug>/` | `PRD.md`, `AGENTS.md`, `compose.yml`, `.env`, `index.php`, `lib/`, `views/`, `data/`, `tests/` | l'app |

**Un conteneur par app**, tous construits sur l'image de leur stack, un seul Caddy devant. Une app naît sur `https://apps.domaine.fr/<slug>/`, puis passe à `<slug>.domaine.fr`, puis à son propre domaine : une commande à chaque fois, zéro changement de code, les anciennes adresses redirigent.

## Parler à l'instance

```sh
curl -fsSL https://raw.githubusercontent.com/julienby/stackfast/main/INIT/install.sh | sh -s -- mon-vps
cd mon-vps && cp .env.example .env              # APPS_HOST, HOST_UID
stackfast new php-htmx demo                     # gabarit + token + conteneur + proxy → https://apps.domaine.fr/demo/
stackfast check demo                            # lint + tests dans l'image ; un hook le relance à chaque fin de tour de Claude
stackfast domain demo demo.domaine.fr           # l'app est mûre : nouvelle adresse, l'ancienne redirige
stackfast ls                                    # apps, santé, adresses
stackfast audit                                 # PHP servi vs dernier correctif, apps à jour
stackfast update                                # faille PHP publiée : reconstruit, teste toutes les apps, bascule
stackfast deploy                                # sur le VPS après un push : git pull + tout à jour
```

Aussi : `up`, `down`, `logs`, `shell`, `rollback`, `proxy`, `migrate`. `stackfast` sans argument les liste.

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

- `php-htmx` : PHP 8.5 sur FrankenPHP (un conteneur durci par app), htmx 4 + Tailwind CDN, JSON à plat puis SQLite, tests CLI. Chaque app expose aussi `/api` et `/mcp` par défaut (bearer token `<SLUG>_API_TOKEN`). Voir `INIT/stacks/php-htmx/AGENTS.md`.
- `python` : à venir, même moule.

## Repo

```
INIT/install.sh          installeur idempotent du client (curl | sh)
INIT/stackfast           le client : liens global + skills, pull/push, init/sync d'instance
INIT/skills/<skill>/     mes skills, reliées dans ~/.claude/skills/ (`explain` : page HTML dans ~/.cache/explain/)
INIT/codex/              profils Codex rapide/fort, reliés dans ~/.codex/
INIT/CLAUDE.global.md    la méthode
INIT/instance/           gabarit d'un VPS (copié sans écraser ; bin/ et .claude/ resynchronisés par sync)
INIT/stacks/<stack>/     une stack (copiée en écrasant, sauf modification locale détectée)
DECISIONS.md             arbitrages du hub
docs/PRD.md              le pourquoi de cette organisation
docs/GETTING-STARTED.md  tester en local, sans VPS ni domaine
docs/TUTORIEL.md         tout comprendre, de bout en bout
docs/DEPLOY-VPS.md       déployer en prod, migrer une instance de l'ancien modèle
docs/SECURITE.md         une faille PHP est annoncée : update + audit
```
