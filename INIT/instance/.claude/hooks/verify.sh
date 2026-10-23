#!/usr/bin/env sh
# Fin de tour : bin/check sur ce qui a changé. Exit 2 = Claude est bloqué et voit la sortie.
# Une seule relance par tour (stop_hook_active). Rien n'a changé → rien. stacks/ a changé → toutes les apps ;
# sinon seulement les apps touchées (une par conteneur jetable : 20 apps ne se revérifient pas à chaque tour).
cd "$(dirname "$0")/../.."
grep -q '"stop_hook_active": *true' && exit 0
changed="$(git status --porcelain apps stacks | cut -c4-)"
[ -z "$changed" ] && exit 0
if echo "$changed" | grep -q '^stacks/'; then
  out="$(bin/check 2>&1)" && exit 0
else
  status=0
  out=""
  for slug in $(echo "$changed" | awk -F/ '$1 == "apps" && NF >= 3 && $3 != "" { print $3 }' | sort -u); do
    out="$out$(bin/check "$slug" 2>&1)
" || status=1
  done
  [ "$status" -eq 0 ] && exit 0
fi
echo "$out" >&2
exit 2
