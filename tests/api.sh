#!/usr/bin/env sh
# Tests de l'API jbh. Lance un serveur PHP jetable : port, base et jeton uniques.
#   tests/api.sh
set -u
cd "$(dirname "$0")/.."

tmp="$(mktemp -d)"
port="$(shuf -i 20000-40000 -n 1)"
token="jeton-de-test-$$"
export JBH_DB="$tmp/jbh.sqlite" JBH_TOKEN="$token"
base="http://127.0.0.1:$port"

php -S "127.0.0.1:$port" app/index.php >"$tmp/serveur.log" 2>&1 &
pid=$!
trap 'kill "$pid" 2>/dev/null; rm -rf "$tmp"' EXIT

for _ in 1 2 3 4 5 6 7 8 9 10; do
  curl -s -o /dev/null "$base/" && break
  sleep 0.5
done

# --- outils ---------------------------------------------------------------

pass=0
fail=0

# req METHODE CHEMIN [CORPS] : remplit $status et $tmp/corps. Le jeton valide est envoyé,
# sauf si $auth est défini (vide = pas d'en-tête).
req() {
  method="$1"; path="$2"; data="${3:-}"
  set -- -s -o "$tmp/corps" -w '%{http_code}' -X "$method" "$base$path"
  [ "${auth-$token}" ] && set -- "$@" -H "Authorization: Bearer ${auth-$token}"
  [ -n "$data" ] && set -- "$@" -H 'Content-Type: application/json' -d "$data"
  status="$(curl "$@")"
}

ok()  { pass=$((pass + 1)); }
bad() { fail=$((fail + 1)); printf 'ÉCHEC : %s\n  attendu : %s\n  obtenu  : %s\n' "$1" "$2" "$3"; }

statut() { [ "$status" = "$2" ] && ok || bad "$1 (statut)" "$2" "$status"; }

# json ÉTIQUETTE EXPRESSION_JQ VALEUR_ATTENDUE
json() {
  got="$(jq -r "$2" "$tmp/corps" 2>/dev/null)"
  [ "$got" = "$3" ] && ok || bad "$1" "$3" "$got"
}

# --- 1. accès : lecture privée --------------------------------------------

auth='' req GET /items
statut "sans jeton : refus" 401
auth='mauvais' req GET /items
statut "mauvais jeton : refus" 401
auth='' req GET /
statut "la racine est privée aussi" 401

# --- 2. la racine décrit l'API --------------------------------------------

req GET /
statut "racine" 200
json "racine : lien vers les items" '._links.items.href' '/items'
json "racine : lien vers elle-même" '._links.self.href' '/'

# --- 3. collection vide ----------------------------------------------------

req GET /items
statut "liste vide" 200
json "liste vide : aucun item" '.items | length' '0'
json "liste : lien self" '._links.self.href' '/items'

# --- 4. création -----------------------------------------------------------

req PUT /items/skill/xyz '{"files":[{"path":"SKILL.md","content":"# un"},{"path":"scripts/run.sh","content":"echo salut"}]}'
statut "création d'un skill" 201
json "création : version 1" '.version' '1'
json "création : lien self" '._links.self.href' '/items/skill/xyz'

# --- 5. lecture d'un item --------------------------------------------------

req GET /items/skill/xyz
statut "lecture" 200
json "lecture : type" '.type' 'skill'
json "lecture : nom" '.name' 'xyz'
json "lecture : version" '.version' '1'
json "lecture : deux fichiers" '.files | length' '2'
json "lecture : contenu de SKILL.md" '.files[] | select(.path=="SKILL.md") | .content' '# un'
json "lecture : sous-dossier conservé" '.files[] | select(.path=="scripts/run.sh") | .content' 'echo salut'
json "lecture : lien collection" '._links.collection.href' '/items'
json "lecture : lien versions" '._links.versions.href' '/items/skill/xyz/versions'
json "lecture : lien update" '._links.update.method' 'PUT'
json "lecture : lien delete" '._links.delete.method' 'DELETE'

# --- 6. mise à jour : une nouvelle version, l'ancienne reste ----------------

req PUT /items/skill/xyz '{"files":[{"path":"SKILL.md","content":"# deux"}]}'
statut "mise à jour" 200
json "mise à jour : version 2" '.version' '2'

req GET /items/skill/xyz
json "lecture : dernière version" '.files[0].content' '# deux'
json "lecture : un seul fichier dans la v2" '.files | length' '1'

# --- 7. historique ---------------------------------------------------------

req GET /items/skill/xyz/versions
statut "liste des versions" 200
json "versions : 2 entrées" '.versions | length' '2'
json "versions : lien vers la v1" '.versions[] | select(.number==1) | ._links.self.href' '/items/skill/xyz/versions/1'

req GET /items/skill/xyz/versions/1
statut "lecture de la v1" 200
json "v1 : contenu d'origine" '.files[] | select(.path=="SKILL.md") | .content' '# un'
json "v1 : deux fichiers" '.files | length' '2'

req GET /items/skill/xyz/versions/9
statut "version inconnue" 404

# --- 8. liste et filtre par type -------------------------------------------

req GET '/items?type=skill'
json "liste skill : 1 item" '.items | length' '1'
json "liste skill : lien de l'item" '.items[0]._links.self.href' '/items/skill/xyz'
json "liste skill : version courante" '.items[0].version' '2'

req GET '/items?type=claude-md'
json "liste claude-md : vide" '.items | length' '0'

req GET '/items?type=autre'
statut "filtre : type inconnu" 400

# --- 9. entrées invalides --------------------------------------------------

req PUT /items/autre/x '{"files":[{"path":"a.md","content":"x"}]}'
statut "type inconnu" 400

req PUT /items/skill/x '{"files":[{"path":"../evade.md","content":"x"}]}'
statut "chemin avec .." 400

req PUT /items/skill/x '{"files":[{"path":"/abs.md","content":"x"}]}'
statut "chemin absolu" 400

req PUT /items/skill/x '{"files":[]}'
statut "aucun fichier" 400

req PUT /items/skill/x 'pas du json'
statut "corps illisible" 400

req PUT '/items/skill/mauvais%20nom' '{"files":[{"path":"a.md","content":"x"}]}'
statut "nom avec espace" 400

req GET /items/skill/x
statut "rien n'a été créé par les refus" 404

# --- 10. suppression -------------------------------------------------------

req DELETE /items/skill/xyz
statut "suppression" 204

req GET /items/skill/xyz
statut "lecture après suppression" 404

req GET /items/skill/xyz/versions/1
statut "versions supprimées avec l'item" 404

req DELETE /items/skill/xyz
statut "supprimer deux fois" 404

req GET /items/skill/inconnu
statut "item inconnu" 404

# --- bilan -----------------------------------------------------------------

echo "$pass réussis, $fail échecs"
[ "$fail" -eq 0 ]
