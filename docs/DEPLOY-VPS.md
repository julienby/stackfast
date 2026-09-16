# Déployer sur un VPS

Le proxy (Caddy ou nginx) est géré en dehors de ce repo — cette instance ne fait que rejoindre son réseau Docker par son nom.

## 1. Créer l'instance

```sh
git clone https://github.com/julienby/ma-stack-ia.git /tmp/hub
sh /tmp/hub/INIT/install.sh /var/www/mon-instance
cd /var/www/mon-instance
cp .env.example .env
```

## 2. Configurer `.env`

- `HOST_UID` / `HOST_GID` : `id -u` / `id -g` de l'utilisateur propriétaire des fichiers de l'instance.
- `PROXY_NETWORK` : le réseau Docker du proxy existant, trouvé avec :

```sh
docker inspect <conteneur-proxy> --format '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{end}}'
```

## 3. Créer l'app

```sh
bin/new-app php-htmx mon-app
```

Remplit `apps/php-htmx/mon-app/PRD.md`, et affiche le bloc de config proxy (Caddy et nginx) à coller à l'étape 5.

## 4. Déployer le conteneur de la stack

```sh
bin/deploy php-htmx
```

Le conteneur rejoint `PROXY_NETWORK` automatiquement (déclaré en réseau externe dans `docker-compose.yml`), joignable par le proxy via son nom (`php-htmx`).

## 5. Vérifier avant d'exposer

```sh
bin/check php-htmx mon-app
```

## 6. Config du proxy (en dehors de ce repo)

Modèles complets dans `INIT/instance/proxy/caddy/Caddyfile.example` et `INIT/instance/proxy/nginx/site.conf.example`. Principe commun : le proxy joint le conteneur par son nom sur `PROXY_NETWORK`, et écrase toujours `X-Base-Path` (jamais de confiance au client — c'est ce header qui pilote la génération d'URL côté app).

**Caddy** — sous-répertoires, une ligne pour toutes les apps :
```caddyfile
web.example.com {
    reverse_proxy php-htmx:80 {
        header_up -X-Base-Path
    }
}
```
**Caddy** — domaine dédié à une app (`bin/new-app` imprime ce bloc avec le bon slug) :
```caddyfile
mon-app.example.com {
    rewrite * /mon-app{uri}
    reverse_proxy php-htmx:80 {
        header_up X-Base-Path /
    }
}
```

**nginx** — sous-répertoires :
```nginx
server {
    server_name web.example.com;
    location / {
        proxy_pass http://php-htmx:80;
        proxy_set_header Host $host;
        proxy_set_header X-Base-Path "";
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```
**nginx** — domaine dédié :
```nginx
server {
    server_name mon-app.example.com;
    location / {
        proxy_pass http://php-htmx:80/mon-app/;
        proxy_set_header Host $host;
        proxy_set_header X-Base-Path /;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Recharger sans downtime :
```sh
docker exec <conteneur-proxy> caddy reload --config /chemin/vers/Caddyfile   # Caddy
docker exec <conteneur-proxy> nginx -s reload                               # nginx
```

## 7. Vérifier en prod

```sh
curl https://ton-domaine.tld/mon-app/
```

## 8. Déploiements suivants (mise à jour de code)

```sh
cd /var/www/mon-instance && git pull && bin/deploy php-htmx && bin/check php-htmx
```

## Point non vérifié en conditions réelles

Le header `X-Base-Path` posé par le proxy n'a été validé qu'en local (`curl -H`). Son comportement en prod, avec un vrai Caddyfile/site nginx, reste à confirmer au premier déploiement réel.
