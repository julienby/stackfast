# CLAUDE.md — Projet : app PHP / htmx 4 / Tailwind

> Chargé uniquement dans ce projet, en plus de `~/.claude/CLAUDE.md` (la méthode). Ne répète pas la méthode ici.
> Ici : la stack, la structure du repo, la boucle d'amélioration et les Lessons de CE projet.
> Fichier canonique : `AGENTS.md` (lu par Codex, Kimi, Cursor…). `CLAUDE.md` est un lien symbolique. Un contrat, tous les clients.

---

## Documents du repo (source de vérité, dans cet ordre)

- **`PRD.md`** — le produit : problème, utilisateurs, périmètre v1, hors périmètre, critères de succès.
  Si une demande contredit le PRD, dis-le avant de coder. Si le PRD est incomplet, pose les questions, ne comble pas en silence.
- **`DECISIONS.md`** — une ligne par décision d'architecture (date, choix, pourquoi, alternative écartée). On y écrit quand je tranche.
- **`PLAN.md`** (gitignored) — tâche en cours + avancement. Survit à `/clear`.
- **Lessons** (en bas de ce fichier) — erreurs observées → règles.

## Première session (repo vide + CLAUDE.md + PRD.md)

1. Lis `PRD.md`. Liste tes questions et hypothèses. Attends mes réponses.
2. Propose le découpage v1 en tâches livrables (chacune testable en 1 session). Attends validation.
3. Lance `/bootstrap` : squelette, `make check` vert sur un projet vide, CI, premier commit.
4. Aucune feature avant que l'étape 3 soit prouvée verte.

## Stack

- **PHP 8.4**, `declare(strict_types=1)` partout, Composer + PSR-4 pour l'autoload, rien d'autre.
  **Aucun framework.** L'hypermédia (HATEOAS + htmx) est déjà la structure de l'application. Objectif : lisible par un humain et par un agent.
- **htmx 4, hypermedia first.** Un endpoint renvoie du HTML : page complète, ou fragment si header `HX-Request`.
  JS minimal : pas de framework, pas de build node. Chaque action possible est un lien ou un formulaire **dans la réponse**.
- **Agentification first.** Demain l'utilisateur est un agent (Claude/GPT via un serveur MCP), pas un humain devant une UI.
  Donc : toute logique métier vit dans `src/Domain/` sous forme de **cas d'usage appelables sans HTTP** (une classe, une méthode, entrées/sorties typées).
  Les handlers HTTP sont des adaptateurs fins : ils parsent la requête, appellent le cas d'usage, rendent du HTML.
  Un futur serveur MCP sera un second adaptateur sur les **mêmes** cas d'usage, sans toucher au Domain ni casser la structure.
  Test de lisibilité : la liste des fichiers de `src/Domain/` doit se lire comme la liste des outils MCP futurs.
- **Tailwind via CDN** pour commencer (`<script src="https://cdn.tailwindcss.com">`). Passage au binaire CLI seulement quand on optimise, et je le demande.
- **SQLite via PDO.** SQL explicite, pas d'ORM. Migrations = fichiers `db/migrations/NNN_nom.sql`, appliqués par `bin/migrate`.
  Avant tout changement de schéma : décris la migration et son impact, attends validation.
- **Qualité, au plus simple** : `php -l` sur tous les fichiers + **PHPUnit**. C'est tout. **`make check` = lint + phpunit.**
  Rien n'est « fini » tant que `make check` ne passe pas. Cite la sortie, ne la résume pas. PHPStan viendra si je le demande.
- **CI** : un seul job = `make check` (GitHub Actions ou GitLab CI, fichiers fournis).
  Le run (VPS, Docker, Caddy, TLS) est **hors repo** : ne génère aucune config d'infra.

## Structure (proposée, adapte si je tranche autrement)

```
public/index.php        front controller, unique point d'entrée
src/Http/               routes + handlers fins (adaptateur HTTP → Domain → HTML)
src/Domain/             cas d'usage métier, zéro HTTP, zéro SQL : la future surface MCP
src/Db/                 PDO + requêtes SQL explicites
templates/              pages
templates/partials/     fragments htmx
db/migrations/          001_xxx.sql
tests/                  miroir de src/, tests HTTP sur le HTML rendu
bin/                    scripts (migrate, serve)
```

## Workflow par tâche

- `/feature <nom>` : PRD → plan → tests → implémentation → `make check` → diff → `/retro`.
- Chaque feature = un cas d'usage Domain testé **sans HTTP**, puis un handler qui le rend en HTML.
- Tests HTTP = on vérifie le **HTML rendu** (présence du fragment, des liens/formulaires attendus), pas juste le 200.
- Fin de tâche : montre le diff, liste ce qui est vérifié, dis ce que tu n'as **pas** pu tester.
- Tâche suivante indépendante → je fais `/clear`. L'état vit dans le code commité + `PLAN.md`.

## Boucle d'amélioration

- Mécanisme défini dans `~/.claude/CLAUDE.md` : tu proposes la Lesson à chaque `/retro`, je valide, tu l'écris ci-dessous.

---

## Lessons (journal des corrections — spécifiques à ce projet)

- _(vide pour l'instant)_
