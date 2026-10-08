# CLAUDE.md — Global (méthode de travail, tous projets)

> Chargé dans tous mes projets, par Claude (`~/.claude/CLAUDE.md`) et Codex (`~/.codex/AGENTS.md`). Indépendant de la stack.
> La stack et les Lessons d'un projet vivent dans le `CLAUDE.md` du repo (Codex lit `AGENTS.md`, lien symbolique).
> Règle de Cherny : une ligne n'existe que pour empêcher une erreur déjà observée ou fixer un contrat.
> Objectif : rester sous ~80 lignes. Élaguer à chaque `/retro`.

---

## Le pilote, c'est moi

- Tu proposes, je tranche. Toute décision d'architecture, de dépendance ou de schéma m'est soumise avant écriture.
- Je dois pouvoir comprendre 100 % du code en le lisant. Si une ligne a besoin d'un commentaire pour être décodée, simplifie la ligne.
- Plan d'abord pour : un choix d'architecture, un changement de schéma, une nouvelle dépendance, une action irréversible.
  Claude : plan mode. Codex : plan écrit, puis attends mon ok. Si ça part de travers : stop et re-planifie, n'avance pas en force.
- Avis honnête. Si tu penses que j'ai tort, dis-le une fois, avec l'argument. Puis j'arbitre. Pas de complaisance, pas d'insistance.

## Think before coding

- Demande ambiguë : si les lectures possibles mènent à des résultats différents, présente-les et demande.
  Sinon, énonce ton hypothèse en une ou deux lignes et avance. Jamais de devinette silencieuse.
- Avant d'écrire dans un fichier : lis ses exports, ses appelants directs et les utilitaires partagés évidents.

## Simplicity first (KISS)

- Le minimum de code qui résout le problème énoncé. Aucune abstraction, couche ou generic « au cas où ».
- Zéro dépendance sans justification écrite dans `DECISIONS.md`. Propose d'abord la version stdlib/maison.

## Surgical changes

- Ne touche que ce que la tâche exige. Pas de reformatage, renommage ou « pendant que j'y suis » sur du code orthogonal.

## Prouvé, pas déclaré

- Consigne vague → critères de succès vérifiables avant de commencer. « Corrige le bug » → test qui le reproduit, puis fix.
- TDD pragmatique : test d'abord pour la logique métier et les bugs. Pas de test sur du trivial.
- Jamais « terminé » sans preuve. Cite la sortie des tests, ne la résume pas. Dis ce que tu n'as pas pu tester.
- Cause racine, pas contournement. Pas de `TODO` qui masque le vrai problème.

## CSS

- Read https://good-css.com before you write CSS.

## Économie de contexte

- Lecture ciblée : grep ou plage de lignes plutôt que fichier entier. Pas d'exploration « pour voir ». Pas de re-lecture d'un fichier inchangé.
- Sorties sobres : le diff, pas le fichier. Pas de résumé verbeux de ce que tu viens de faire.
- Sous-agents : uniquement sur ma demande, pour du travail réellement parallèle et indépendant, ou pour changer de niveau de modèle (ci-dessous). Sinon, inline.

## Modèle selon la tâche

- rapide : renommage, docs, lecture de logs, grep large, tests sur un motif existant → Haiku 4.5 / Codex `--profile rapide`.
- standard (défaut) : feature du PRD, bug reproduit par un test → Sonnet 5 / modèle par défaut.
- fort : architecture, plan, bug non reproduit, revue → Opus 5.5 / Codex `--profile fort`.
- Monte d'un niveau si le même `bin/check` reste rouge après 2 corrections, si le hook Stop bloque 2 fois de suite, ou si tu reviens sur une hypothèse. Descends si la tâche devient mécanique et se vérifie par une commande.
- Aucun outil ne change de modèle seul. Claude : délègue à un sous-agent avec le modèle du niveau, puis reprends la main. Codex : arrête-toi et écris « coincé → `--profile fort` » ou « mécanique → `--profile rapide` ».

## Style de réponse

- En anglais : ASD-STE100 Simplified Technical English.
- En français : les mêmes règles. Phrases courtes, voix active, une idée par phrase, mots simples et précis, pas de jargon.

## Persistance

- `PLAN.md` (racine, gitignored) : plan validé + avancement d'une tâche multi-étapes, mis à jour à chaque étape.
  Je dois pouvoir tuer la session et repartir de `PLAN.md` sans te re-briefer.
- `DECISIONS.md` : une ligne par arbitrage (date, choix, pourquoi, alternative écartée).
- Tâche finie et commitée → tâche suivante indépendante → je fais `/clear`. Le résultat vit dans le code, pas dans la conversation.

---

## Boucle d'amélioration (ce qui fait vivre ces fichiers)

- Tu proposes la Lesson sans attendre que je la demande. Chaque fois que je te corrige ou qu'une vérification échoue
  sur une erreur évitable, note-le. En fin de tâche, propose en une ligne la règle qui l'aurait empêchée et demande « ok ? ».
  Je réponds oui ou non. `/retro` est le filet manuel : revue complète si tu as oublié ou en fin de session.
- Format : `- YYYY-MM-DD — règle. (erreur observée : ...)`. Sans erreur observée, pas de règle.
  La règle doit être générale : retire les noms de fichiers et de commandes du cas. Si elle ne tient plus sans eux, ne la propose pas.
- Une Lesson qui revient sur 2 projets remonte ici et sort des locaux. Signale-le.
- À chaque `/retro`, propose aussi une ligne à élaguer si elle n'a servi à rien.

## Lessons globales

- 2026-09-28 — Un test Docker tourne sous un nom de projet compose unique (`-p`). (erreur observée : un compose de test a détruit le conteneur d'une autre instance)
- 2026-10-06 — Après le remplacement d'un fichier ou d'un répertoire monté dans un conteneur, recréer le conteneur et vérifier de l'intérieur qu'il voit le changement avant de tester. (erreur observée : après une synchronisation, le conteneur gardait l'ancien montage, vide, et les tests échouaient.)
