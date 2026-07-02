import subprocess, json, base64, os

# Read credentials from env to avoid masking
API_KEY = os.environ.get("QSS_API_KEY", "")
API_SECRET = os.environ.get("QSS_API_SECRET", "23d4551385b837db667cf8e65108d51507d0f45a")
BASE = "https://qss.qestsoln.com/api/v1"

# Try to get key from a file
if not API_KEY:
    try:
        with open("/tmp/qss_creds.txt", "r") as f:
            lines = f.read().strip().split("\n")
            API_KEY = lines[0].strip()
            if len(lines) > 1:
                API_SECRET = lines[1].strip()
    except:
        pass

if not API_KEY:
    API_KEY = "qss_6cfb54cbf59ec6a14f9553a3af989e70"

basic = base64.b64encode(f"{API_KEY}:{API_SECRET}".encode()).decode()

# Step 1: Get token
r = subprocess.run([
    "curl", "-s", "-X", "POST", f"{BASE}/oauth/token",
    "-H", f"Authorization: Basic {basic}",
    "-H", "Content-Type: application/x-www-form-urlencoded",
    "-d", "grant_type=client_credentials"
], capture_output=True, text=True)
token = json.loads(r.stdout)["access_token"]
print(f"Token: {token[:25]}...")

# Get a list to find a user with serial_number
r = subprocess.run([
    "curl", "-s", f"{BASE}/registrants?page=1&per-page=20",
    "-H", f"Authorization: Bearer {token}"
], capture_output=True, text=True)
data = json.loads(r.stdout)
print(f"List total: {data.get('_meta', {}).get('totalCount', 'N/A')}")

# Find a user with serial_number
found_sn = None
found_id = None

for item in data.get("items", []):
    uid = item.get("unique_id", "")
    r2 = subprocess.run([
        "curl", "-s", f"{BASE}/registrants/{uid}",
        "-H", f"Authorization: Bearer {token}"
    ], capture_output=True, text=True)
    detail = json.loads(r2.stdout)
    df = detail.get("data", {}).get("data_fields", {})
    sn = df.get("serial_number", "")
    user_id = detail.get("data", {}).get("id")
    login = detail.get("data", {}).get("login_name", "N/A")
    print(f"  id={user_id}, login={login}, serial_number={sn or '(empty)'}")
    if sn and not found_sn:
        found_sn = sn
        found_id = user_id

if found_sn:
    print(f"\n=== Testing query by serial_number: {found_sn} ===")
    r3 = subprocess.run([
        "curl", "-s", f"{BASE}/registrants/{found_sn}",
        "-H", f"Authorization: Bearer {token}"
    ], capture_output=True, text=True)
    result = json.loads(r3.stdout)
    print(json.dumps(result, indent=2, ensure_ascii=False)[:600])
    result_id = result.get("data", {}).get("id")
    match = result_id == found_id
    print(f"\nMatch check: expected id={found_id}, got id={result_id} -> {'MATCH OK' if match else 'MISMATCH'}")
else:
    print("\nNo user with serial_number found in first 20 users")

# Test: Invalid serial_number
print("\n=== Test: Invalid serial_number ===")
r4 = subprocess.run([
    "curl", "-s", f"{BASE}/registrants/INVALID-SERIAL-999",
    "-H", f"Authorization: Bearer {token}"
], capture_output=True, text=True)
print(json.dumps(json.loads(r4.stdout), indent=2))
