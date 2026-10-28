#!/usr/bin/env sh
# Lint + marqueurs de conflit + tests de l'app, DANS le conteneur (lancé par bin/check, qui se lance depuis l'hôte).
set -eu
cd "$(dirname "$0")/.."

status=0
lint=0
for f in $(find . -name '*.php' -not -path './vendor/*' -not -path './data/*'); do
  php -l "$f" >/dev/null 2>&1 || { php -l "$f" 2>&1 | head -1; lint=1; }
done
[ "$lint" -eq 0 ] && echo "lint php : ok" || { echo "lint php : FAIL"; status=1; }

# Marqueurs de conflit laissés par une fusion : dans une vue HTML, php -l ne les voit pas.
conflicts="$(grep -rnE '^(<<<<<<<|>>>>>>>) ' . --include='*.php' --include='*.md' --include='*.json' --include='*.sql' \
  --exclude-dir=vendor --exclude-dir=data 2>/dev/null || true)"
if [ -n "$conflicts" ]; then echo "$conflicts" | head -5; echo "conflits : FAIL (résous les marqueurs <<<<<<< / >>>>>>>)"; status=1
else echo "conflits : aucun marqueur"; fi

for t in $(find tests -name '*_test.php' | sort); do
  echo "== $t"
  php "$t" || status=1
done
exit "$status"
