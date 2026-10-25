Implémente la feature : $ARGUMENTS

Contexte attendu : tu sais dans quelle app tu travailles (`apps/<stack>/<slug>`). Sinon, demande.

1. Relis la ligne correspondante dans le `PRD.md` de l'app (périmètre v1). Si elle n'y est pas, arrête-toi : c'est hors PRD.
2. Écris le plan dans `PLAN.md` : fichiers touchés, tests à écrire. Hypothèses explicites. Attends validation.
3. Tests d'abord : cas d'usage du modèle (`models/`) **sans HTTP**, puis HTML rendu (fragment htmx, liens et formulaires attendus).
4. Implémente le minimum qui fait passer les tests, en suivant l'organisation MVC de la stack : la route dans `routes.php`, l'action dans `controllers/`, le métier dans `models/`, le HTML dans `views/`. Pas de nouvelle abstraction.
5. `stackfast check <slug>` vert, sortie citée. Diff montré. Ce qui n'a pas pu être testé, dit.
6. Lance /retro.
