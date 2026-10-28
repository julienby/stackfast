# Architecture de stackfast

Une page pour comprendre comment tout s'emboîte. Le pourquoi de chaque choix est dans `DECISIONS.md`.

## Le principe : une cuisine par app, une seule porte d'entrée

```
                   Internet
                      │ 80 / 443
              ┌───────▼────────┐
              │ Caddy (façade) │   le seul élément partagé : TLS, domaines, une route par app
              └───┬───────┬────┘
        réseau    │       │    réseau
        de app1   │       │    de app2           (une app ne voit jamais une autre app)
          ┌───────▼──┐ ┌──▼───────┐
          │  app1    │ │  app2    │   chacune : son dépôt git, son image, son socle, ses libs,
          │ FrankenPHP│ │ FrankenPHP│  sa base SQLite, son .env, ses éventuels conteneurs en plus
          └──────────┘ └──────────┘
```

- **Une app est autonome.** Son dépôt contient tout ce qu'il faut pour la construire, la tester et la faire tourner : `Dockerfile`, `compose.yml`, `Caddyfile`, `php.ini`, son socle (`core/`), ses dépendances (`composer.json`), son contrat pour les agents (`AGENTS.md`, `.claude/`). `docker compose up` suffit à la lancer, sans stackfast.
- **Aucun code partagé entre deux apps.** Le socle et la recette sont **copiés** dans l'app à sa création, avec leur numéro de version. Deux apps peuvent avoir des versions différentes de la recette.
- **Seul Caddy est commun.** Il porte les certificats et une route par app. Docker et la machine sont partagés, comme pour tout serveur.

## Trois rôles pour stackfast, aucun à l'exécution

| Rôle | Ce que c'est | Où |
|---|---|---|
| **Générateur** | les recettes (`php-htmx`…) et plus tard les modules (worker, cron…) | hub : `INIT/stacks/<recette>/template/` |
| **Boîte à outils** | des modes opératoires identiques pour toutes les apps : `new`, `up`, `check`, `deploy`, `audit`, `update`, `backup`, `domain` | serveur : `bin/` de l'instance, appelé par `stackfast <verbe>` |
| **Mémoire** | la méthode, les leçons, les skills : le savoir-faire qui circule entre les apps | hub : `INIT/CLAUDE.global.md`, leçons, skills |

**Le code est copié et versionné. Le savoir-faire est partagé en direct.** Ajouter une lib à la recette ne touche aucune app existante ; une leçon apprise sur une app profite tout de suite aux autres.

## Une app, de près

```
apps/monsite/              un dépôt git à lui ; Claude Code se lance ici
  stackfast.json           recette et version, modules, port
  AGENTS.md  .claude/      contrat complet, hook de fin de tour, commandes, équipe de modèles
  Dockerfile compose.yml   son image et ses conteneurs
  Caddyfile  php.ini       son serveur interne et ses réglages
  core/                    le socle : routage, vues, CSRF, SQLite, API/MCP
  routes.php models/ controllers/ views/   MVC en fonctions
  migrations/              le schéma SQLite, un fichier par étape
  assets/                  Tailwind (compilé), htmx (local)
  tests/  bin/check  bin/build
  data/  .env  vendor/     hors git
```

## Les modes opératoires communs

Toutes les apps répondent au même contrat, quelle que soit leur recette :

| Contrat | Ce que l'app fournit | Ce que fait stackfast |
|---|---|---|
| construire | `Dockerfile`, `bin/build` | `up`, `update` : image, dépendances, CSS |
| vérifier | `bin/check` (lint, tests, marqueurs de conflit) | `check`, et avant chaque mise à jour |
| servir | port interne, santé (`healthcheck`) | une route dans Caddy, `domain` |
| sauvegarder | `data/`, `.env` | `backup`, `restore` |
| surveiller | version PHP, `composer.lock` | `audit` |

## Versions et mises à jour

- `stackfast.json` garde la version de la recette d'origine. Une app y reste tant que tu ne décides pas de monter de version.
- **Correctifs de sécurité** (PHP, Caddy) : `stackfast update` reconstruit l'image de chaque app avec le dernier correctif, sans toucher à son code.
- **Nouvelle version de la recette** : `stackfast upgrade <app>` montre les différences, tu choisis, puis fusion et `check` (phase 4).

## L'équipe de modèles

L'app embarque des sous-agents Claude Code (`.claude/agents/`) : le chef (la session principale, Opus pour les plats complexes) planifie et relit ; les commis (Sonnet) implémentent ; les sous-traitants (Haiku) font le mécanique. `stackfast.json` fixe le modèle par défaut de l'app.

## Performances

FrankenPHP sert l'app (PHP et serveur HTTP dans un seul processus), en **mode worker** par défaut : l'app reste chargée en mémoire et `watch` la recharge dès qu'un fichier change. Mesuré le 28/10 sur 1 CPU : 3 394 pages d'accueil/s en worker contre 1 270 en classique, 4 801 pages de détail/s contre 1 400, p99 sous 30 ms. Le socle tient les deux modes (pas de `exit`, pas d'état global entre requêtes) ; le mode se règle dans le `Caddyfile` de l'app. Pas de session pour un visiteur anonyme, CSS compilé, htmx local, SQLite en WAL.

## Où en est la construction

| Phase | Contenu | État |
|---|---|---|
| 1 | la recette php-htmx autonome, outils serveur adaptés | faite (testée sur Docker, 28/10) |
| 2 | déploiement sans coupure, retour arrière automatique, une route Caddy par fichier | à faire |
| 3 | modules : worker, cron, conteneurs en plus | à faire |
| 4 | leçons vers le hub, `upgrade`, agents en `git worktree` | à faire |
| 5 | migration des projets existants | à faire |
