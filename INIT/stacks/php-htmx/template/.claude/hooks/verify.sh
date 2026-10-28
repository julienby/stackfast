#!/usr/bin/env sh
# Fin de tour : si le code a changé, recompile le CSS puis lance bin/check. Exit 2 = Claude est bloqué et voit la sortie.
# Une seule relance par tour (stop_hook_active).
cd "$(dirname "$0")/../.." || exit 0
grep -q '"stop_hook_active": *true' && exit 0
[ -z "$(git status --porcelain 2>/dev/null)" ] && exit 0
out="$(bin/build 2>&1 && bin/check 2>&1)" && exit 0
echo "$out" >&2
exit 2
