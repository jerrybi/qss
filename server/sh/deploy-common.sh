#!/usr/bin/env bash
set -euo pipefail

# QSS Server 部署通用配置
# 沿用 QSR/TIMP 的 rsync 部署体系，同一台服务器 103.7.8.165

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# 必须用 Homebrew rsync，系统自带 openrsync 与远端 rrsync 不兼容
if command -v /opt/homebrew/bin/rsync &>/dev/null; then
  PATH="/opt/homebrew/bin:$PATH"
fi

DEPLOY_HOST="${DEPLOY_HOST:-103.7.8.165}"
DEPLOY_PORT="${DEPLOY_PORT:-8288}"
DEPLOY_USER="${DEPLOY_USER:-vhost-deploy}"
DEPLOY_KEY="${DEPLOY_KEY:-$HOME/.ssh/qss_deploy_ed25519}"
REMOTE_ROOT="${REMOTE_ROOT:-/var/www/vhosts/qestsoln.com/qss.qestsoln.com}"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-https://qss.qestsoln.com/}"

SSH_OPTS=(
  -p "$DEPLOY_PORT"
  -i "$DEPLOY_KEY"
  -o BatchMode=yes
  -o StrictHostKeyChecking=accept-new
  -o ConnectTimeout=10
)

# rsync 过滤规则 — 与 QSR/TIMP 一致
# Include: application/, public/, route/
# Exclude: config/, vendor/, thinkphp/, runtime/, .env, .git, upload/temp
RSYNC_FILTERS=(
  --exclude='/.env.example'
  --include='/application/***'
  --exclude='/config/***'
  --exclude='/extend/***'
  --exclude='/public/upload/***'
  --exclude='/public/upload'
  --exclude='/public/temp/***'
  --include='/public/***'
  --include='/route/***'
  --exclude='/think'
  --exclude='/thinkphp/***'
  --exclude='/vendor/***'
  --exclude='/.env'
  --exclude='/.env.*'
  --exclude='/database/***'
  --exclude='/log/***'
  --exclude='/runtime/***'
  --exclude='/.git/***'
  --exclude='*'
)

rsync_target() {
  printf '%s@%s:./' "$DEPLOY_USER" "$DEPLOY_HOST"
}

rsync_cmd() {
  rsync "$@" \
    "${RSYNC_FILTERS[@]}" \
    -e "ssh ${SSH_OPTS[*]}" \
    "$PROJECT_ROOT/" \
    "$(rsync_target)"
}
