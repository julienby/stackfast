# Sandbox d'agent — __SLUG__ / __NAME__

> Tu travailles seul sur l'app **__SLUG__** (stack __STACK__), dans une sandbox. Lis ensuite `stacks/__STACK__/AGENTS.md`, puis `apps/__STACK__/__SLUG__/AGENTS.md` et son `PRD.md`.
> Ce fichier, `stacks/` et `.claude/` sont en lecture seule.

## Ce que tu as, ce que tu n'as pas

- **Écriture** : `apps/__STACK__/__SLUG__/` seulement (aussi montée en `/app`). Seuls ses changements reviendront dans l'instance ; tout le reste est ignoré.
- **Lecture seule** : `stacks/__STACK__/` (helpers, règles, gabarit). Une règle de stack à changer se propose dans ta réponse finale, jamais ici.
- **Absent** : les autres apps, les `.env` de l'instance, docker, les commandes `stackfast`. Ne cherche pas à les contourner.
- **Données** : `data/` est vide et à toi (jetable). **Secrets** : seulement ceux de l'environnement (`env`), dont le token `/api` + `/mcp` propre à cette sandbox.
- **Réseau** : Internet seulement (CDN, API). Rien d'autre n'est joignable.

## Comment travailler

- Ton app tourne déjà en preview : `http://127.0.0.1:8080/` dans la sandbox (`curl`), et pour l'humain sur __PREVIEW__ (derrière un mot de passe). Le code est lu à chaud : pas de redémarrage.
- Vérifier : `stacks/__STACK__/run-checks` (lint + tests). Un hook le relance à chaque fin de tour ; vert cité en fin de tâche.
- Suis le `PRD.md` : ce qui n'y est pas n'existe pas. Une feature à la fois, tests d'abord.
- `git` est local à la sandbox (historique à toi, jamais lu par l'hôte) : commite par étape si ça t'aide.
- Fin de tâche : résume ce qui a changé, ce qui n'a pas pu être testé, et les décisions à valider. L'humain relit avec `stackfast agent diff` puis fusionne avec `stackfast agent apply`.
