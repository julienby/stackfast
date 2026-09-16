# Getting started — tester en local

Teste toute la chaîne (installeur → app → conteneur → checks) sans VPS ni domaine, avec juste Docker.

## Prérequis

- Docker + `docker compose`
- `git`

## 1. Créer une instance de test

```sh
sh INIT/install.sh /tmp/mon-instance
cd /tmp/mon-instance
cp .env.example .env
```

Édite `.env` si besoin (`HOST_UID`/`HOST_GID` doivent correspondre à ton utilisateur : `id -u`, `id -g`).

Relancer `sh INIT/install.sh /tmp/mon-instance` est sans risque : `stacks/` est resynchronisé depuis le hub, `apps/` n'est jamais écrasé.

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

Le conteneur n'a pas de port publié (il n'est joignable que via le réseau proxy) — on tape son IP directement :

```sh
IP=$(docker inspect $(grep PHP_HTMX_CONTAINER .env | cut -d= -f2) \
  --format '{{(index .NetworkSettings.Networks "caddy-net").IPAddress}}')

curl http://$IP/demo/                              # page complète
curl -H 'HX-Request: true' http://$IP/demo/         # fragment htmx seul
curl -o /dev/null -w '%{http_code}\n' http://$IP/demo/lib/bootstrap.php   # 403 attendu
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
