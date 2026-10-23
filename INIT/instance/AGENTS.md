# Instance — contrat du VPS

> Un VPS = ce repo. Plusieurs stacks (`stacks/`), plusieurs apps (`apps/<stack>/<slug>/`), **un conteneur par app**, un Caddy devant (`proxy/`).
> Trois niveaux de règles, lus dans cet ordre : `~/.claude/CLAUDE.md` (méthode) → `stacks/<stack>/AGENTS.md` (stack) → `apps/<stack>/<slug>/AGENTS.md` (app).
> `CLAUDE.md` est un lien vers ce fichier : un contrat, tous les clients.
> On parle à l'instance par `stackfast <commande>` (= `bin/<commande>`), jamais par `docker` à la main.

## Si tu dois…

- **créer une app** → `stackfast new <stack> <slug>` : gabarit, `.env` et token, conteneur démarré, en ligne sur `https://$APPS_HOST/<slug>/`. Puis lis son `PRD.md` et suis « Première session » de son `AGENTS.md`. Ne crée jamais une app à la main.
- **travailler sur une app** → lis d'abord `stacks/<stack>/AGENTS.md`, puis l'`AGENTS.md` et le `PRD.md` de l'app. Ne lis pas les autres apps ni leur `.env`.
- **vérifier** → `stackfast check [slug]`. Cite la sortie. Un hook la relance à chaque fin de tour sur les apps touchées.
- **voir l'état** → `stackfast ls` (apps, santé, adresses) ; `stackfast logs <slug>` (erreurs PHP comprises).
- **donner une adresse à une app** → `stackfast domain <slug> <domaine>` : le domaine devient l'adresse, l'ancien chemin et les anciens domaines redirigent (301). Zéro changement de code. Ne modifie pas `proxy/generated.caddy`.
- **appliquer un correctif de sécurité** (PHP, Caddy) → `stackfast update` puis `stackfast audit`. Voir `docs/SECURITE.md` du hub.
- **déployer** → `stackfast deploy` (git pull + toutes les apps + proxy).
- **ajouter un secret** → `.env` de l'app (gitignoré), puis `stackfast up <slug>`. Le `.env` de l'instance ne contient que des réglages d'instance.
- **ajouter un conteneur à une app** (base de données, worker…) → seulement si l'architecture l'impose : un service dans le `compose.yml` de l'app (modèle commenté), réseau `back`, une décision dans l'`AGENTS.md` de l'app.
- **prendre une décision d'architecture** → une ligne dans `DECISIONS.md`, après validation.
- **placer une règle** → si elle reste vraie sur une autre stack, elle va dans la méthode (`~/.claude/CLAUDE.md`), sinon dans la stack. Le code propre à une stack vit dans `stacks/<stack>/` (`check`, `audit`, `post-new-app`, `stack.conf`), jamais dans `bin/`.

## Ne fais pas

- Ne modifie pas `stacks/`, `bin/`, `.claude/` (sauf `settings.local.json`), `proxy/compose.yml` : ils appartiennent au hub `stackfast`. Une règle de stack se propose via `/retro`, s'écrit dans le hub, puis `stackfast pull && stackfast sync` est lancé ici.
- Ne partage rien entre deux apps autrement que par la stack. Une app n'inclut pas les fichiers d'une autre, ne joint pas son conteneur.
- Ne commite jamais `.env`, `data/`, `PLAN.md`, `proxy/generated.caddy`.

## Lessons de l'instance

- _(vide)_ — règles propres à ce VPS uniquement (proxy, réseau, machine). Format : `- YYYY-MM-DD — règle. (erreur observée : ...)`.
