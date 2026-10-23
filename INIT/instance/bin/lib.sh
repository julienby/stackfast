# Fonctions communes aux commandes de l'instance. Sourcé par bin/* (jamais lancé seul).
# Une app = apps/<stack>/<slug>/ avec son compose.yml et son .env ; le slug est unique dans l'instance.
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

die() { echo "stackfast : $*" >&2; exit 1; }
say() { printf '%-10s : %s\n' "$1" "$2"; }

[ -f .env ] || die ".env manquant : cp .env.example .env"
set -a
. ./.env
set +a
INSTANCE="${STACKFAST_INSTANCE:-main}"
PROXY="$INSTANCE-proxy"

# image <stack> : nom de l'image de la stack, sans tag (:current en service, :previous pour revenir).
image() { echo "sf-$INSTANCE-$1"; }

# stack_conf <stack> <VAR> : valeur d'une variable de stacks/<stack>/stack.conf.
stack_conf() {
  [ -f "stacks/$1/stack.conf" ] || die "stack inconnue : $1 (pas de stacks/$1/stack.conf)"
  ( . "./stacks/$1/stack.conf"; eval "printf '%s' \"\${$2:-}\"" )
}

# apps [stack] : une ligne "<stack> <slug>" par app.
apps() {
  for f in apps/${1:-*}/*/compose.yml; do
    [ -f "$f" ] || continue
    d="${f%/compose.yml}"
    echo "$(basename "$(dirname "$d")") $(basename "$d")"
  done
}

# app_dir <slug> : apps/<stack>/<slug>.
app_dir() {
  for d in apps/*/"$1"; do
    [ -f "$d/compose.yml" ] && { echo "$d"; return 0; }
  done
  die "app inconnue : $1 (voir stackfast ls)"
}

app_stack() { d="$(app_dir "$1")"; basename "$(dirname "$d")"; }

# compose_app <slug> <args…> : docker compose sur l'app. Le .env de l'instance sert à l'interpolation
# (instance, uid) ; les secrets de l'app passent par son propre .env (env_file), jamais par celui-ci.
compose_app() {
  d="$(app_dir "$1")"; shift
  docker compose --env-file "$ROOT/.env" -f "$d/compose.yml" "$@"
}

# domains <slug> : domaines de l'app (le premier est le principal, les suivants redirigent vers lui).
domains() {
  [ -f proxy/domains ] || return 0
  awk -v s="$1" '$1 == s { for (i = 2; i <= NF; i++) print $i }' proxy/domains
}

# app_url <slug> : adresse publique actuelle.
app_url() {
  main="$(domains "$1" | head -1)"
  if [ -n "$main" ]; then echo "https://$main/"
  elif [ -n "${APPS_HOST:-}" ]; then echo "https://$APPS_HOST/$1/"
  else echo "(aucune : renseigne APPS_HOST dans .env ou stackfast domain $1 <domaine>)"
  fi
}

# context_hash <stack> : empreinte de ce qui construit l'image (lib/ est monté, app-template/ copié : exclus).
context_hash() {
  ( cd "stacks/$1" && find . -type f ! -path './lib/*' ! -path './app-template/*' ! -name AGENTS.md ! -name .stackfast-sums \
      | LC_ALL=C sort | xargs sha256sum | sha256sum | cut -c1-16 )
}

# image_stale <stack> : vrai si l'image :current manque ou a été construite depuis un autre état de la stack.
image_stale() {
  built="$(docker image inspect -f '{{index .Config.Labels "stackfast.context"}}' "$(image "$1"):current" 2>/dev/null || true)"
  [ "$built" != "$(context_hash "$1")" ]
}

# app_container <slug> : nom du conteneur web (joint par le proxy).
app_container() { echo "$INSTANCE-$1"; }
