# Décisions du hub

Une ligne par arbitrage : date, choix, pourquoi, alternative écartée.

- 2026-08-20 — kit php-htmx : un repo par app, libs copiées, pas extraites. **Remplacée le 2026-09-16.**
- 2026-09-16 — Un monorepo par VPS (instance), multi-stack : `apps/<stack>/<slug>`. Une seule chose à déployer, un seul contrat à lire. Écarté : un repo par stack ou par app (autant de repos que de SaaS).
- 2026-09-16 — Composer et helpers partagés par stack (`stacks/<stack>/lib`, namespace `Stack\`). Le namespace lève le risque de collision qui justifiait la copie. Écarté : copier `lib/` dans chaque app.
- 2026-09-16 — JSON à plat par défaut (`data/*.json`, écriture atomique), SQLite quand l'app est validée. On voit les données, on itère. Écarté : SQLite + migrations dès le départ.
- 2026-09-16 — Préfixe d'URL fourni par le proxy via l'en-tête `X-Base-Path` (Caddy ou nginx), sinon déduit de `SCRIPT_NAME`. Bascule sous-répertoire → domaine sans toucher au code. Écarté : variable d'env par app (impossible avec un conteneur partagé).
- 2026-09-16 — Tests PHP CLI sans framework (`tests/*_test.php`, ok/FAIL, exit 1), une commande `bin/check` pour toutes les stacks. Écarté : PHPUnit (dépendance, config, pas lisible par tout le monde).
- 2026-09-16 — Secrets dans `.env` gitignoré (une variable par app, préfixe `<SLUG>_`), passés par `env_file`. Écarté : valeurs par défaut dans compose.
- 2026-09-16 — Apache tourne avec l'uid de l'hôte (`HOST_UID/HOST_GID`) : `data/` bind-monté reste lisible et éditable des deux côtés. Écarté : `chown www-data` / `chmod 777`.
- 2026-09-16 — Chaque app expose `/api` et `/mcp` par défaut, scaffoldées même vides (200, liste vide). Agentic first : jamais à rajouter après coup. Écarté : les créer seulement quand l'app en a besoin.
- 2026-09-16 — `/api` et `/mcp` répondent même vides plutôt que 404 : un client (humain ou agent) découvre toujours la surface, même sans endpoint déclaré. Écarté : 404 tant qu'aucun endpoint n'est enregistré.
- 2026-09-16 — Un seul bearer token par app (`<SLUG>_API_TOKEN`, `.env`) protège `/api` et `/mcp` ensemble. Une app = un consommateur (l'app elle-même ou son agent), pas de raison de séparer. Écarté : un secret partagé au niveau instance ; deux tokens distincts par surface.
