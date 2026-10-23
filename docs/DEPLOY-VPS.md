# Déployer sur un VPS

Une instance = un repo sur le VPS. Elle porte son propre Caddy (`proxy/`, ports 80/443) et un conteneur par app.
Prérequis : Docker + `docker compose` v2.17 ou plus, `git`, `curl`, `openssl`, ports 80/443 libres.

## 1. Créer l'instance

```sh
curl -fsSL https://raw.githubusercontent.com/julienby/stackfast/main/INIT/install.sh | sh -s -- /var/www/mon-instance
cd /var/www/mon-instance
cp .env.example .env
```

## 2. Configurer `.env`

- `APPS_HOST` : l'hôte des apps en incubation, par ex. `apps.domaine.fr` (enregistrement DNS A/AAAA vers le VPS).
- `HOST_UID` / `HOST_GID` : `id -u` / `id -g` du propriétaire des fichiers. Les conteneurs tournent avec cet uid.
- `STACKFAST_INSTANCE` : à changer seulement si deux instances partagent la machine.

## 3. Première app

```sh
stackfast new php-htmx mon-app
```

Construit l'image de la stack (une fois), démarre le conteneur, démarre le proxy et l'affiche :
`en ligne : mon-app → https://apps.domaine.fr/mon-app/`. Caddy obtient le certificat au premier accès.

## 4. Cycle de vie d'une adresse

```sh
stackfast domain mon-app mon-app.domaine.fr   # l'app est mûre
stackfast domain mon-app mon-app.fr           # elle a son domaine
stackfast domain mon-app                      # où en est-elle ?
```

Le nouveau domaine devient l'adresse ; `apps.domaine.fr/mon-app/` et les anciens domaines redirigent (301).
Pointe d'abord le DNS du domaine vers le VPS. Rien d'autre : pas de bloc à écrire, pas de code à changer.

Un site hors stackfast (statique, autre service) : un bloc à la main dans `proxy/Caddyfile`, puis `stackfast proxy`.

## 5. Déploiements suivants

```sh
cd /var/www/mon-instance && stackfast deploy
```

`git pull`, puis chaque app redémarrée si son `compose.yml` a changé, image de stack reconstruite si la stack a changé, proxy régénéré. Le code des apps est monté : un pull suffit pour lui.

Après `stackfast pull && stackfast sync` (nouvelle version du hub) : `stackfast deploy`.

## 6. Sécurité

```sh
stackfast audit     # à lancer après chaque annonce de sécurité PHP ; en cron pour être prévenu
stackfast update    # applique : reconstruit, teste, bascule
```

Procédure complète : [SECURITE.md](SECURITE.md).

## 7. Migrer une instance de l'ancien modèle (un conteneur Apache partagé)

1. `stackfast pull && stackfast sync` dans l'instance. `proxy/Caddyfile`, `proxy/domains` et `proxy/compose.yml` arrivent.
2. Dans `.env` : ajouter `APPS_HOST` et `STACKFAST_INSTANCE` (voir `.env.example`). `PROXY_NETWORK` et `PHP_HTMX_CONTAINER` ne servent plus.
3. Ton Caddy existant tient les ports 80/443 : recopie ses blocs hors apps dans `proxy/Caddyfile`, puis arrête-le.
4. Pour chaque app : `stackfast migrate php-htmx <slug>` (crée son `compose.yml`, copie ses variables `<SLUG>_*` dans son `.env`, la démarre).
   Une app qui avait un domaine dédié : `stackfast domain <slug> <domaine>`.
   Une app qui avait un `services/*/docker-compose.yml` : ce service passe dans son `compose.yml` (modèle `worker` commenté).
5. `docker rm -f php-htmx` (l'ancien conteneur partagé), puis retire les `<SLUG>_*` du `.env` de l'instance.
6. `stackfast audit` doit dire « rien à faire ».

## Points non vérifiés en conditions réelles

Écrit et relu, pas encore lancé sur une vraie machine : la chaîne complète (FrankenPHP non root en lecture seule, `docker compose extends`, rechargement du proxy, réseaux par app) doit être validée au premier déploiement, de préférence avec `docs/GETTING-STARTED.md` partie 2 en local.
