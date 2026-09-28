#!/usr/bin/env sh
# Installe ou met à jour le client stackfast.
#
#   curl -fsSL https://raw.githubusercontent.com/julienby/stackfast/main/INIT/install.sh | sh
#   curl -fsSL https://raw.githubusercontent.com/julienby/stackfast/main/INIT/install.sh | sh -s -- mon-vps
#
# Idempotent :
#   - hub      : clone permanent dans ~/.stackfast (git pull s'il existe déjà) ;
#   - client   : ~/.local/bin/stackfast, lien vers le hub ;
#   - liens    : ~/.claude/CLAUDE.md, ~/.codex/AGENTS.md, skills (un fichier présent est sauvegardé) ;
#   - instance : si un dossier est donné, `stackfast init <dossier>`.
# Prérequis : git. STACKFAST_HOME=<clone existant> réutilise un clone (développement du hub).
set -eu

REPO="${STACK_REPO:-https://github.com/julienby/stackfast.git}"
HUB="${STACKFAST_HOME:-$HOME/.stackfast}"

if [ -d "$HUB/.git" ]; then
  git -C "$HUB" pull --ff-only
else
  git clone --quiet "$REPO" "$HUB"
  echo "hub cloné  : $HUB"
fi

mkdir -p "$HOME/.local/bin"
ln -sfn "$HUB/INIT/stackfast" "$HOME/.local/bin/stackfast"
echo "client     : $HOME/.local/bin/stackfast"

"$HUB/INIT/stackfast" link

if [ $# -gt 0 ]; then
  "$HUB/INIT/stackfast" init "$1"
fi

case ":$PATH:" in
  *":$HOME/.local/bin:"*) ;;
  *) echo "Ajoute ~/.local/bin à ton PATH : export PATH=\"\$HOME/.local/bin:\$PATH\"" ;;
esac
