# Getting started

> **À mettre à jour.** Ce document décrit l'ancien modèle (image partagée par stack, `apps/<stack>/<slug>`). Les apps sont désormais autonomes : voir [ARCHITECTURE.md](../ARCHITECTURE.md) et le `AGENTS.md` de la recette.

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
| `stackfast sync` | dans l'instance | `bin/`, `.claude/`, `proxy/compose.yml` et `stacks/` de l'instance, rien d'autre (puis `stackfast deploy` pour l'appliquer) |

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

Sur le VPS, tu corriges vite un bug dans `stacks/php-htmx/lib/stack.php`. Puis tu lances `stackfast sync`.

```
Modifs locales dans stacks/php-htmx (depuis la dernière sync) :
./lib/stack.php
stackfast : reporte-les dans le hub, ou écrase-les : stackfast sync --force
```

La sync s'arrête. Rien n'est effacé. Deux choix :

- **Garder la correction** : reporte-la dans `~/.stackfast/INIT/stacks/php-htmx/lib/stack.php`, `stackfast push`, puis sur le VPS `stackfast pull` et `stackfast sync --force`.
- **Jeter la correction** : `stackfast sync --force`.

Comment ça marche : chaque sync écrit `stacks/<stack>/.stackfast-sums`, la somme sha256 de chaque fichier posé. La sync suivante compare. Un fichier modifié, ajouté ou supprimé à la main bloque. Un fichier changé par le **hub** ne bloque pas : le manifeste décrit ce que la dernière sync a posé, pas ce que le hub contient maintenant.

Autres cas :

- Un fichier supprimé dans le hub est aussi supprimé dans l'instance.
- `stacks/<stack>/vendor/` n'est jamais touché ni vérifié.
- Première sync (pas de manifeste) : elle passe toujours.
- `bin/` et `.claude/` suivent la même règle, avec un manifeste à la racine (`.stackfast-sums`). Seuls les fichiers du hub sont gérés : `.claude/settings.local.json` et tes ajouts restent.
- Instance créée avant ce manifeste : la première sync s'arrête si un fichier de `bin/` ou `.claude/` diffère du hub. Regarde, puis `stackfast sync --force`.

## Exemple 5 — Le token d'une app

```sh
stackfast new php-htmx mon-app
```

La commande écrit le token dans le `.env` **de l'app** (`apps/php-htmx/mon-app/.env`, gitignoré, lu par son conteneur seulement) :

```
token      : MON_APP_API_TOKEN (bearer /api + /mcp), dans apps/php-htmx/mon-app/.env
```

Règle du nom : slug en majuscules, `-` devient `_`, puis `_API_TOKEN`. `mon-app` → `MON_APP_API_TOKEN`. `demo` → `DEMO_API_TOKEN`.

Sans cette ligne, `/api` et `/mcp` répondent 401 à tout le monde. Après une modif du `.env` de l'app : `stackfast up mon-app`.

```sh
curl -H "Authorization: Bearer 3f9a…" https://apps.example.com/mon-app/api    # 200
curl https://apps.example.com/mon-app/api                                     # 401
```

## Exemple 6 — Une app, trois adresses successives

L'app ne connaît pas son adresse. Elle calcule ses liens avec `url('/items')`, à partir du header `X-Base-Path` que le proxy pose **toujours** (un client ne peut pas le choisir).

| Étape | Commande | Adresse | `X-Base-Path` | `url('/items')` |
|---|---|---|---|---|
| incubation | `stackfast new php-htmx mon-app` | `apps.example.com/mon-app/` | `/mon-app` | `/mon-app/items` |
| mûre | `stackfast domain mon-app mon-app.example.com` | `mon-app.example.com/` | `/` | `/items` |
| autonome | `stackfast domain mon-app mon-app.fr` | `mon-app.fr/` | `/` | `/items` |

À chaque étape, les adresses précédentes redirigent (301) vers la nouvelle. Le conteneur ne change pas, le code non plus. L'état tient dans `proxy/domains` (une ligne : `mon-app mon-app.fr mon-app.example.com`), le Caddyfile est régénéré.

Seule chose à faire hors stackfast : pointer le DNS du domaine vers le serveur. Caddy obtient le certificat tout seul.

## Exemple 7 — Le hook de fin de tour

Dans une instance, Claude Code lance `.claude/hooks/verify.sh` à la fin de chaque tour.

| Situation | Résultat |
|---|---|
| rien de changé dans `apps/` ni `stacks/` (git) | rien ne tourne |
| une app modifiée, tests verts | rien à signaler |
| une app modifiée, tests rouges | Claude est bloqué et voit la sortie de `stackfast check <slug>` |
| `stacks/` modifié | toutes les apps sont vérifiées |
| déjà bloqué une fois dans ce tour | Claude peut s'arrêter (pas de boucle infinie) |

Seules les apps touchées sont vérifiées : avec 20 apps, un tour ne relance pas 20 conteneurs. Limite : le hook regarde `git status`. Un changement déjà **commité** pendant le tour n'est pas vérifié. Lance `stackfast check` avant de commiter.

---

# 2. Tester en local

Teste toute la chaîne (installeur → app → conteneur → proxy → checks) sans VPS ni domaine, avec juste Docker.

## Prérequis

- Docker + `docker compose` (v2.17 ou plus)
- `git`, `curl`, `openssl`
- les ports 80 et 443 libres (le proxy de l'instance les prend)

## 1. Créer une instance de test

```sh
STACKFAST_HOME="$PWD" sh INIT/install.sh /tmp/mon-instance
cd /tmp/mon-instance
cp .env.example .env
```

Dans `.env` : `APPS_HOST=localhost`, et `HOST_UID`/`HOST_GID` à `id -u` / `id -g`.

`STACKFAST_HOME="$PWD"` utilise ce clone comme hub, sans en cloner un autre.

## 2. Créer une app

```sh
stackfast new php-htmx demo
```

Premier lancement : construit l'image de la stack (une minute), puis démarre le conteneur `main-demo`, puis le proxy `main-proxy`. Ensuite, une app de plus prend quelques secondes.

## 3. Vérifier que ça répond

Caddy sert `localhost` avec un certificat local : `-k` pour curl.

```sh
curl -k https://localhost/demo/                              # page complète
curl -k -H 'HX-Request: true' https://localhost/demo/         # fragment htmx seul
curl -k -o /dev/null -w '%{http_code}\n' https://localhost/demo/models/items.php          # 404 attendu
curl -k -o /dev/null -w '%{http_code}\n' -X POST https://localhost/demo/items -d 'label=x'  # 403, pas de CSRF
stackfast ls                                                  # demo : running healthy
```

## 4. Lancer les tests

```sh
stackfast check          # tout
stackfast check demo     # une app
```

Vert + exit 0 si tout passe, sinon exit 1. Chaque app est testée dans un conteneur jetable (image de la stack, sans réseau).

## 5. Sécurité

```sh
stackfast audit          # version PHP servie vs dernier correctif php.net, réglages, apps à jour
stackfast update         # reconstruit, teste toutes les apps, bascule
```

## 6. Nettoyer

```sh
cd /tmp/mon-instance
stackfast down demo
docker compose --env-file .env -f proxy/compose.yml down -v
docker image rm $(docker image ls -q 'sf-main-php-htmx')
rm -rf /tmp/mon-instance
```

## Pour de vrai (un VPS)

Même déroulé avec `APPS_HOST=apps.ton-domaine.fr` (DNS vers le VPS). Voir `docs/DEPLOY-VPS.md`.
