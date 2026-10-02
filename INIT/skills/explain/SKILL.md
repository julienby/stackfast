---
name: explain
description: Explique un sujet (diff, module, feature, concept) dans une page HTML autonome avec diagramme SVG, pour le comprendre avant de valider. Utiliser quand l'utilisateur tape /explain ou demande une explication visuelle.
---

# /explain <sujet>

Objectif : que l'utilisateur comprenne le sujet à 100 %. La page est jetable.

## Étapes

1. Lis le code ou le diff ciblé. Lecture ciblée : grep ou plage de lignes. N'invente rien.
2. Écris une page HTML autonome : un seul fichier, CSS et SVG en ligne, aucune dépendance externe.
3. Écris le fichier dans `~/.cache/explain/<projet>/<sujet>.html`.
   - `<projet>` = nom du dossier racine git courant (sinon du dossier courant).
   - `<sujet>` = slug court en minuscules, sans accent. Crée les dossiers si besoin.
   - N'écris jamais dans le repo.
4. Réponds avec le chemin du fichier et une phrase. Pas de résumé de la page.

## Contenu de la page

- Un diagramme SVG du mécanisme réel : flux, étapes ou relations entre les parties.
- Un texte court en ASD-STE100 (en français : phrases courtes, voix active, une idée par phrase).
- Les références au code : `fichier:ligne`.
- Une section « Ce que je n'ai pas pu vérifier », si besoin.
- Lisible en thème clair et sombre (`prefers-color-scheme`).
