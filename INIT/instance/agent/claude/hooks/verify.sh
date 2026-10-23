#!/usr/bin/env sh
# Fin de tour dans la sandbox : lint + tests de l'app. Exit 2 = Claude est bloqué et voit la sortie.
# Une seule relance par tour (stop_hook_active).
grep -q '"stop_hook_active": *true' && exit 0
cd /work || exit 0
[ -z "$(git status --porcelain apps 2>/dev/null)" ] && exit 0
out="$(APP_DIR=/work/apps/__STACK__/__SLUG__ LIB_DIR=/work/stacks/__STACK__/lib stacks/__STACK__/run-checks 2>&1)" && exit 0
echo "$out" >&2
exit 2
