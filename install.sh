#!/usr/bin/env bash
# WordPress Orchestrator framework installer (macOS / Linux)
# Copies agents/ and skills/ into ~/.claude so Claude Code can load them.
#
#   ./install.sh            overwrite in place (a file deleted upstream is NOT removed)
#   ./install.sh --clean    first remove, from the destination, every top-level entry THIS repo
#                           ships (each skill folder, each top-level file under skills/, each file
#                           under agents/) and copy the repo's version fresh, so a file retired
#                           inside a framework skill stops lingering. Anything the repo does not
#                           name is left untouched. A whole skill or agent the repo retired by
#                           name is NOT removed: no manifest is kept, so delete it by hand.
#
# INSTALL_DEST overrides the destination (default ~/.claude); it exists so the installer can be
# tested against a temp directory. Set to the empty string it is an error, never "use the default".
set -euo pipefail
SRC="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

CLEAN=0
for arg in "$@"; do
  case "$arg" in
    --clean) CLEAN=1 ;;
    *) echo "install.sh: unknown argument '$arg' (the only option is --clean)" >&2; exit 2 ;;
  esac
done

DEST="${INSTALL_DEST-$HOME/.claude}"
if [ -z "$DEST" ]; then
  echo "install.sh: INSTALL_DEST is set but empty; refusing to guess a destination" >&2
  exit 2
fi

# Copying a checkout onto itself is never meant, and --clean would delete the repo's own skills/ and
# agents/. -ef compares the real directories, so a trailing slash, .. or a symlink cannot hide it.
for sub in skills agents; do
  if [ "$DEST/$sub" -ef "$SRC/$sub" ]; then
    echo "install.sh: INSTALL_DEST resolves to this checkout ($sub/ is the source itself); refusing, nothing changed" >&2
    exit 2
  fi
done

if [ "$CLEAN" -eq 1 ]; then
  REPLACED=""
  shopt -s dotglob   # the copy step below includes dotfiles, so the clean loop must too
  for sub in skills agents; do
    for entry in "$SRC/$sub"/*; do
      [ -e "$entry" ] || continue
      name="$(basename "$entry")"
      rm -rf -- "${DEST:?}/$sub/$name"
      REPLACED="${REPLACED:+$REPLACED, }$sub/$name"
    done
  done
  echo "--clean: replaced only what this repo ships: ${REPLACED:-nothing}"
fi

mkdir -p "$DEST/agents" "$DEST/skills"
cp -R "$SRC/agents/." "$DEST/agents/"
cp -R "$SRC/skills/." "$DEST/skills/"

# Report exactly what is on disk, so this message can't drift from the repo.
AGENTS=""; AGENT_COUNT=0
for f in "$SRC"/agents/*.md; do
  [ -f "$f" ] || continue
  name="$(basename "$f" .md)"
  AGENTS="${AGENTS:+$AGENTS, }$name"
  AGENT_COUNT=$((AGENT_COUNT + 1))
done

SKILLS=""; SKILL_COUNT=0
for d in "$SRC"/skills/*/; do
  [ -d "$d" ] || continue
  name="$(basename "$d")"
  SKILLS="${SKILLS:+$SKILLS, }$name"
  SKILL_COUNT=$((SKILL_COUNT + 1))
done

echo "WordPress Orchestrator framework installed into $DEST"
echo "Agents ($AGENT_COUNT): $AGENTS"
echo "Skills ($SKILL_COUNT): $SKILLS"
echo
if [ "$CLEAN" -eq 0 ]; then
  echo "NOTE: this is an overwrite-in-place install. Files with the same name are replaced,"
  echo "but files deleted upstream are NOT removed from $DEST — re-run with --clean to drop files"
  echo "retired inside a framework skill; a whole retired skill or agent has to be deleted by hand."
fi
