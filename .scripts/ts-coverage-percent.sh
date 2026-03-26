#!/usr/bin/env sh
set -eu

# REQ-TEST-009 contract:
# - Makefile target `test-ts` (or equivalent TS coverage target) must run:
#   pnpm run test:coverage | tee coverage-ts.txt
#   ./.scripts/ts-coverage-percent.sh coverage-ts.txt
# - .gitignore must include /coverage-ts.txt

RAW_FILE="${1:-coverage-ts.txt}"

if [ ! -f "$RAW_FILE" ]; then
  echo "ERROR: coverage output file not found: $RAW_FILE" >&2
  exit 1
fi

# Strip ANSI escape sequences, parse coverage summary, and return a conservative
# global percentage: min(Statements, Branches, Functions, Lines).
VALUE="$(
  sed 's/\x1B\[[0-9;]*[A-Za-z]//g' "$RAW_FILE" \
    | awk '
      /^[[:space:]]*Statements[[:space:]]*:/ {
        for (i=1; i<=NF; i++) if ($i ~ /^[0-9]+(\.[0-9]+)?%$/) { gsub(/%/, "", $i); statements=$i; break }
      }
      /^[[:space:]]*Branches[[:space:]]*:/ {
        for (i=1; i<=NF; i++) if ($i ~ /^[0-9]+(\.[0-9]+)?%$/) { gsub(/%/, "", $i); branches=$i; break }
      }
      /^[[:space:]]*Functions[[:space:]]*:/ {
        for (i=1; i<=NF; i++) if ($i ~ /^[0-9]+(\.[0-9]+)?%$/) { gsub(/%/, "", $i); functions=$i; break }
      }
      /^[[:space:]]*Lines[[:space:]]*:/ {
        for (i=1; i<=NF; i++) if ($i ~ /^[0-9]+(\.[0-9]+)?%$/) { gsub(/%/, "", $i); lines=$i; break }
      }
      END {
        if (statements=="" || branches=="" || functions=="" || lines=="") exit 1
        min=statements+0
        b=branches+0
        f=functions+0
        l=lines+0
        if (b < min) min=b
        if (f < min) min=f
        if (l < min) min=l
        printf "%.2f", min
      }
    ' || true
)"

if [ -z "${VALUE:-}" ]; then
  echo "ERROR: Could not extract TS coverage summary from ${RAW_FILE}" >&2
  exit 1
fi

if [ -t 1 ]; then
  RED="$(printf '\033[31m')"
  ORANGE="$(printf '\033[38;5;208m')"
  GREEN="$(printf '\033[32m')"
  RESET="$(printf '\033[0m')"
else
  RED=""
  ORANGE=""
  GREEN=""
  RESET=""
fi

COLOR="$GREEN"
if awk "BEGIN { exit !(${VALUE} < 50) }"; then
  COLOR="$RED"
elif awk "BEGIN { exit !(${VALUE} <= 85) }"; then
  COLOR="$ORANGE"
fi

printf 'Global TS coverage (min of Statements/Branches/Functions/Lines): %s%s%%%s\n' "$COLOR" "$VALUE" "$RESET"
