#!/usr/bin/env sh
# Tests du CLI jbh contre un serveur PHP jetable : port, base et jeton uniques.
#   tests/cli.sh
set -u
cd "$(dirname "$0")/.."
root="$(pwd)"

tmp="$(mktemp -d)"
port="$(shuf -i 20000-40000 -n 1)"
token="jeton-de-test-$$"
export JBH_DB="$tmp/jbh.sqlite" JBH_TOKEN="$token" JBH_URL="http://127.0.0.1:$port"

php -S "127.0.0.1:$port" app/index.php >"$tmp/serveur.log" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null; rm -rf "$tmp"' EXIT

for _ in 1 2 3 4 5 6 7 8 9 10; do
  curl -s -o /dev/null "$JBH_URL/" && break
  sleep 0.5
done

jbh="$root/bin/jbh"

pass=0
fail=0
ok()  { pass=$((pass + 1)); }
bad() { fail=$((fail + 1)); printf 'ÉCHEC : %s\n  attendu : %s\n  obtenu  : %s\n' "$1" "$2" "$3"; }

# est ÉTIQUETTE ATTENDU OBTENU
est() { [ "$3" = "$2" ] && ok || bad "$1" "$2" "$3"; }

# --- données de test ---------------------------------------------------------

mkdir -p "$tmp/skill/scripts"
printf '# un' >"$tmp/skill/SKILL.md"
printf 'echo salut' >"$tmp/skill/scripts/run.sh"
printf '# global' >"$tmp/CLAUDE.md"

# --- 1. jeton ----------------------------------------------------------------

out="$(JBH_TOKEN='' "$jbh" list 2>&1)"; code=$?
est "sans jeton : échec" 1 "$code"
case "$out" in *JBH_TOKEN*) ok ;; *) bad "sans jeton : le message nomme JBH_TOKEN" "JBH_TOKEN dans le message" "$out" ;; esac

JBH_TOKEN=mauvais "$jbh" list >/dev/null 2>&1; code=$?
est "mauvais jeton : échec" 1 "$code"

# --- 2. list -----------------------------------------------------------------

est "liste vide" "" "$("$jbh" list)"

# --- 3. update d'un dossier --------------------------------------------------

est "update dossier : création" "skill/xyz v1" "$("$jbh" update skill xyz --with "$tmp/skill")"
est "list : une ligne" "skill xyz v1" "$("$jbh" list)"

# --- 4. get --to : les fichiers reviennent à l'identique ---------------------

"$jbh" get skill xyz --to "$tmp/sortie" >/dev/null
est "get --to : SKILL.md" "# un" "$(cat "$tmp/sortie/SKILL.md" 2>/dev/null)"
est "get --to : sous-dossier" "echo salut" "$(cat "$tmp/sortie/scripts/run.sh" 2>/dev/null)"

# --- 5. update d'un fichier seul ---------------------------------------------

est "update fichier : création" "claude-md/global v1" "$("$jbh" update claude-md global --with "$tmp/CLAUDE.md")"
est "get d'un seul fichier : contenu" "# global" "$("$jbh" get claude-md global)"
est "list claude-md" "claude-md global v1" "$("$jbh" list claude-md)"

# --- 6. nouvelle version -----------------------------------------------------

printf '# deux' >"$tmp/skill/SKILL.md"
est "update : version 2" "skill/xyz v2" "$("$jbh" update skill xyz --with "$tmp/skill")"

# --- 7. delete ---------------------------------------------------------------

"$jbh" delete skill xyz >/dev/null
est "list après delete" "claude-md global v1" "$("$jbh" list)"

# --- 8. erreurs --------------------------------------------------------------

"$jbh" get skill inconnu >/dev/null 2>&1; code=$?
est "get inconnu : échec" 1 "$code"

"$jbh" update skill x --with "$tmp/absent" >/dev/null 2>&1; code=$?
est "update d'un chemin absent : échec" 1 "$code"

"$jbh" update autre x --with "$tmp/CLAUDE.md" >/dev/null 2>&1; code=$?
est "update d'un type inconnu : échec" 1 "$code"

"$jbh" nimporte >/dev/null 2>&1; code=$?
est "commande inconnue : échec" 1 "$code"

# --- 9. pull (dans un faux HOME : jamais le vrai) ----------------------------

home="$tmp/home"
mkdir -p "$home/.claude/skills/ancien" "$home/.codex/skills/ancien"
printf 'à moi' >"$home/.claude/skills/ancien/SKILL.md"
printf '# local' >"$home/.claude/CLAUDE.md"
pull() { HOME="$home" "$jbh" pull; }

printf '# un' >"$tmp/skill/SKILL.md"
"$jbh" update skill xyz --with "$tmp/skill" >/dev/null
"$jbh" update skill ancien --with "$tmp/skill" >/dev/null
"$jbh" update claude-md autre --with "$tmp/CLAUDE.md" >/dev/null

pull >"$tmp/pull1" 2>&1; code=$?
est "pull : succès" 0 "$code"
est "pull : CLAUDE.md pour Claude" "# global" "$(cat "$home/.claude/CLAUDE.md" 2>/dev/null)"
est "pull : AGENTS.md pour Codex" "# global" "$(cat "$home/.codex/AGENTS.md" 2>/dev/null)"
est "pull : skill Claude" "# un" "$(cat "$home/.claude/skills/xyz/SKILL.md" 2>/dev/null)"
est "pull : sous-dossier Claude" "echo salut" "$(cat "$home/.claude/skills/xyz/scripts/run.sh" 2>/dev/null)"
est "pull : skill Codex" "# un" "$(cat "$home/.codex/skills/xyz/SKILL.md" 2>/dev/null)"
est "pull : claude-md autre que global ignoré" "" "$(ls "$home/.claude" "$home/.codex" | grep -i autre)"
est "pull : ancien CLAUDE.md sauvegardé" "# local" "$(cat "$home"/.config/jbh/sauvegardes/*claude_CLAUDE.md 2>/dev/null)"
est "pull : ancien skill local sauvegardé" "à moi" "$(cat "$home"/.config/jbh/sauvegardes/*claude_skills_ancien/SKILL.md 2>/dev/null)"

# deuxième pull sans changement : rien n'est réécrit
printf 'retouché' >"$home/.claude/skills/xyz/SKILL.md"
est "pull sans changement : message" "à jour" "$(pull)"
est "pull sans changement : fichier intact" "retouché" "$(cat "$home/.claude/skills/xyz/SKILL.md")"

# nouvelle version : réécrite, fichier retiré disparu
printf '# trois' >"$tmp/skill-v3.md"
"$jbh" update skill xyz --with "$tmp/skill-v3.md" >/dev/null
est "pull nouvelle version : message" "skill/xyz v2" "$(pull)"
est "pull nouvelle version : contenu" "# trois" "$(cat "$home/.claude/skills/xyz/skill-v3.md" 2>/dev/null)"
est "pull nouvelle version : ancien fichier retiré" "absent" "$([ -e "$home/.claude/skills/xyz/scripts/run.sh" ] && echo présent || echo absent)"

# --- bilan -------------------------------------------------------------------

echo "$pass réussis, $fail échecs"
[ "$fail" -eq 0 ]
