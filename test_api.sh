#!/bin/bash
# QSS API v1 测试脚本
# 用法: bash test_api.sh

BASE_URL="https://qss.qestsoln.com/api/v1"

# 从同目录的 keys.txt 读取凭证 (格式: 两行, 第一行 key, 第二行 secret)
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
KEYS_FILE="${SCRIPT_DIR}/keys.txt"

if [ ! -f "$KEYS_FILE" ]; then
  echo "❌ 找不到 keys.txt，请先创建该文件"
  echo "   内容格式:"
  echo '   qss_...'
  echo '   23d...'
  exit 1
fi

API_KEY="$(sed -n '1p' "$KEYS_FILE")"
API_SECRET="$(sed -n '2p' "$KEYS_FILE")"

echo "=========================================="
echo " QSS API v1 测试"
echo "=========================================="

# Step 1: 获取 Bearer Token
echo ""
echo "[1] 获取 Bearer Token..."
BASIC=$(echo -n "${API_KEY}:${API_SECRET}" | base64)
TOKEN=*** -s -X POST "${BASE_URL}/oauth/token" \
  -H "Authorization: Basic ${BASIC}" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials" | python3 -c "import sys,json;print(json.load(sys.stdin)['access_token'])")

if [ -z "$TOKEN" ]; then
  echo "    ❌ Token 获取失败"
  exit 1
fi
echo "    ✅ Token 获取成功 (有效期 30 分钟)"

# Step 2: 通过 serial_number 获取单个用户
echo ""
echo "[2] 获取单个用户 (serial_number=N1001)..."
echo "    GET ${BASE_URL}/registrants/N1001"
echo ""
curl -s "${BASE_URL}/registrants/N1001" \
  -H "Authorization: Bearer *** | python3 -m json.tool

echo ""
echo "[3] 获取单个用户 (带字段筛选, serial_number=N1002)..."
echo "    GET ${BASE_URL}/registrants/N1002?fields=id,event_name,login_name"
echo ""
curl -s "${BASE_URL}/registrants/N1002?fields=id,event_name,login_name" \
  -H "Authorization: Bearer *** | python3 -m json.tool

echo ""
echo "[4] 测试不存在的 serial_number..."
curl -s "${BASE_URL}/registrants/INVALID-999" \
  -H "Authorization: Bearer *** | python3 -m json.tool

echo ""
echo "=========================================="
echo " 测试完成"
echo "=========================================="
