#!/usr/bin/env sh
# Installe ma stack IA (méthode globale + kit projet) sur n'importe quelle machine.
#
#   curl -fsSL https://raw.githubusercontent.com/julienby/ma-stack-ia/main/INIT/install.sh | sh -s -- mon-projet
#   curl -fsSL .../install.sh | sh -s -- mon-projet php-htmx     # kit explicite (défaut : php-htmx)
#
# Indépendant du fournisseur : le contrat projet est AGENTS.md (CLAUDE.md = lien),
# le contrat global est posé pour Claude Code (~/.claude/CLAUDE.md) et Codex (~/.codex/AGENTS.md).
# Prérequis : git. Idempotent : ne casse pas un projet existant, sauvegarde un global existant.
set -eu

REPO="${STACK_REPO:-https://github.com/julienby/ma-stack-ia.git}"
DEST="${1:?usage: install.sh <répertoire-projet> [kit]}"
KIT="${2:-php-htmx}"

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
git clone --quiet --depth 1 "$REPO" "$TMP/stack"
SRC="$TMP/stack/INIT"

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

# 2. Projet : le kit, sans écraser un fichier déjà présent.
mkdir -p "$DEST"
( cd "$SRC/$KIT" && find . -type f -o -type l ) | while read -r f; do
  if [ -e "$DEST/$f" ]; then
    echo "conservé  : $f"
  else
    mkdir -p "$DEST/$(dirname "$f")"
    cp -P "$SRC/$KIT/$f" "$DEST/$f"
    echo "créé      : $f"
  fi
done

# 3. Git.
if [ ! -d "$DEST/.git" ]; then
  ( cd "$DEST" && git init -q )
  echo "git init  : $DEST"
fi

echo
echo "Prêt. Ensuite : cd $DEST ; remplir PRD.md ; lancer claude (ou codex)."
