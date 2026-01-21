#!/usr/bin/env bash
set -euo pipefail

# --- Helpers ---
die() { echo "Erreur: $*" >&2; exit 1; }

# --- Ensure git repo ---
git rev-parse --is-inside-work-tree >/dev/null 2>&1 || die "Pas un dépôt Git ici."

branch="$(git rev-parse --abbrev-ref HEAD)"
remote="${1:-origin}"

# --- Show status ---
echo "Branche: ${branch}"
echo "Remote : ${remote}"
echo

# --- Stage changes ---
git add -A

# --- If nothing to commit, just sync ---
if git diff --cached --quiet; then
  echo "Aucun changement à committer."
else
  # Commit message from arg or prompt
  msg="${2:-}"
  if [[ -z "${msg}" ]]; then
    read -r -p "Message de commit: " msg
  fi
  [[ -n "${msg}" ]] || die "Message de commit vide."

  git commit -m "${msg}"
fi

# --- Sync (pull rebase + push) ---
echo
echo "Synchronisation avec ${remote}/${branch}..."
git pull --rebase "${remote}" "${branch}"
git push "${remote}" "${branch}"

echo "OK: dépôt local synchronisé avec GitHub."
