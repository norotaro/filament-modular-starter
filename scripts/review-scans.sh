#!/usr/bin/env bash
# The two mechanical review scans from AGENTS.md (Placement rules, Comment
# rules). They live in a script so a reviewer runs them as one allowlisted
# command: .claude/hooks/pre-pr-review-bash.sh refuses pipes, so the awk stage
# cannot ride along with an arbitrary command.
#
# Usage: scripts/review-scans.sh placement|comments [base-ref]   (default base: main)
set -euo pipefail

scan=${1:-}
base=${2:-main}
# A fresh clone has origin/main but no local main.
if [[ $# -lt 2 ]] && ! git rev-parse --verify --quiet main >/dev/null; then
  base=origin/main
fi

diff_against_base() {
  # This script's own regexes would match the comment scan.
  git diff "${base}...HEAD" -U0 -- ':(exclude)*.md' ':(exclude)scripts/review-scans.sh'
}

case "$scan" in
  placement)
    diff_against_base |
        awk '
          /^\+\+\+ b\// { p = substr($0, 7); m = (index(p, "app/Modules/") == 1); c = (m && index(p, "/Http/Controllers/") > 0); mo = (m && index(p, "/Models/") > 0); f = (c || mo || (m && index(p, "/Filament/") > 0)) ? p : "" }
          f && /^\+/ && !/^\+\+\+/ && !/^\+[ \t]*([*]|\/)/ && ( /Gate::|::forUser\(/ || (c && /(^|[^A-Za-z0-9_>])app\(/) ) { print f ": " $0 }' ;;
  comments)
    diff_against_base |
        awk '
          /^\+\+\+ b\// { f = substr($0, 7) }
          /^\+/ && !/^\+\+\+/ && !/TODO\(task/ && !/UNMOUNTED:/ &&
          /task [0-9]+\.[0-9]+|PR-[0-9]|PR #[0-9]|[Rr]ound-[0-9]|finding #[0-9A-Z][0-9A-Z-]*|session [0-9a-f]{8,}|in this batch/ {
            print f ": " $0
          }' ;;
  *)
    echo "usage: scripts/review-scans.sh placement|comments [base-ref]" >&2
    exit 64 ;;
esac
