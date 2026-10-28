# Image de l'app __SLUG__ : FrankenPHP (PHP 8.5 + serveur HTTP dans un seul processus), ses dépendances, son CSS.
# Autonome : construite par `docker compose build` depuis ce dossier, sans aucun fichier extérieur.
# En développement, compose.yml monte le dossier de l'app sur /app (code lu à chaud) ; bin/build prépare vendor/ et le CSS.

ARG PHP_IMAGE=dunglas/frankenphp:1-php8.5-trixie

# 1. Dépendances composer (seulement celles de cette app).
FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction --no-scripts

# 2. CSS : Tailwind par son binaire autonome (pas de Node), vérifié par son empreinte. Ne finit pas dans l'image.
FROM debian:trixie-slim AS assets
ARG TAILWIND_VERSION=4.3.3
ARG TARGETARCH
RUN apt-get update && apt-get install -y --no-install-recommends ca-certificates curl && rm -rf /var/lib/apt/lists/* \
 && case "$TARGETARCH" in \
      amd64) arch=x64;   sum=dc61b3ac6b8c9ca874c0cc4c57b2409791a64c5540404ca5f5367360babc313a ;; \
      arm64) arch=arm64; sum=55fd0b241214eff3de1e8ee4f22796662f2d2e7a49bcfca7477cfd0bac398195 ;; \
      *) echo "architecture non gérée : $TARGETARCH" >&2; exit 1 ;; \
    esac \
 && curl -fsSL -o /usr/local/bin/tailwindcss \
      "https://github.com/tailwindlabs/tailwindcss/releases/download/v${TAILWIND_VERSION}/tailwindcss-linux-${arch}" \
 && echo "$sum  /usr/local/bin/tailwindcss" | sha256sum -c - \
 && chmod +x /usr/local/bin/tailwindcss
WORKDIR /app
COPY assets/src assets/src
COPY views views
RUN tailwindcss -i assets/src/app.css -o assets/app.css --minify

# 3. L'image servie.
FROM ${PHP_IMAGE} AS app
RUN install-php-extensions gd exif \
 && apt-get update && apt-get install -y --no-install-recommends sqlite3 && rm -rf /var/lib/apt/lists/* \
 # L'image amont pose NET_BIND_SERVICE sur le binaire : avec cap_drop ALL il ne se lancerait plus. On écoute sur 8080.
 && setcap -r /usr/local/bin/frankenphp
COPY php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY Caddyfile /etc/frankenphp/Caddyfile
COPY . /app
COPY --from=deps /app/vendor /app/vendor
COPY --from=assets /app/assets/app.css /app/assets/app.css
# Le conteneur tourne en lecture seule avec l'uid de l'hôte : Caddy n'écrit que dans /data et /config (tmpfs).
ENV XDG_CONFIG_HOME=/config XDG_DATA_HOME=/data
WORKDIR /app
EXPOSE 8080
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
