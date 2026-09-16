# Déployer sur un VPS avec Caddy déjà en conteneur

Cas où un reverse-proxy Caddy tourne déjà sur le VPS (autre conteneur, autre repo) et où on veut lui ajouter une instance ma-stack-ia sans y toucher.

## 1. Créer l'instance

```sh
git clone https://github.com/julienby/ma-stack-ia.git /tmp/hub
sh /tmp/hub/INIT/install.sh /var/www/mon-instance
cd /var/www/mon-instance
cp .env.example .env
```

## 2. Configurer `.env`

- `HOST_UID` / `HOST_GID` : `id -u` / `id -g` de l'utilisateur propriétaire des fichiers de l'instance.
- `PROXY_NETWORK` : le réseau Docker du conteneur Caddy existant, trouvé avec :

```sh
docker inspect <conteneur-caddy> --format '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{end}}'
```

## 3. Créer l'app

```sh
bin/new-app php-htmx mon-app
```

Remplit `apps/php-htmx/mon-app/PRD.md` avant d'aller plus loin, et affiche le bloc de config proxy à coller à l'étape 6.

## 4. Déployer le conteneur de la stack

```sh
bin/deploy php-htmx
```

Le conteneur rejoint `PROXY_NETWORK` automatiquement (déclaré en réseau externe dans `docker-compose.yml`).

## 5. Vérifier avant d'exposer

```sh
bin/check php-htmx mon-app
```

## 6. Éditer le Caddyfile existant

Coller le bloc affiché à l'étape 3 (modèle dans `INIT/instance/proxy/caddy/Caddyfile.example`) dans le Caddyfile déjà utilisé par le conteneur Caddy — pas de nouveau service, pas de nouveau conteneur proxy.

## 7. Recharger sans downtime

```sh
docker exec <conteneur-caddy> caddy reload --config /chemin/vers/Caddyfile
```

## 8. Vérifier en prod

```sh
curl https://ton-domaine.tld/mon-app/
```

## 9. Déploiements suivants (mise à jour de code)

```sh
cd /var/www/mon-instance && git pull && bin/deploy php-htmx && bin/check php-htmx
```

## Point non vérifié en conditions réelles

Le header `X-Base-Path` posé par Caddy n'a été validé qu'en local (`curl -H`). Son comportement en prod, avec le vrai Caddyfile du VPS, reste à confirmer au premier déploiement réel.
