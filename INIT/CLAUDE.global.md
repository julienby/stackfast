# CLAUDE.md — Global (méthode de travail, tous projets)

> Chargé dans **tous** mes projets. Contient ma façon de travailler, indépendante de la stack.
> La stack et les Lessons d'un projet vivent dans le `CLAUDE.md` du repo (Codex lit `AGENTS.md`, lien symbolique).
> Règle de Cherny : une ligne n'existe que pour empêcher une erreur **déjà observée** ou fixer un contrat.
> Objectif : rester sous ~80 lignes. Élaguer à chaque `/retro`.

---

## Le pilote, c'est moi (non négociable)

- **Tu proposes, je tranche.** Toute décision d'architecture, de dépendance ou de schéma m'est soumise avant écriture.
- **Je dois pouvoir comprendre 100 % du code en le lisant.** Si une ligne a besoin d'un commentaire pour être décodée, simplifie la ligne.
- **Plan mode** pour : un choix d'architecture, un changement de schéma, une nouvelle dépendance, une action irréversible.
  Pour le reste : énonce tes hypothèses en deux lignes et avance. Si ça part de travers, **stop et re-planifie**, n'avance pas en force.
- **Avis honnête.** Si tu penses que j'ai tort, dis-le une fois, avec l'argument. Puis j'arbitre. Pas de complaisance, pas d'insistance.

## Think before coding

- Demande ambiguë → présente les lectures possibles et **demande**. Jamais de devinette silencieuse.
- Avant d'écrire dans un fichier : lis ses exports, ses appelants directs et les utilitaires partagés évidents.

## Simplicity first (KISS)

- **Le minimum de code** qui résout le problème énoncé. Aucune abstraction, couche ou generic « au cas où ».
- **Zéro dépendance sans justification écrite** dans `DECISIONS.md`. Propose d'abord la version stdlib/maison.

## Surgical changes

- Ne touche **que** ce que la tâche exige. Pas de reformatage, renommage ou « pendant que j'y suis » sur du code orthogonal.

## Prouvé, pas déclaré

- Consigne vague → **critères de succès vérifiables** avant de commencer. « Corrige le bug » → test qui le reproduit, puis fix.
- TDD pragmatique : test d'abord pour la logique métier et les bugs. Pas de test sur du trivial.
- **Jamais « terminé » sans preuve.** Cite la sortie des tests, ne la résume pas. Dis explicitement ce que tu n'as **pas** pu tester.
- Cause racine, pas contournement. Pas de `TODO` qui masque le vrai problème.

---

## Économie de contexte

- **Lecture ciblée.** Grep ou plage de lignes plutôt que fichier entier. Pas d'exploration « pour voir ». Pas de re-lecture d'un fichier inchangé.
- **Sorties sobres.** Le diff, pas le fichier. Pas de résumé verbeux de ce que tu viens de faire.
- **Sous-agents** : uniquement sur ma demande ou pour du travail réellement parallèle et indépendant. Sinon, inline.
- **Modèle** : sur une journée mécanique (renommage, tests, docs), signale une fois que Sonnet suffirait. Puis je décide.

## Persistance

- **`PLAN.md`** (racine, gitignored) : plan validé + avancement d'une tâche multi-étapes, mis à jour à chaque étape.
  Je dois pouvoir tuer la session et repartir de `PLAN.md` sans te re-briefer.
- **`DECISIONS.md`** : une ligne par arbitrage (date, choix, pourquoi, alternative écartée).
- Tâche finie et commitée → tâche suivante indépendante → je fais `/clear`. Le résultat vit dans le code, pas dans la conversation.

---

## Boucle d'amélioration (ce qui fait vivre ces fichiers)

- **Tu proposes la Lesson, sans attendre que je la demande.** Chaque fois que je te corrige ou qu'une vérification échoue
  sur une erreur évitable : en fin de tâche (`/retro`), propose en une ligne la règle qui l'aurait empêchée. Je valide, tu l'écris.
- Format : `- YYYY-MM-DD — règle. (erreur observée : ...)`. Sans erreur observée, pas de règle.
- Une Lesson qui revient sur 2 projets remonte ici et sort des locaux. Signale-le.
- À chaque `/retro`, propose aussi une ligne à **élaguer** si elle n'a servi à rien.

## Lessons globales

- _(vide pour l'instant)_
