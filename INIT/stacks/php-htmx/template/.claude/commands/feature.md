Implémente la feature : $ARGUMENTS

1. Relis la ligne correspondante dans `PRD.md` (périmètre v1). Si elle n'y est pas, arrête-toi : c'est hors PRD.
2. Écris le plan dans `PLAN.md` : route(s), action(s), modèle, vue(s), migration éventuelle, tests à écrire. Hypothèses explicites. Attends validation.
3. Délègue chaque tâche bien délimitée au sous-agent **commis** (une tâche = une route ou un cas d'usage) ; le mécanique au **sous-traitant**. Garde pour toi ce qui demande un choix.
4. Tests d'abord : le modèle sans HTTP, puis le HTML rendu (fragment htmx, liens et formulaires attendus).
5. `bin/check` vert, sortie citée. Fais relire le diff par le sous-agent **relecteur** ; corrige ce qui est bloquant.
6. Diff montré, ce qui n'a pas pu être testé dit. Lance /retro.
