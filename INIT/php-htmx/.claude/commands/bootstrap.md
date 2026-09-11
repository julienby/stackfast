Bootstrappe ce repo selon CLAUDE.md, sans écrire aucune feature.

1. Vérifie que PRD.md a été lu et que le découpage v1 est validé dans PLAN.md. Sinon, arrête-toi et demande.
2. Crée : composer.json (PSR-4 `App\` → src/), la structure de dossiers de CLAUDE.md, public/index.php avec un routeur maison,
   une page « / » qui rend un template, bin/migrate, db/migrations/001_init.sql (vide ou table minimale du PRD).
3. Installe PHPUnit en dev, rien d'autre. Un test HTTP qui vérifie que « / » renvoie du HTML contenant le titre.
4. Lance `make check`. Cite la sortie complète. Corrige jusqu'à vert.
5. Écris dans DECISIONS.md les choix faits ici. Propose le premier commit (message court), attends mon accord.
