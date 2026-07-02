#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/deploy-common.sh"

printf 'QSS dry-run -> %s\n' "$REMOTE_ROOT"
rsync_cmd -av --dry-run --itemize-changes
