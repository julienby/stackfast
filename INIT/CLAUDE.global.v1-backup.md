# CLAUDE.md — Global (méthode de travail, tous projets)

> Ce fichier vit dans `~/.claude/CLAUDE.md` et est chargé dans **tous** mes projets.
> Il contient ma façon de travailler, indépendante de la stack.
> Les règles spécifiques à un projet (stack, Lessons) vivent dans le `CLAUDE.md` du repo.
>
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

## Économie de contexte (coût & focus)

- **Concision, mais jamais au détriment du résultat.** Va droit au but : pas de préambule,
  pas de résumé de clôture inutile. Objectif de brièveté, mais **c'est à toi de juger** :
  une explication d'archi ou un debug non trivial mérite la place qu'il faut. Ne te bride pas.
- **Lecture ciblée.** Ne lis jamais un fichier entier si un grep/une plage de lignes suffit.
  N'explore pas le repo « pour voir » : lis uniquement ce que la tâche exige.
- **Pas de re-lecture.** Si un fichier est déjà dans le contexte et n'a pas changé, ne le relis pas.
- **Sorties sobres.** Montre le diff, pas le fichier recopié en entier.

### Sous-agents

- **Utilise-les largement** pour garder le contexte principal propre : recherche, exploration,
  revue de code, tests, analyses parallèles. Un sous-agent = une tâche focalisée.
- Chaque sous-agent a **sa propre fenêtre de contexte** et partage le cache du parent :
  paralléliser du travail **indépendant** (agents qui n'entrent pas en conflit) coûte à peine
  plus qu'une seule tâche. Profites-en pour tout ce qui est parallélisable sans risque.
- Réserve le travail principal à l'architecture, au code métier et au debug non trivial ;
  offload le reste.

### Persistance hors-contexte (survivre à la compaction)

- **`PLAN.md`** (racine, gitignored) = état qui doit survivre à un `/clear` ou à une nouvelle
  session. Au début d'une tâche multi-étapes : plan validé + avancement, mis à jour à chaque étape.
  Je peux tuer la session et repartir de `PLAN.md` sans te re-briefer.
- **TodoWrite** = suivi **intra-session** d'une tâche qui touche 3-4 fichiers ou risque une
  compaction. Les todos survivent à la compaction, **les messages non** — d'où leur intérêt.
- Règle simple : ce qui doit survivre à un `/clear` → `PLAN.md` ; le suivi courant → TodoWrite.

### Compaction

- **Ne subis pas l'auto-compaction** (déclenchée tard, ~75-98 % de la fenêtre → résumé pauvre).
  Quand le contexte atteint **~60 %**, signale-le et propose `/compact` avec une instruction de focus.
  Le chiffre est indicatif : compacter tôt = résumé de meilleure qualité.
- **Focus de compaction préféré** : préserver les décisions d'architecture, l'état de la tâche
  en cours, et les contraintes non évidentes découvertes pendant la session.
- Alternative selon le cas : « bon moment pour `/clear`, l'état est dans `PLAN.md` ».

### Délégation de modèle

- Pour les tâches sans jugement (renommage massif, exploration, lancer/relire des tests) :
  signale qu'un modèle moins cher (Sonnet) suffirait. Réserve le haut de gamme au non-trivial.

---

## Autonomie sur les bugs (Cherny « No laziness »)

- Face à un bug report : **corrige-le**, ne demande pas qu'on te tienne la main.
- Pars des logs, erreurs, tests qui échouent → remonte à la **cause racine** → résous.
  Zéro changement de contexte requis de ma part. Va réparer la CI sans qu'on te dise comment.
- Mais respecte le plan mode pour les corrections non triviales : proposer avant de charger.

---

## Promotion des Lessons

- Les Lessons naissent **locales** (dans le `CLAUDE.md` du projet).
- Si la **même** leçon revient sur 2-3 projets → elle est universelle : remonte-la ici, retire-la des locaux.
- Ce fichier change rarement (la méthode est stable). Les Lessons locales changent souvent.
