# Instance — contrat du VPS

> Un VPS = ce repo. Plusieurs stacks (`stacks/`), plusieurs apps (`apps/<stack>/<slug>/`), un proxy devant (Caddy ou nginx, hors repo).
> Trois niveaux de règles, lus dans cet ordre : `~/.claude/CLAUDE.md` (méthode) → `stacks/<stack>/AGENTS.md` (stack) → `apps/<stack>/<slug>/AGENTS.md` (app).
> `CLAUDE.md` est un lien vers ce fichier : un contrat, tous les clients.

## Si tu dois…

- **créer une app** → `bin/new-app <stack> <slug>`, puis lis `apps/<stack>/<slug>/PRD.md` et suis « Première session » de son `AGENTS.md`. Ne crée jamais une app à la main.
- **travailler sur une app** → lis d'abord `stacks/<stack>/AGENTS.md`, puis l'`AGENTS.md` et le `PRD.md` de l'app. Ne lis pas les autres apps.
- **vérifier** → `bin/check [stack] [slug]`. Cite la sortie. Un hook la relance à chaque fin de tour.
- **déployer** → `bin/deploy <stack>`. Rien d'autre (pas de `docker` à la main sur le VPS).
- **exposer une app sur un domaine** → un bloc dans le proxy, modèle dans `proxy/`. Zéro changement de code.
- **ajouter un secret** → `.env` (gitignoré), documenté dans `.env.example` sans valeur.
- **prendre une décision d'architecture** → une ligne dans `DECISIONS.md`, après validation.

## Ne fais pas

- Ne modifie pas `stacks/` : il appartient au hub `ma-stack-ia`. Une règle de stack se propose via `/retro`, s'écrit dans le hub, puis `install.sh` est relancé ici.
- Ne partage rien entre deux apps autrement que par la stack. Une app n'inclut pas les fichiers d'une autre.
- Ne commite jamais `.env`, `data/`, `vendor/`, `PLAN.md`.

## Lessons de l'instance

- _(vide)_ — règles propres à ce VPS uniquement (proxy, réseau, machine). Format : `- YYYY-MM-DD — règle. (erreur observée : ...)`.
