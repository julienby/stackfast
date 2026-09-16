#!/usr/bin/env sh
# Fin de tour : bin/check. Exit 2 = Claude est bloqué et voit la sortie.
cd "$(dirname "$0")/../.."
out="$(bin/check 2>&1)" && exit 0
echo "$out" >&2
exit 2
