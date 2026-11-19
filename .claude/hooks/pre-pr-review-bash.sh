#!/usr/bin/env bash
# Mirrors the command list of .opencode/agents/*.md: Claude Code frontmatter
# takes tool names only, so per-command rules need this hook. Keep both command
# lists in sync.
# The scans published in AGENTS.md pipe git diff into awk, so a pipeline into
# read-only filters is allowed; chaining, redirection and substitution are not.
# Deny, not ask: a hook's "ask" prompts even under bypass mode and stalls a
# background reviewer until a human answers; a denial with a reason lets the
# reviewer switch to git grep and Read on its own.
set -u

# Exit 2 blocks the tool call, so a missing jq fails closed instead of open.
command -v jq >/dev/null 2>&1 || {
  echo "pre-pr-review-bash.sh needs jq on PATH" >&2
  exit 2
}

cmd=$(jq -r '.tool_input.command // ""')

deny_reason=""

# Splits $cmd into pipeline segments, honoring quotes. A prefix check alone would
# let "git diff; rm -rf ." through, so anything that chains, redirects or
# substitutes outside single quotes is refused before the segments are matched.
segments=()
check_syntax() {
  local i ch next prev="" quote="" seg=""
  for ((i = 0; i < ${#cmd}; i++)); do
    ch=${cmd:i:1}
    next=${cmd:i+1:1}
    if [[ $quote == "'" ]]; then
      [[ $ch == "'" ]] && quote=""
      seg+=$ch
      continue
    fi
    if [[ $quote == '"' ]]; then
      if [[ $ch == '`' || ( $ch == '$' && $next == '(' ) ]]; then
        deny_reason="command substitution"; return 1
      fi
      [[ $ch == '"' ]] && quote=""
      seg+=$ch
      continue
    fi
    case $ch in
      "'" | '"') quote=$ch; seg+=$ch ;;
      ';' | '&' | '>' | '<' | '`') deny_reason="'$ch' outside quotes"; return 1 ;;
      '$')
        if [[ $next == '(' ]]; then deny_reason="command substitution"; return 1; fi
        seg+=$ch ;;
      '|')
        if [[ $next == '|' ]]; then deny_reason="'||' outside quotes"; return 1; fi
        segments+=("$seg"); seg="" ;;
      $'\n')
        # A pipeline may continue on the next line only after '|' or '\'.
        if [[ $prev != '|' && $prev != '\' ]]; then deny_reason="a second command on a new line"; return 1; fi
        seg+=' ' ;;
      *) seg+=$ch ;;
    esac
    [[ $ch != ' ' && $ch != $'\t' ]] && prev=$ch
  done
  if [[ -n $quote ]]; then deny_reason="an unclosed quote"; return 1; fi
  segments+=("$seg")
}

trim() { local s=$1; s=${s#"${s%%[![:space:]]*}"}; printf '%s' "${s%"${s##*[![:space:]]}"}"; }

check_segments() {
  local first=1 seg
  for seg in "${segments[@]}"; do
    seg=$(trim "$seg")
    if ((first)); then
      first=0
      case "$seg" in
        "git status"* | "git diff"* | "git log"* | "git show"* | "git grep"* | "git ls-files"* | \
        "composer test"* | "php artisan test"* | "composer lint"* | "vendor/bin/pest"* | "vendor/bin/phpstan"*) ;;
        *) deny_reason="a command outside the allowlist"; return 1 ;;
      esac
      # git can write files (--output) or run a pager command (-O).
      case " $seg" in
        *" --output"* | *" -O"* | *" --open-files-in-pager"* | *" --ext-diff"*) deny_reason="a git option that writes or runs commands"; return 1 ;;
      esac
    else
      case "$seg" in
        grep* | sort* | uniq* | wc* | head* | tail* | cut* | tr*) ;;
        awk*)
          case "$seg" in
            *system* | *getline* | *'print >'* | *'printf >'* | *'| "'* | *'|"'*) deny_reason="an awk program that runs commands or writes files"; return 1 ;;
          esac ;;
        *) deny_reason="a pipe into something other than grep, awk, sort, uniq, wc, head, tail, cut or tr"; return 1 ;;
      esac
    fi
  done
}

if check_syntax && check_segments; then
  decision=allow
  reason="pre-pr-review bash allowlist"
else
  decision=deny
  reason="Refused: ${deny_reason}. Allowed: one git status/diff/log/show/grep/ls-files, composer test/lint, php artisan test, vendor/bin/pest or vendor/bin/phpstan command, optionally piped into grep, awk, sort, uniq, wc, head, tail, cut or tr. Search with git grep, list files with git ls-files, read with the Read tool."
fi

jq -n --arg d "$decision" --arg r "$reason" '{hookSpecificOutput: {hookEventName: "PreToolUse", permissionDecision: $d, permissionDecisionReason: $r}}'
