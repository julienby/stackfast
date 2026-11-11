#!/usr/bin/env sh
# Tests de jbh dans un projet (add, status, sync, remove) contre un serveur PHP jetable.
#   tests/projet.sh
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
est() { [ "$3" = "$2" ] && ok || bad "$1" "$2" "$3"; }
contient() { case "$3" in *"$2"*) ok ;; *) bad "$1" "contient : $2" "$3" ;; esac; }

# --- données dans jbh --------------------------------------------------------

mkdir -p "$tmp/src/skill" "$tmp/projet"
printf 'un\ndeux\ntrois\nquatre\ncinq\n' >"$tmp/src/AGENTS.md"
"$jbh" update instructions-md web-php --with "$tmp/src/AGENTS.md" >/dev/null
printf 'api\n' >"$tmp/src/api.md"
"$jbh" update instructions-md api --with "$tmp/src/api.md" >/dev/null
printf '# explain v1\n' >"$tmp/src/skill/SKILL.md"
printf 'notes\n' >"$tmp/src/skill/notes.md"
"$jbh" update skill explain --with "$tmp/src/skill" >/dev/null

cd "$tmp/projet"

# --- 1. sans .jbh ------------------------------------------------------------

"$jbh" status >/dev/null 2>&1; code=$?
est "status sans .jbh : échec" 1 "$code"

# --- 2. add ------------------------------------------------------------------

est "add instructions : message" "ajouté : instructions-md/web-php v1 → AGENTS.md" "$("$jbh" add instructions-md web-php)"
est "add instructions : contenu" "un deux trois quatre cinq" "$(tr '\n' ' ' <AGENTS.md | sed 's/ $//')"
est "add instructions : CLAUDE.md renvoie à AGENTS.md" "@AGENTS.md" "$(cat CLAUDE.md 2>/dev/null)"
est "add instructions : .jbh" "instructions-md web-php 1 AGENTS.md" "$(cat .jbh)"

"$jbh" add instructions-md api --to app/AGENTS.md >/dev/null
est "add --to : sous-dossier" "api" "$(cat app/AGENTS.md 2>/dev/null)"
est "add --to : CLAUDE.md à côté" "@AGENTS.md" "$(cat app/CLAUDE.md 2>/dev/null)"

"$jbh" add skill explain >/dev/null
est "add skill : destination par défaut" "# explain v1" "$(cat .claude/skills/explain/SKILL.md 2>/dev/null)"
est "add skill : deuxième fichier" "notes" "$(cat .claude/skills/explain/notes.md 2>/dev/null)"

"$jbh" add skill explain >/dev/null 2>&1; code=$?
est "add deux fois : échec" 1 "$code"

printf 'à moi' >perso.md
"$jbh" add skill explain --to perso.md >/dev/null 2>&1; code=$?
est "add sur un fichier existant : échec" 1 "$code"
est "add sur un fichier existant : intact" "à moi" "$(cat perso.md)"

# --- 3. status sans changement -----------------------------------------------

est "status : tout à jour" "instructions-md/api v1 à jour
instructions-md/web-php v1 à jour
skill/explain v1 à jour" "$("$jbh" status | sort)"
est "sync sans changement" "à jour" "$("$jbh" sync)"

# --- 4. fusion propre : local et distant ont changé des lignes différentes ---

sed -i 's/^un$/un local/' AGENTS.md
printf 'un\ndeux\ntrois\nquatre\ncinq distant\n' >"$tmp/src/AGENTS.md"
"$jbh" update instructions-md web-php --with "$tmp/src/AGENTS.md" >/dev/null

est "status : en retard et modifié" "instructions-md/web-php v1 → v2 (modifié en local)" "$("$jbh" status | grep web-php)"

out="$("$jbh" sync)"; code=$?
est "sync fusion : succès" 0 "$code"
contient "sync fusion : message" "fusionné : AGENTS.md" "$out"
est "sync fusion : les deux changements" "un local deux trois quatre cinq distant" "$(tr '\n' ' ' <AGENTS.md | sed 's/ $//')"
est "sync fusion : .jbh à v2" "instructions-md web-php 2 AGENTS.md" "$(grep web-php .jbh)"
est "sync après fusion" "à jour" "$("$jbh" sync)"

# --- 5. conflit : même ligne changée des deux côtés --------------------------

printf 'un distant\ndeux\ntrois\nquatre\ncinq distant\n' >"$tmp/src/AGENTS.md"
"$jbh" update instructions-md web-php --with "$tmp/src/AGENTS.md" >/dev/null

out="$("$jbh" sync)"; code=$?
est "sync conflit : échec" 1 "$code"
contient "sync conflit : message" "CONFLIT : AGENTS.md" "$out"
contient "sync conflit : marqueurs dans le fichier" "<<<<<<<" "$(cat AGENTS.md)"
contient "sync conflit : le texte local est gardé" "un local" "$(cat AGENTS.md)"
est "sync conflit : .jbh à v3" "instructions-md web-php 3 AGENTS.md" "$(grep web-php .jbh)"

# --- 6. fichier local intact : remplacé, fichier retiré disparaît ------------

printf 'à moi' >.claude/skills/explain/local.md
rm "$tmp/src/skill/notes.md"
printf '# explain v2\n' >"$tmp/src/skill/SKILL.md"
"$jbh" update skill explain --with "$tmp/src/skill" >/dev/null

"$jbh" sync >/dev/null
est "sync : fichier non modifié remplacé" "# explain v2" "$(cat .claude/skills/explain/SKILL.md)"
est "sync : fichier retiré à distance supprimé" "absent" "$([ -e .claude/skills/explain/notes.md ] && echo présent || echo absent)"
est "sync : fichier ajouté en local intact" "à moi" "$(cat .claude/skills/explain/local.md)"

# --- 7. remove ---------------------------------------------------------------

printf 'api retouché' >app/AGENTS.md
out="$("$jbh" remove instructions-md api)"
contient "remove : fichier modifié gardé" "gardé" "$out"
est "remove : fichier modifié présent" "api retouché" "$(cat app/AGENTS.md)"
est "remove : .jbh sans la ligne" "0" "$(grep -c ' api ' .jbh)"

"$jbh" remove skill explain >/dev/null
est "remove : fichier non modifié supprimé" "absent" "$([ -e .claude/skills/explain/SKILL.md ] && echo présent || echo absent)"
est "remove : fichier local conservé" "à moi" "$(cat .claude/skills/explain/local.md)"

"$jbh" remove skill inconnu >/dev/null 2>&1; code=$?
est "remove inconnu : échec" 1 "$code"

# --- bilan -------------------------------------------------------------------

echo "$pass réussis, $fail échecs"
[ "$fail" -eq 0 ]
