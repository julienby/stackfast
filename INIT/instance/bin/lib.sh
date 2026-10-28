# Fonctions communes aux commandes de l'instance. Sourcé par bin/* (jamais lancé seul).
set -eu
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

die() { echo "stackfast : $*" >&2; exit 1; }
say() { printf '%-10s : %s\n' "$1" "$2"; }

# .env d'instance obligatoire, sauf pour `restore` (qui le restaure d'une sauvegarde : STACKFAST_ALLOW_NO_ENV=1).
if [ -f .env ]; then
  set -a
  . ./.env
  set +a
elif [ "${STACKFAST_ALLOW_NO_ENV:-}" != 1 ]; then
  die ".env manquant : cp .env.example .env"
fi
INSTANCE="${STACKFAST_INSTANCE:-main}"
PROXY="$INSTANCE-proxy"

# Une app = apps/<slug>/, un dépôt git autonome avec son stackfast.json (recette, port…), son compose.yml, son .env.
# stackfast agit de l'extérieur : il ne fait que lancer ce que l'app sait faire (bin/build, bin/check, compose).

# json_get <fichier> <clé> : valeur texte ou nombre d'une clé de premier niveau (nos stackfast.json sont plats).
json_get() { sed -n "s/^ *\"$2\": *\"\{0,1\}\([^\",]*\)\"\{0,1\},\{0,1\} *$/\1/p" "$1" | head -1; }

# apps : une ligne "<recette> <slug>" par app.
apps() {
  for f in apps/*/stackfast.json; do
    [ -f "$f" ] || continue
    echo "$(json_get "$f" stack) $(basename "$(dirname "$f")")"
  done
}

# app_dir <slug> : apps/<slug>.
app_dir() {
  [ -f "apps/$1/stackfast.json" ] || die "app inconnue : $1 (voir stackfast ls)"
  echo "apps/$1"
}

app_stack() { json_get "$(app_dir "$1")/stackfast.json" stack; }
app_port() { p="$(json_get "$(app_dir "$1")/stackfast.json" port)"; echo "${p:-8080}"; }

# app_image <slug> : image de l'app (:current en service, :previous pour revenir en arrière).
app_image() { echo "$INSTANCE-$1"; }

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

# app_container <slug> : nom du conteneur web (joint par le proxy).
app_container() { echo "$INSTANCE-$1"; }

# --- Agents : une copie de travail isolée par agent, .agents/<slug>/<nom>/ (jamais dans git) ---
AGENTS_DIR=".agents"

agent_dir() { echo "$AGENTS_DIR/$1/$2"; }
agent_container() { echo "$INSTANCE-agent-$1-$2"; }

# agents : une ligne "<slug> <nom>" par agent existant (dossier créé par bin/agent).
agents() {
  for m in "$AGENTS_DIR"/*/*/meta; do
    [ -f "$m" ] || continue
    d="${m%/meta}"
    echo "$(basename "$(dirname "$d")") $(basename "$d")"
  done
}

# agent_meta <slug> <nom> <CLÉ> : valeur dans le fichier meta de l'agent.
agent_meta() { sed -n "s/^$3=//p" "$(agent_dir "$1" "$2")/meta" | head -1; }

# agent_running <slug> <nom> : vrai si le conteneur de l'agent tourne.
agent_running() { [ "$(docker inspect -f '{{.State.Running}}' "$(agent_container "$1" "$2")" 2>/dev/null || true)" = true ]; }

# agent_url <slug> <nom> : adresse de la preview (derrière le proxy, avec mot de passe).
agent_url() {
  if [ -n "${APPS_HOST:-}" ]; then echo "https://$APPS_HOST/_preview/$1/$2/"
  else echo "(APPS_HOST manquant dans .env)"
  fi
}

# --- Sauvegardes ---
# cat_archive <fichier> : flux tar.gz en clair (déchiffre une archive .enc avec BACKUP_PASSPHRASE).
cat_archive() {
  case "$1" in
    *.enc)
      [ -n "${BACKUP_PASSPHRASE:-}" ] || die "archive chiffrée : exporte BACKUP_PASSPHRASE (la phrase utilisée à la sauvegarde)"
      openssl enc -d -aes-256-cbc -pbkdf2 -pass env:BACKUP_PASSPHRASE -in "$1" ;;
    *) cat "$1" ;;
  esac
}

# last_backup : chemin de la sauvegarde la plus récente de cette instance (vide s'il n'y en a pas).
last_backup() {
  ls -1t "${BACKUP_DIR:-backups}/$INSTANCE"-*.tar.gz* 2>/dev/null | head -1 || true
}
