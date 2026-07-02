#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/deploy-common.sh"

VERIFY_HTTP_STRICT="${VERIFY_HTTP_STRICT:-0}"
HTTP_PROBES=(
  "$HEALTHCHECK_URL"
  "${HEALTHCHECK_URL%/}/cms/login/index"
  "${HEALTHCHECK_URL%/}/index/index"
)

printf 'QSS checksum verify -> %s\n' "$REMOTE_ROOT"
DIFF_OUTPUT="$(rsync_cmd -rnc --dry-run --itemize-changes || true)"
if [ -n "$DIFF_OUTPUT" ]; then
  printf '%s\n' "$DIFF_OUTPUT"
else
  echo 'NO_DIFF'
fi

printf '\nHTTP probe (advisory)\n'
http_issue=0
for url in "${HTTP_PROBES[@]}"; do
  headers="$(curl --noproxy '*' -sS -D - -o /dev/null --max-redirs 0 "$url" || true)"
  status="$(printf '%s\n' "$headers" | awk 'toupper($1) ~ /^HTTP\// {code=$2} END {print code}')"
  location="$(printf '%s\n' "$headers" | awk 'tolower($1)=="location:" {print $2}' | tail -n 1 | tr -d '\r')"

  printf '%s -> %s' "$url" "${status:-curl_error}"
  if [ -n "$location" ]; then
    printf ' location=%s' "$location"
  fi
  printf '\n'

  if [ "${status:-}" = "301" ] && [ "$location" = "/" ]; then
    http_issue=1
  fi
done

if [ "$http_issue" -eq 1 ]; then
  echo 'WARN_HTTP_REDIRECT_LOOP'
  if [ "$VERIFY_HTTP_STRICT" = "1" ]; then
    exit 1
  fi
fi
