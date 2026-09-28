# Getting started

Deux parties :

1. **Comprendre** : les subtilités, en exemples courts.
2. **Tester en local** : toute la chaîne avec Docker, sans VPS.

---

# 1. Comprendre en exemples

## Exemple 1 — Les quatre niveaux

Tu as une machine avec le hub, et un VPS avec une instance `mon-vps` qui porte l'app `mon-app`.

| Niveau | Où, sur le disque | Qui l'écrit |
|---|---|---|
| Méthode | `~/.claude/CLAUDE.md` → lien vers `~/.stackfast/INIT/CLAUDE.global.md` | toi, sur ta machine |
| Instance | `mon-vps/AGENTS.md`, `mon-vps/bin/`, `mon-vps/.claude/` | toi, dans l'instance |
| Stack | `mon-vps/stacks/php-htmx/` | le hub seulement |
| App | `mon-vps/apps/php-htmx/mon-app/` | toi, dans l'instance |

Le hub, c'est ce repo. Le client le clone une fois dans `~/.stackfast`.

## Exemple 2 — Qui touche quoi

| Commande | Où la lancer | Ce qu'elle modifie |
|---|---|---|
| `stackfast pull` | n'importe où | le clone `~/.stackfast` (`git pull`), puis les liens global + skills |
| `stackfast push "msg"` | n'importe où | commit de **tout** le clone `~/.stackfast`, `pull --rebase`, puis push |
| `stackfast init mon-vps` | n'importe où | crée les fichiers absents de l'instance, puis `sync` |
| `stackfast sync` | dans l'instance | `stacks/` de l'instance, rien d'autre |

Piège : `pull` ne met **pas** à jour l'instance. Après un `pull`, lance `stackfast sync` dans chaque instance.

```sh
stackfast pull          # le hub est à jour
cd /var/www/mon-vps
stackfast sync          # les stacks de l'instance sont à jour
```

Piège : `init` ne remplace jamais un fichier existant. Si le hub change `INIT/instance/AGENTS.md`, une instance déjà créée garde son ancien `AGENTS.md`. Copie la modif à la main si tu la veux.

## Exemple 3 — Où écrire une règle

Tu veux la règle « les dates s'affichent au format JJ/MM/AAAA ».

| Elle vaut pour… | Écris-la dans | Puis |
|---|---|---|
| `mon-app` seulement | `apps/php-htmx/mon-app/AGENTS.md` | commit dans l'instance |
| toutes les apps de `mon-vps` | `mon-vps/AGENTS.md` | commit dans l'instance |
| toutes les apps php-htmx | `~/.stackfast/INIT/stacks/php-htmx/AGENTS.md` | `stackfast push`, puis sur chaque VPS `stackfast pull` et `stackfast sync` |
| tous tes projets | `~/.claude/CLAUDE.md` | `stackfast push`, puis `stackfast pull` ailleurs |

`~/.claude/CLAUDE.md` est un lien. L'éditer, c'est éditer le hub. `stackfast status` te montre ce qui n'est pas encore poussé.

## Exemple 4 — Une modif locale dans `stacks/`

Sur le VPS, tu corriges vite un bug dans `stacks/php-htmx/lib/bootstrap.php`. Puis tu lances `stackfast sync`.

```
Modifs locales dans stacks/php-htmx (depuis la dernière sync) :
./lib/bootstrap.php
stackfast : reporte-les dans le hub, ou écrase-les : stackfast sync --force
```

La sync s'arrête. Rien n'est effacé. Deux choix :

- **Garder la correction** : reporte-la dans `~/.stackfast/INIT/stacks/php-htmx/lib/bootstrap.php`, `stackfast push`, puis sur le VPS `stackfast pull` et `stackfast sync --force`.
- **Jeter la correction** : `stackfast sync --force`.

Comment ça marche : chaque sync écrit `stacks/<stack>/.stackfast-sums`, la somme sha256 de chaque fichier posé. La sync suivante compare. Un fichier modifié, ajouté ou supprimé à la main bloque. Un fichier changé par le **hub** ne bloque pas : le manifeste décrit ce que la dernière sync a posé, pas ce que le hub contient maintenant.

Autres cas :

- Un fichier supprimé dans le hub est aussi supprimé dans l'instance.
- `stacks/<stack>/vendor/` n'est jamais touché ni vérifié.
- Première sync (pas de manifeste) : elle passe toujours.

## Exemple 5 — Le token d'une app

```sh
bin/new-app php-htmx mon-app
```

La commande ajoute le token à la fin de `.env` et l'affiche :

```
Token /api + /mcp (bearer), ajouté dans .env :
MON_APP_API_TOKEN=3f9a…
```

Si `.env` n'existe pas encore, elle affiche « à ajouter dans .env (absent) » : copie la ligne toi-même.

Règle du nom : slug en majuscules, `-` devient `_`, puis `_API_TOKEN`. `mon-app` → `MON_APP_API_TOKEN`. `demo` → `DEMO_API_TOKEN`.

Sans cette ligne dans `.env`, `/api` et `/mcp` répondent 401 à tout le monde. Après un ajout dans `.env`, relance `bin/deploy php-htmx` : le conteneur lit `.env` au démarrage.

