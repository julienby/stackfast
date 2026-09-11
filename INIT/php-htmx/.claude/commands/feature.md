Implémente la feature : $ARGUMENTS

1. Relis la ligne correspondante dans PRD.md (périmètre v1). Si elle n'y est pas, arrête-toi : c'est hors PRD.
2. Écris le plan dans PLAN.md : fichiers touchés, tests à écrire, migration éventuelle. Hypothèses explicites. Attends validation.
3. Tests d'abord : test du cas d'usage Domain **sans HTTP**, puis test HTTP sur le HTML rendu (fragment htmx + liens/formulaires attendus).
4. Implémente le minimum qui fait passer les tests. Handlers fins, Domain sans HTTP/SQL.
5. `make check` vert, sortie citée. Diff montré. Ce qui n'a pas pu être testé, dit.
6. Lance /retro.
