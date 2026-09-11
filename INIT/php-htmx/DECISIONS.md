# DECISIONS — journal des décisions d'architecture

> Une ligne par décision. Date, choix, pourquoi, alternative écartée. On n'efface pas, on ajoute une ligne « remplacée par ».

- YYYY-MM-DD — Aucun framework. Pourquoi : lisibilité 100 %, zéro dépendance structurante ; l'hypermédia est la structure. Écarté : Laravel, Symfony.
- YYYY-MM-DD — Domain = cas d'usage appelables sans HTTP. Pourquoi : un serveur MCP viendra se brancher dessus sans réécrire le métier.
- YYYY-MM-DD — Tailwind CDN en v1, CLI plus tard. Qualité = php -l + PHPUnit, PHPStan plus tard si besoin.
- YYYY-MM-DD — SQLite + PDO. Pourquoi : un fichier, zéro service, suffisant pour le volume v1. Écarté : Postgres (à revoir si >1 écrivain concurrent lourd).
