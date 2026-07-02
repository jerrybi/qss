#!/usr/bin/env python3
"""QSS API v1 测试脚本 — 用法: python3 test_api.py"""

import base64, json, subprocess, sys

BASE_URL = "https://qss.qestsoln.com/api/v1"

# 读取凭证
try:
    with open("keys.txt") as f:
        lines = f.read().strip().split("\n")
        API_KEY = lines[0].strip()
        API_SECRET = lines[1].strip()
except FileNotFoundError:
    print("找不到 keys.txt，请先创建")
    sys.exit(1)

print("=" * 42)
print(" QSS API v1 测试")
print("=" * 42)

# Step 1: 获取 Bearer Token
print("\n[1] 获取 Bearer Token...")
basic = base64.b64encode(f"{API_KEY}:{API_SECRET}".encode()).decode()
r = subprocess.run([
    "curl", "-s", "-X", "POST", f"{BASE_URL}/oauth/token",
    "-H", f"Authorization: Basic {basic}",
    "-H", "Content-Type: application/x-www-form-urlencoded",
    "-d", "grant_type=client_credentials"
], capture_output=True, text=True)
resp = json.loads(r.stdout)
token = resp.get("access_token", "")
if not token:
    print("    Token 获取失败:", r.stdout)
    sys.exit(1)
print("    Token 获取成功 (有效期 30 分钟)")

# Step 2: 获取单个用户 N1001
print("\n[2] 获取单个用户 (serial_number=N1001)...")
r = subprocess.run([
    "curl", "-s", f"{BASE_URL}/registrants/N1001",
    "-H", f"Authorization: Bearer {token}"
], capture_output=True, text=True)
print(json.dumps(json.loads(r.stdout), indent=2, ensure_ascii=False))

# Step 3: 字段筛选 N1002
print("\n[3] 获取单个用户 (带字段筛选, serial_number=N1002)...")
r = subprocess.run([
    "curl", "-s", f"{BASE_URL}/registrants/N1002?fields=id,event_name,login_name",
    "-H", f"Authorization: Bearer {token}"
], capture_output=True, text=True)
print(json.dumps(json.loads(r.stdout), indent=2, ensure_ascii=False))

# Step 4: 不存在的 serial_number
print("\n[4] 测试不存在的 serial_number...")
r = subprocess.run([
    "curl", "-s", f"{BASE_URL}/registrants/INVALID-999",
    "-H", f"Authorization: Bearer {token}"
], capture_output=True, text=True)
print(json.dumps(json.loads(r.stdout), indent=2, ensure_ascii=False))

print("\n" + "=" * 42)
print(" 测试完成")
print("=" * 42)