```sh
curl -H "Authorization: Bearer 3f9a…" https://web.example.com/mon-app/api    # 200
curl https://web.example.com/mon-app/api                                     # 401
```

## Exemple 6 — `X-Base-Path` : une app, deux adresses

L'app ne connaît pas son adresse. Elle calcule ses liens avec `url('/items')`.

| Accès | Le proxy envoie | `url('/items')` donne |
|---|---|---|
| `web.example.com/mon-app/` (sous-répertoire) | rien (le proxy efface le header) | `/mon-app/items` |
| `mon-app.example.com/` (domaine dédié) | `X-Base-Path: /` | `/items` |

Sans header, l'app déduit le préfixe du chemin du script (`/mon-app`). Le proxy efface ou écrase **toujours** ce header : un client ne doit pas pouvoir le choisir.

Test en local, sans proxy :

```sh
curl http://$IP/mon-app/                          # liens en /mon-app/...
curl -H 'X-Base-Path: /' http://$IP/mon-app/      # liens en /...
```

## Exemple 7 — Le hook de fin de tour

Dans une instance, Claude Code lance `.claude/hooks/verify.sh` à la fin de chaque tour.

| Situation | Résultat |
|---|---|
| rien de changé dans `apps/` ni `stacks/` (git) | rien ne tourne |
| une app modifiée, tests verts | rien à signaler |
| une app modifiée, tests rouges | Claude est bloqué et voit la sortie de `bin/check` |
| déjà bloqué une fois dans ce tour | Claude peut s'arrêter (pas de boucle infinie) |

Limite : le hook regarde `git status`. Un changement déjà **commité** pendant le tour n'est pas vérifié. Lance `bin/check` avant de commiter.

---

# 2. Tester en local

Teste toute la chaîne (installeur → app → conteneur → checks) sans VPS ni domaine, avec juste Docker.

## Prérequis

- Docker + `docker compose`
- `git`

## 1. Créer une instance de test

```sh
STACKFAST_HOME="$PWD" sh INIT/install.sh /tmp/mon-instance
cd /tmp/mon-instance
cp .env.example .env
```

Édite `.env` si besoin (`HOST_UID`/`HOST_GID` doivent correspondre à ton utilisateur : `id -u`, `id -g`).

`STACKFAST_HOME="$PWD"` utilise ce clone comme hub, sans en cloner un autre.

Dans l'instance, `stackfast sync` ne touche jamais `apps/`. Il remplace `stacks/`, sauf si tu l'as modifié (voir exemple 4).

## 2. Créer une app

```sh
bin/new-app php-htmx demo
```

Crée `apps/php-htmx/demo` depuis le gabarit et affiche le bloc à coller dans le proxy (Caddy ou nginx) une fois en prod. En local on n'en a pas besoin.

## 3. Réseau proxy (une fois)

Les conteneurs de la stack rejoignent un réseau Docker externe nommé par `PROXY_NETWORK` dans `.env` (défaut `caddy-net`) :

```sh
docker network create caddy-net
```

## 4. Déployer

```sh
bin/deploy php-htmx
```

Build l'image et démarre le conteneur `web` (nom donné par `PHP_HTMX_CONTAINER` dans `.env`).

## 5. Vérifier que ça répond

Le conteneur n'a pas de port publié (il n'est joignable que via le réseau proxy) — on tape son IP directement. Juste après `bin/deploy`, Apache démarre : un premier `curl` peut répondre `000`. Attends deux secondes et relance.

```sh
IP=$(docker inspect $(grep PHP_HTMX_CONTAINER .env | cut -d= -f2) \
  --format '{{(index .NetworkSettings.Networks "caddy-net").IPAddress}}')

curl http://$IP/demo/                              # page complète
curl -H 'HX-Request: true' http://$IP/demo/         # fragment htmx seul
curl -o /dev/null -w '%{http_code}\n' http://$IP/demo/lib/items.php       # 403 attendu
curl -o /dev/null -w '%{http_code}\n' -X POST http://$IP/demo/items -d 'label=x'  # 403, pas de CSRF
```

## 6. Lancer les tests

```sh
bin/check                  # tout
bin/check php-htmx         # une stack
bin/check php-htmx demo    # une app
```

`CHECK OK` + exit 0 si tout passe, `CHECK FAILED` + exit 1 sinon. C'est ce que le hook `.claude/hooks/verify.sh` relance automatiquement en fin de tour dans Claude Code (exit 2 si rouge, pour que Claude voie l'échec).

## 7. Nettoyer

```sh
cd /tmp/mon-instance
docker compose --env-file .env -f stacks/php-htmx/docker-compose.yml down
docker network rm caddy-net   # seulement si créé pour ce test
rm -rf /tmp/mon-instance
```

## Pour de vrai (un VPS)

Même déroulé, avec un vrai domaine devant : installer Caddy ou nginx sur le VPS, coller le bloc affiché par `bin/new-app`, `PROXY_NETWORK` pointant sur le réseau Docker de ce proxy, et `bin/deploy` lancé depuis un `git pull` (ou en CI). Voir `INIT/instance/proxy/` pour les deux exemples de config, et `docs/PRD.md` pour le pourquoi.
