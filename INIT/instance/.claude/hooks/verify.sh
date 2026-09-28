#!/usr/bin/env sh
# Fin de tour : bin/check. Exit 2 = Claude est bloqué et voit la sortie.
# Une seule relance par tour (stop_hook_active), et rien à vérifier si apps/ et stacks/ n'ont pas changé.
cd "$(dirname "$0")/../.."
grep -q '"stop_hook_active": *true' && exit 0
[ -z "$(git status --porcelain apps stacks)" ] && exit 0
out="$(bin/check 2>&1)" && exit 0
echo "$out" >&2
exit 2
