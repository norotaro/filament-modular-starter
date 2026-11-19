#!/usr/bin/env bash
# Keeps the pre-PR reviewer agents read-only. Claude Code frontmatter takes tool
# names only, so per-command rules need this hook; .opencode/agents/*.md carry
# the same command list as permission globs. Keep both lists in sync.
#
# Allowlist by construction, not by parsing shell: a command may only contain
# the characters below, so quotes, escapes, comments, pipes, redirection,
# substitution and chaining cannot appear at all, and the words can be split on
# spaces safely. Each allowed command then gets its options checked, because
# git, pest and phpstan all have options that write files or run code.
#
# Deny, not ask: a hook's "ask" prompts even under bypass mode and stalls a
# background reviewer until a human answers; a denial with a reason lets the
# reviewer switch to git grep and Read on its own.
set -u

# Exit 2 blocks the tool call, so a missing jq fails closed instead of open.
command -v jq >/dev/null 2>&1 || {
  echo "pre-pr-review-bash.sh needs jq on PATH" >&2
  exit 2
}

# The sentinel keeps trailing newlines, which command substitution would strip.
cmd=$(jq -r '(.tool_input.command // "") + "#"')
cmd=${cmd%#}
problem=""

# Long options git accepts in abbreviated form that write files, run commands,
# or read outside the repository. A word is refused when it spells any prefix
# of one of these, because git expands unambiguous prefixes.
GIT_DANGEROUS_LONG="output open-files-in-pager ext-diff no-index textconv"

git_option_ok() {
  local sub=$1 word=$2 name danger
  case "$word" in
    --*)
      name=${word#--}
      name=${name%%=*}
      for danger in $GIT_DANGEROUS_LONG; do
        [[ $danger == "$name"* ]] && return 1
      done ;;
    -*)
      # git grep -O<cmd> runs a pager command, also inside grouped short options.
      [[ $sub == grep && $word == *O* ]] && return 1 ;;
  esac
  return 0
}

test_option_ok() {
  case "$1" in
    --compact | --parallel | --filter=* | --testsuite=*) return 0 ;;
    tests/*) [[ $1 != *..* ]] && return 0 ;;
  esac
  return 1
}

pint_option_ok() {
  case "$1" in
    --test | --diff=* | --dirty) return 0 ;;
    app/* | tests/* | config/* | routes/* | database/*) [[ $1 != *..* ]] && return 0 ;;
  esac
  return 1
}

phpstan_option_ok() {
  case "$1" in
    analyse | --no-progress | --memory-limit=* | --error-format=*) return 0 ;;
    app/* | tests/*) [[ $1 != *..* ]] && return 0 ;;
  esac
  return 1
}

check() {
  local word
  if [[ -z $cmd || ! $cmd =~ ^[A-Za-z0-9\ _./:@^~=,+%-]+$ ]]; then
    problem="characters outside letters, digits, spaces and _ . / : @ ^ ~ = , + % -"
    return 1
  fi

  local -a w
  read -r -a w <<<"$cmd"

  case "${w[0]} ${w[1]:-}" in
    "git status" | "git diff" | "git log" | "git show" | "git grep" | "git ls-files")
      for word in "${w[@]:2}"; do
        git_option_ok "${w[1]}" "$word" || { problem="git option $word"; return 1; }
      done ;;
    "vendor/bin/pest "* | "php artisan")
      local start=1
      if [[ ${w[0]} == php ]]; then
        [[ ${w[2]:-} == test ]] || { problem="php artisan other than test"; return 1; }
        start=3
      fi
      for word in "${w[@]:start}"; do
        test_option_ok "$word" || { problem="test option $word"; return 1; }
      done ;;
    "composer test")
      [[ ${#w[@]} -eq 2 ]] || { problem="arguments to composer test"; return 1; } ;;
    "vendor/bin/phpstan "*)
      for word in "${w[@]:1}"; do
        phpstan_option_ok "$word" || { problem="phpstan option $word"; return 1; }
      done ;;
    "vendor/bin/pint --test")
      # Without --test pint rewrites files.
      for word in "${w[@]:2}"; do
        pint_option_ok "$word" || { problem="pint option $word"; return 1; }
      done ;;
    "scripts/review-scans.sh placement" | "scripts/review-scans.sh comments")
      [[ ${#w[@]} -le 3 && ${w[2]:-x} != -* ]] || { problem="review-scans takes one base ref"; return 1; } ;;
    *)
      problem="a command outside the allowlist"
      return 1 ;;
  esac
}

if check; then
  decision=allow
  reason="pre-pr-review bash allowlist"
else
  decision=deny
  reason="Refused: ${problem}. Allowed: git status/diff/log/show/grep/ls-files, composer test, php artisan test or vendor/bin/pest (--compact, --parallel, --filter=, --testsuite=, tests/ paths), vendor/bin/phpstan analyse, vendor/bin/pint --test, and scripts/review-scans.sh placement|comments [base-ref]. No pipes, quotes or redirection: search with git grep, list files with git ls-files, read with the Read tool."
fi

jq -n --arg d "$decision" --arg r "$reason" '{hookSpecificOutput: {hookEventName: "PreToolUse", permissionDecision: $d, permissionDecisionReason: $r}}'
