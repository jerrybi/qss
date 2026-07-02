#!/bin/bash
API_KEY="qss_...SECRET="23d4...f45a"
BASIC=$(echo -n "${API_KEY}:${API_SECRET}" | base64)
TOKEN=$(curl -s -X POST https://qss.qestsoln.com/api/v1/oauth/token \
  -H "Authorization: Basic ${BASIC}" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials" | python3 -c "import sys,json;print(json.load(sys.stdin)['access_token'])")

echo "Token obtained: ${TOKEN:0:20}..."
echo ""

echo "=== Test 1: Get single registrant WITHOUT event_id ==="
curl -s "https://qss.qestsoln.com/api/v1/registrants/DB63B506-3536-B771-018B-25F1748AF252" \
  -H "Authorization: Bearer ${TOKEN}" | python3 -m json.tool

echo ""
echo "=== Test 2: Get single registrant with fields filter, NO event_id ==="
curl -s "https://qss.qestsoln.com/api/v1/registrants/DB63B506-3536-B771-018B-25F1748AF252?fields=id,unique_id,event_name,login_name" \
  -H "Authorization: Bearer ${TOKEN}" | python3 -m json.tool

echo ""
echo "=== Test 3: List registrants WITHOUT event_id ==="
curl -s "https://qss.qestsoln.com/api/v1/registrants?page=1&per-page=3" \
  -H "Authorization: Bearer ${TOKEN}" | python3 -m json.tool

echo ""
echo "=== Test 4: Invalid token ==="
curl -s "https://qss.qestsoln.com/api/v1/registrants/DB63B506-3536-B771-018B-25F1748AF252" \
  -H "Authorization: Bearer invalid_token_12345" | python3 -m json.tool

echo ""
echo "=== Test 5: Not found registrant ==="
curl -s "https://qss.qestsoln.com/api/v1/registrants/INVALID-ID-12345" \
  -H "Authorization: Bearer ${TOKEN}" | python3 -m json.tool
