#!/bin/sh
# Entrée de la sandbox d'un agent : lance le serveur de l'app (preview) en arrière-plan, initialise le dépôt git
# de travail (propre à la sandbox, jamais lu par l'hôte), puis exécute la commande demandée (sleep par défaut).
set -e

frankenphp run --config /etc/frankenphp/Caddyfile >> /home/agent/server.log 2>&1 &

cd /work
if [ ! -d .git ]; then
  printf 'data/\n.env\nvendor\n' > .gitignore
  git init -q
  git add -A
  git commit -qm "base" || true
fi

exec "$@"
