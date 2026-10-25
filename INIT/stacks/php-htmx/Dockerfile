# Image de la stack php-htmx : FrankenPHP (Caddy + PHP dans un seul processus), une instance par app.
# Construite par `stackfast update php-htmx` (jamais à la main) : --pull, tests, puis bascule des apps.
# Convention dans le conteneur :
#   /app                        = apps/php-htmx/<slug> (monté)
#   /stacks/php-htmx/lib        = stacks/php-htmx/lib (monté, lecture seule)
#   /stacks/php-htmx/vendor     = construit ici depuis composer.json
# L'index.php des apps cherche dirname(/app, 2) . '/stacks/php-htmx/lib' = /stacks/php-htmx/lib.

ARG BASE_IMAGE=dunglas/frankenphp:1-php8.5-trixie

FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

FROM ${BASE_IMAGE} AS app
RUN install-php-extensions gd exif \
 && apt-get update && apt-get install -y --no-install-recommends sqlite3 \
 && rm -rf /var/lib/apt/lists/*

# L'image amont pose NET_BIND_SERVICE sur le binaire (ports < 1024). Avec cap_drop ALL le noyau refuse alors
# de le lancer (« exec: frankenphp: Operation not permitted ») ; on écoute sur 8080, on la retire.
RUN setcap -r /usr/local/bin/frankenphp

COPY php.ini $PHP_INI_DIR/conf.d/zz-stackfast.ini
COPY Caddyfile /etc/frankenphp/Caddyfile
COPY healthcheck.php /usr/local/share/stackfast/healthcheck.php
COPY --from=deps /app/vendor /stacks/php-htmx/vendor

# Le conteneur tourne avec l'uid de l'hôte (compose : user:) et un système de fichiers en lecture seule :
# Caddy écrit seulement dans /data et /config (tmpfs).
ENV XDG_CONFIG_HOME=/config XDG_DATA_HOME=/data
WORKDIR /app
EXPOSE 8080
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]

# Sandbox d'agent (`stackfast agent`) : la même image + git, node et Claude Code. L'agent sert son app
# (preview) et lance ses tests avec le php de cette image ; il n'a ni docker, ni les autres apps.
# Build : docker build --target agent (bin/agent, bin/update). L'image des apps est la cible `app`.
FROM app AS agent
RUN apt-get update && apt-get install -y --no-install-recommends git nodejs npm ripgrep jq less procps \
 && rm -rf /var/lib/apt/lists/* \
 && npm install -g @anthropic-ai/claude-code && npm cache clean --force
COPY agent-entrypoint.sh /usr/local/bin/agent-entrypoint.sh
ENV HOME=/home/agent GIT_AUTHOR_NAME=agent GIT_AUTHOR_EMAIL=agent@sandbox GIT_COMMITTER_NAME=agent GIT_COMMITTER_EMAIL=agent@sandbox
WORKDIR /work
ENTRYPOINT ["/usr/local/bin/agent-entrypoint.sh"]
CMD ["sleep", "infinity"]
