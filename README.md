# ma-stack-ia

Ma façon de travailler avec un agent de code (Claude Code, Codex, Kimi…), installable en une ligne.

```sh
curl -fsSL https://raw.githubusercontent.com/julienby/ma-stack-ia/main/INIT/install.sh | sh -s -- mon-projet
cd mon-projet        # remplir PRD.md, puis lancer claude ou codex
```

Le principe : **l'idée n'est rien, l'exécution compte.** Un projet = un répertoire, un contrat, un PRD, et une boucle qui apprend de ses erreurs.

---

## Ce que l'installeur pose

| Où | Quoi | Rôle |
|---|---|---|
| `~/.claude/CLAUDE.md`, `~/.codex/AGENTS.md` | `INIT/CLAUDE.global.md` | **La méthode**, indépendante de la stack. Identique sur tous mes projets. |
| `mon-projet/AGENTS.md` (+ lien `CLAUDE.md`) | `INIT/php-htmx/AGENTS.md` | **La stack** de ce projet + ses Lessons. |
| `mon-projet/PRD.md` | gabarit | Le produit en une page. Ce qui n'y est pas n'existe pas. |
| `mon-projet/DECISIONS.md` | gabarit | Une ligne par arbitrage d'architecture. |
| `mon-projet/.claude/commands/` | `/bootstrap`, `/feature`, `/retro` | Les routines. |
| `Makefile`, CI GitHub + GitLab | `make check` | Un seul job : lint + tests. Rien n'est fini tant qu'il n'est pas vert. |

L'installeur n'écrase jamais un fichier déjà présent dans le projet. Un global existant est sauvegardé avant remplacement.

## La méthode (résumé du global)

- **Je pilote.** L'agent propose, je tranche. Plan mode pour l'archi, le schéma, une dépendance, l'irréversible.
- **Simple, chirurgical, prouvé.** Le minimum de code, rien d'orthogonal touché, aucun « terminé » sans sortie de tests citée.
- **Avis honnête.** L'agent me contredit une fois, avec l'argument. Puis j'arbitre.
- **`PLAN.md`** (gitignored) porte l'état d'une tâche. Tâche commitée → `/clear`.

## Le kit `php-htmx`

- PHP 8.4, Composer pour l'autoload, **aucun framework**. L'hypermédia (HATEOAS + htmx 4) est la structure.
- **Agentification first** : la logique métier vit dans `src/Domain/` en cas d'usage appelables sans HTTP. Les handlers HTTP sont des adaptateurs fins. Un serveur MCP viendra se brancher sur les mêmes cas d'usage sans rien casser.
- SQLite via PDO, SQL explicite. Tailwind par CDN en v1. Qualité : `php -l` + PHPUnit.
- Le run (VPS, Docker, Caddy) est hors repo.

## La boucle d'amélioration

1. Je demande une feature → `/feature nom` : PRD → plan → tests → code → `make check` → diff.
2. Fin de tâche → `/retro` : **l'agent propose lui-même** la Lesson qui aurait évité l'erreur observée, et une ligne à élaguer.
3. Je valide → il l'écrit dans les Lessons du projet.
4. Une Lesson qui revient sur deux projets remonte dans le global.

Règle de Cherny : une ligne n'existe que pour empêcher une erreur déjà observée. Global sous ~80 lignes, projet sous ~100.

## Ajouter une stack

Un nouveau dossier dans `INIT/` (ex. `INIT/python-fastapi/`) avec son `AGENTS.md`, puis :

```sh
curl -fsSL .../install.sh | sh -s -- mon-projet python-fastapi
```

## Layout du dépôt

```
INIT/install.sh              l'installeur
INIT/CLAUDE.global.md        la méthode
INIT/CLAUDE.global.v1-backup.md   ancienne version, pour mémoire
INIT/php-htmx/               le kit projet
```
