#!/bin/sh
# Remappe l'utilisateur www-data sur l'uid/gid de l'hôte avant de lancer Apache.
# Nécessaire : Apache ne peut pas abandonner ses privilèges vers un uid numérique
# sans entrée /etc/passwd (getpwuid échoue, Apache continue de tourner en root).
set -e

HOST_UID="${HOST_UID:-1000}"
HOST_GID="${HOST_GID:-1000}"

if [ "$(id -u www-data)" != "$HOST_UID" ]; then
    usermod -u "$HOST_UID" www-data
fi
if [ "$(id -g www-data)" != "$HOST_GID" ]; then
    groupmod -g "$HOST_GID" www-data
fi

exec "$@"
