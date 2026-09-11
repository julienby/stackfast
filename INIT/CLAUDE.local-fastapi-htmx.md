# CLAUDE.md — Projet : app Python / FastAPI / HTMX

> Ce fichier vit à la racine du repo et est chargé **uniquement dans ce projet**.
> Il s'ajoute à mon `~/.claude/CLAUDE.md` global (méthode de travail) — ne répète pas ces règles ici.
> Ici : la stack de CE projet + les Lessons de CE projet.

---

## Stack

- **Python + FastAPI** côté serveur. API pensée **HATEOAS** : les réponses portent les liens/actions disponibles, le client suit l'hypermédia, on ne hardcode pas les transitions d'état côté front.
- **HTMX + HTML + Tailwind** côté vue. **Le minimum de JS.** Pas de framework front, pas de build JS lourd. Si une interaction se fait en HTMX, on ne la fait pas en JS.
- **SQLite** comme base. SQL explicite et lisible privilégié ; pas d'ORM lourd sans justification.
- **Docker** pour le packaging applicatif.
- **Le reverse proxy (Caddy) est géré à part.** Ne génère aucune config proxy, TLS, ou routing réseau dans l'app. On ne mélange pas les responsabilités.

## Architecture & style

- **Code modulaire** : chaque module a une responsabilité claire et lisible isolément.
- Structure attendue (adapte si le repo diffère, ne l'impose pas) :
  - routes/endpoints FastAPI fins → délèguent à la logique métier
  - logique métier isolée des détails HTTP et SQL
  - templates HTML + partials HTMX séparés
- Endpoints HTMX : renvoient des **fragments HTML**, pas du JSON, sauf API explicitement REST/JSON.
- Noms explicites. Pas d'abréviation cryptique.

## Workflow

- Réponses HTMX = fragments testables : vérifie le HTML rendu, pas juste le code 200.
- Avant un changement de schéma SQLite : décris la migration et son impact, attends validation.
- À la fin d'une tâche : montre le diff, liste ce qui a été vérifié, signale ce que tu n'as PAS pu tester.

---

## Lessons (journal des corrections — spécifiques à ce projet)

> On remplit cette section au fil de l'eau. Quand tu fais une erreur, après correction
> je te demande d'y ajouter la règle qui l'aurait évitée. C'est ça qui fait vivre le fichier.
> Si une leçon se répète sur plusieurs projets, elle remonte dans le global.

- _(vide pour l'instant)_
