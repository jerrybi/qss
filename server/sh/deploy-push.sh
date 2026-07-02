#!/usr/bin/env bash
set -euo pipefail

source "$(dirname "$0")/deploy-common.sh"

printf 'QSS deploy -> %s\n' "$REMOTE_ROOT"
rsync_cmd -avc --omit-dir-times --no-perms --chmod=Du=rwx,Dg=rx,Do=rx,Fu=rw,Fg=r,Fo=r
