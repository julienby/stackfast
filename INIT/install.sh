#!/usr/bin/env sh
# Installe ma stack IA : méthode globale + gabarit d'instance (un VPS) + stacks.
#
#   curl -fsSL https://raw.githubusercontent.com/julienby/stackfast/main/INIT/install.sh | sh -s -- mon-vps
#   sh INIT/install.sh .            # dans un repo d'instance existant (mise à jour des stacks)
#
# Idempotent :
#   - global   : ~/.claude/CLAUDE.md et ~/.codex/AGENTS.md, sauvegardés si différents ;
#   - instance : INIT/instance/* copié SANS écraser (AGENTS.md, bin/, proxy/, .claude/…) ;
#   - stacks   : INIT/stacks/* copié EN ÉCRASANT (elles appartiennent au hub) ; apps/ jamais touché.
# Prérequis : git. STACK_SRC=<chemin local du hub> évite le clone (développement du hub).
set -eu

REPO="${STACK_REPO:-https://github.com/julienby/stackfast.git}"
DEST="${1:-.}"

if [ -n "${STACK_SRC:-}" ]; then
  SRC="$STACK_SRC/INIT"
else
  TMP="$(mktemp -d)"
  trap 'rm -rf "$TMP"' EXIT
  git clone --quiet --depth 1 "$REPO" "$TMP/stack"
  SRC="$TMP/stack/INIT"
fi

# 1. Global : méthode de travail, pour chaque client connu.
install_global() {
  target="$1"
  mkdir -p "$(dirname "$target")"
  if [ -f "$target" ] && ! cmp -s "$SRC/CLAUDE.global.md" "$target"; then
    cp "$target" "$target.bak.$(date +%Y%m%d%H%M%S)"
    echo "global existant sauvegardé : $target.bak.*"
  fi
  cp "$SRC/CLAUDE.global.md" "$target"
  echo "global posé : $target"
}
install_global "$HOME/.claude/CLAUDE.md"
install_global "$HOME/.codex/AGENTS.md"

# 2. Instance : sans écraser un fichier déjà présent.
mkdir -p "$DEST"
( cd "$SRC/instance" && find . \( -type f -o -type l \) ) | while read -r f; do
  if [ -e "$DEST/$f" ] || [ -L "$DEST/$f" ]; then
    echo "conservé  : $f"
  else
    mkdir -p "$DEST/$(dirname "$f")"
    cp -P "$SRC/instance/$f" "$DEST/$f"
    echo "créé      : $f"
  fi
done

# 3. Stacks : en écrasant, apps/ jamais touché.
for stack in "$SRC"/stacks/*/; do
  name="$(basename "$stack")"
  mkdir -p "$DEST/stacks/$name"
  cp -R "$stack". "$DEST/stacks/$name/"
  echo "stack     : stacks/$name (synchronisée)"
done

# 4. Git.
if [ ! -d "$DEST/.git" ]; then
  ( cd "$DEST" && git init -q )
  echo "git init  : $DEST"
fi

echo
echo "Prêt. Ensuite : cd $DEST ; cp .env.example .env ; bin/new-app php-htmx <slug>."
