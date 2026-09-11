# CLAUDE.md

> Ce fichier est un **journal de contraintes**, pas une doc exhaustive.
> Règle de Cherny : on n'ajoute une ligne que pour empêcher une erreur **déjà observée**.
> Si une règle ne répond pas à « quelle erreur précise ça évite ? », elle ne devrait pas être là.
> Objectif : rester sous ~100 lignes. Élaguer régulièrement.

---

## Le pilote, c'est moi (non négociable)

- **Pas de dette de conception.** Toute décision d'architecture m'est soumise avant écriture. Tu proposes, je tranche.
- **Pas de dette technique.** Je dois pouvoir comprendre 100 % du code en le lisant. Si je ne peux pas l'expliquer, c'est qu'il faut le réécrire plus simplement.
- Avant toute tâche non triviale (3+ étapes ou choix d'archi) : **passe en plan mode**, présente le plan, attends mon accord. Si ça part de travers, **stop et re-planifie** — n'avance pas en force.

## Think before coding (Karpathy #1)

- **Énonce tes hypothèses** explicitement avant de coder.
- Si la demande est ambiguë : présente les interprétations possibles et **demande**. Ne devine jamais en silence.
- Surface les incohérences, les tradeoffs et ta confusion plutôt que de produire une complétion confiante mais fausse.
- Avant d'écrire dans un fichier : lis d'abord ses exports, ses appelants directs et les utilitaires partagés évidents.

## Simplicity first (Karpathy #2 + Cherny + KISS)

- Écris **le minimum de code** qui résout le problème énoncé. Rien de plus.
- **Aucune abstraction non demandée.** Pas de couche, pas de factory, pas de generic « au cas où ». Le futur hypothétique ne justifie aucune complexité aujourd'hui.
- Pas de feature spéculative. Pas de « flexibilité » que je n'ai pas réclamée.
- Le code doit être **explicable** : on comprend en lisant, sans commentaire pour décoder une astuce. Si une ligne a besoin d'être expliquée, simplifie la ligne.
- **Minimum de dépendances.** Avant d'ajouter un package : justifie-le, et propose la version stdlib/maison d'abord. Une dépendance = une dette à comprendre et maintenir.

## Surgical changes (Karpathy #3 + Cherny « Minimal Impact »)

- Ne touche **que** ce que la tâche exige. Chaque ligne modifiée doit tracer vers ma demande.
- Pas de reformatage, de renommage ou de « pendant que j'y suis » sur du code orthogonal.
- Pas d'effet de bord, pas de nouveau bug introduit par un nettoyage non demandé.

## Goal-driven & test-driven (Karpathy #4 + Cherny « Verification »)

- Transforme une consigne vague en **critères de succès vérifiables** avant de commencer.
  « corrige le bug » → écris d'abord un test qui le reproduit, puis fais-le passer.
- **TDD pragmatique** : test d'abord pour la logique métier et les bugs. On reste raisonnable — pas de test sur du trivial évident.
- **Ne marque jamais une tâche terminée sans l'avoir prouvée.** « Migration finie » / « tests passent » sont interdits si tu n'as pas vérifié qu'aucun cas n'a été silencieusement ignoré.

## No laziness (Cherny)

- Cherche la **cause racine**, pas le contournement. Pas de fix temporaire, pas de `# TODO` qui masque le vrai problème.
- Standards d'un dev senior : si la solution est moche, dis-le et propose la version propre.

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

## Économie de contexte (coût & focus)

- **Lecture ciblée.** Ne lis jamais un fichier entier si un grep/une plage de lignes suffit.
  N'explore pas le repo « pour voir » : lis uniquement ce que la tâche exige.
- **Pas de re-lecture.** Si un fichier est déjà dans le contexte et n'a pas changé, ne le relis pas.
- **Sorties sobres.** Pas de code recopié en entier dans tes réponses : montre le diff, pas le fichier.
  Pas de résumé verbeux de ce que tu viens de faire — 3 lignes max.
- **Fichier tampon `PLAN.md`** (à la racine, gitignored) : au début d'une tâche multi-étapes,
  écris-y le plan validé + l'état d'avancement. Mets-le à jour à chaque étape franchie.
  Objectif : je peux tuer la session et repartir de `PLAN.md` sans te re-briefer.
- **Signale les sessions à compacter.** Si le contexte devient lourd (longs allers-retours,
  gros fichiers lus), dis-le : « bon moment pour /compact ou /clear, l'état est dans PLAN.md ».
- **Délègue le mécanique.** Pour les tâches sans jugement (renommage massif, exploration de repo,
  lancer/relire des tests) : propose un subagent ou signale que Sonnet/Haiku suffirait.
  Opus reste réservé à l'architecture, au code métier et au debug non trivial.

---

## Lessons (journal des corrections)

> On remplit cette section au fil de l'eau. Quand tu fais une erreur, après correction
> je te demande d'y ajouter la règle qui l'aurait évitée. C'est ça qui fait vivre le fichier.

- _(vide pour l'instant)_
