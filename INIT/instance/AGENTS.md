# Instance — contrat du serveur

> Une instance = le dossier serveur : le Caddy partagé (`proxy/`), les outils (`bin/`, appelés par `stackfast <verbe>`), et `apps/` où chaque app est **un dépôt git autonome**.
> Ici, on exploite le serveur. **On ne code pas une app depuis ici** : `cd apps/<slug> && claude`, où Claude ne voit que l'app et suit son propre `AGENTS.md`.
> `CLAUDE.md` est un lien vers ce fichier. Architecture : `ARCHITECTURE.md` du hub (`~/.stackfast`).

## Si tu dois…

- **créer une app** → `stackfast new <recette> <slug>` (aujourd'hui `php-htmx`). Puis `cd apps/<slug> && claude` et `/prd`.
- **voir l'état** → `stackfast ls` ; `stackfast logs <slug>` ; `stackfast audit`.
- **vérifier une app** → `stackfast check <slug>` (lance son `bin/check`).
- **démarrer ou appliquer un changement de `.env` / `compose.yml`** → `stackfast up <slug>`.
- **donner une adresse** → `stackfast domain <slug> <domaine>` (les anciennes adresses redirigent en 301). Ne modifie pas `proxy/generated.caddy`.
- **corriger une faille PHP ou Caddy** → `stackfast update`, puis `stackfast audit`. Le code des apps n'est pas touché.
- **déployer** → `stackfast deploy [slug]` (git pull de chaque app, puis up).
- **sauvegarder / restaurer** → `stackfast backup`, `stackfast restore <archive> [slug]`.
- **ajouter une lib à une app** → `stackfast composer <slug> require vendor/paquet`.
- **prendre une décision d'architecture du serveur** → une ligne dans `DECISIONS.md`, après validation.

## Ne fais pas

- Ne lis pas les `.env`, `data/` ni les archives de sauvegarde des apps : ils contiennent les secrets et les données (refusé par `.claude/settings.json`).
- Ne modifie pas `bin/`, `.claude/`, `proxy/compose.yml` : ils viennent du hub (`stackfast pull && stackfast sync`).
- Ne copie jamais du code d'une app dans une autre : une amélioration utile à toutes passe par la recette du hub, puis `stackfast upgrade` (phase 4).
- Ne commite jamais `.env`, `proxy/generated.caddy`, `backups/`. `apps/` n'est pas suivi par ce dépôt.

## Leçons de l'instance

- _(vide)_ — règles propres à ce serveur (proxy, réseau, machine). Format : `- AAAA-MM-JJ — règle. (erreur observée : …)`.
