# Mode d'emploi — CLAUDE.md

Ce dépôt utilise un fichier `CLAUDE.md` comme **contrat de travail** entre Julien et
Claude Code. Ce README explique comment il fonctionne et comment le faire vivre.

---

## À quoi sert `CLAUDE.md`

C'est un **journal de contraintes**, pas une documentation. Claude Code le charge
automatiquement au début de chaque session : les règles s'appliquent sans que tu aies
à les rappeler. La philosophie (d'après les méthodes Cherny & Karpathy) tient en une phrase :

> On n'ajoute une règle que pour empêcher une erreur **déjà observée**.
> Si une règle ne répond pas à « quelle erreur précise ça évite ? », elle ne devrait pas être là.

Objectif : rester sous ~100 lignes, élaguer régulièrement.

---

## Ce que le fichier encode

| Section | Ce qu'elle garantit |
|---|---|
| **Le pilote, c'est moi** | Julien tranche l'architecture. Claude propose, n'impose pas. |
| **Think before coding** | Hypothèses explicites, questions si ambigu, jamais de devinette silencieuse. |
| **Simplicity first** | Le minimum de code, zéro abstraction spéculative, zéro dépendance injustifiée. |
| **Surgical changes** | On ne touche que ce que la tâche exige. Pas de « pendant que j'y suis ». |
| **Goal & test-driven** | Critères de succès vérifiables. Un bug = un test qui le reproduit d'abord. |
| **No laziness** | Cause racine, pas contournement. Pas de `# TODO` qui masque le vrai problème. |
| **Stack** | FastAPI + HATEOAS, HTMX/Tailwind (JS minimal), SQLite, Docker. Caddy géré à part. |
| **Économie de contexte** | Lecture ciblée, `PLAN.md` tampon, délégation du mécanique à un modèle moins cher. |
| **Lessons** | Le journal vivant : chaque erreur corrigée devient une règle. |

---

## Les deux fichiers du système

- **`CLAUDE.md`** — tu le maintiens à la main. C'est le seul.
- **`PLAN.md`** — Claude le crée et le met à jour tout seul. Il contient le plan validé
  d'une tâche en cours + son avancement. Il te permet de couper une session et de repartir
  sans re-briefer (« reprends PLAN.md »). **À gitignorer.**

---

## La boucle de travail

1. Tu demandes une tâche non triviale → Claude passe en **plan mode**, présente le plan.
2. Tu valides → Claude écrit le plan dans `PLAN.md` et exécute.
3. Tâche finie → tu vérifies le diff → **commit**.
4. Tâche suivante indépendante → **`/clear`** (contexte frais, coût réparti).

**Tâche indépendante** ≠ code indépendant. Le test : si un nouveau dev pouvait attaquer
la tâche suivante avec juste le repo + une phrase de consigne (sans lire l'historique du
chat), alors c'est indépendant → `/clear`. Le résultat d'une tâche finie vit dans le code
commité, pas dans la conversation.

---

## Maîtriser les coûts (Opus 4.8)

Le coût vient surtout du **contexte relu**, pas du code écrit. Trois réflexes :

1. **`/clear` entre tâches indépendantes** — le levier n°1. `PLAN.md` rend ça indolore.
2. **`/compact` avec instruction** quand tu dois continuer :
   `/compact garde le plan, les décisions d'archi et les fichiers modifiés`.
3. **`/model sonnet` les journées mécaniques** (refactos guidés, tests, docs). Le CLAUDE.md
   est justement ce qui rend le downgrade sûr : Sonnet suit les mêmes règles.

---

## Faire vivre le fichier (le vrai mécanisme)

La section **Lessons** est le cœur du système. Quand Claude fait une bêtise :

1. Tu la corriges.
2. Tu dis : « ajoute la règle qui l'aurait évitée à CLAUDE.md ».
3. Le fichier devient ta mémoire institutionnelle.

Élague aussi : une règle qui ne sert plus, on la supprime. Ne pré-écris pas 800 lignes —
la valeur vient du réflexe, pas du volume.

---

## Pistes quand le fichier grossit

- **`.claude/rules/`** — éclate en `code-style.md`, `htmx-patterns.md`, `sqlite.md`,
  référencés depuis `CLAUDE.md` via `@imports`. L'essentiel dans le fichier principal, le détail à côté.
- **Slash commands** pour les routines répétées : `/new-endpoint` (route + partial HTMX + test),
  `/migration` (schéma SQLite + vérif), `/techdebt` (fin de session, traque la duplication).
- **Hook PostToolUse** : Ruff (format + lint) en automatique après chaque édition — règle le
  formatage raté et évite les échecs CI.
