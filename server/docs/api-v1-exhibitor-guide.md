# QSS REST API v1 — Developer Guide

This guide provides complete documentation for the QSS REST API v1, including authentication, endpoints, request/response formats, and code examples.

---

## Overview

The QSS REST API v1 allows exhibitors to programmatically access registrant data for their assigned events. The API follows RESTful conventions.

**Base URL:** `https://qss.qestsoln.com/api/v1`

---

## Authentication

### Step 1: Obtain Your API Credentials

Contact your QSS administrator to obtain your **API Key** and **API Secret**. These are long strings that look like:

```
api_key:    qss_6cfb54cbf59ec6a14f9553a3af989e70
api_secret: 23d4551385b837db667cf8e65108d51507d0f45a
```

### Step 2: Exchange Credentials for a Bearer Token

Before calling any data endpoint, you must exchange your API Key and Secret for a temporary Bearer Token (JWT). Tokens expire after **30 minutes**.

**Request:**

```http
POST /api/v1/oauth/token HTTP/1.1
Host: qss.qestsoln.com
Authorization: Basic base64(api_key:api_secret)
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials
```

**How to construct the Basic Auth header:**

Base64-encode the string `api_key:api_secret` (colon-separated):

```bash
echo -n "qss_6cfb54cbf59ec6a14f9553a3af989e70:23d4551385b837db667cf8e65108d51507d0f45a" | base64
```

**Response (200):**

```json
{
    "access_token": "eyJ0eX...JKV1",
    "expires_at": "2026-07-02 00:52:33",
    "type": "bearer"
}
```

**cURL Example:**

```bash
curl -X POST https://qss.qestsoln.com/api/v1/oauth/token \
  -H "Authorization: Basic cXNzXzZjZmI..." \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials"
```

### Step 3: Use the Bearer Token

Include the token in the `Authorization` header for all subsequent requests:

```http
Authorization: Bearer eyJ0eX...
```

> **Note:** Your event is automatically determined from your API credentials. There is no need to pass `event_id` in any request — the system knows which event belongs to your account.

---

## Endpoints

### Get a Single Registrant

Retrieve complete details for a specific registrant by their `unique_id`.

```http
GET /api/v1/registrants/{unique_id}
```

**Path Parameters:**

| Parameter | Description |
|-----------|-------------|
| `unique_id` | The registrant's unique identifier (GUID format) |

**Query Parameters:**

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `fields` | No | — | Comma-separated field names (see Available Fields below) |

**Response (200):**

```json
{
    "status": "success",
    "message": "OK",
    "data": {
        "id": 959,
        "unique_id": "DB63B506-3536-B771-018B-25F1748AF252",
        "event_id": "AB6E9834-5752-2A1A-2CF9-58391339841B",
        "event_name": "PSDay Malaysia",
        "login_name": "jerry.bi",
        "type": "",
        "status": 1,
        "checkin_status": 0,
        "checkin_time": null,
        "zone": null,
        "table_no": null,
        "enable_track": 1,
        "data_fields": {
            "email": "jerry@example.com",
            "first_name": "Jerry",
            "last_name": "Bi",
            "organisation": "QSS",
            "login_name": "jerry.bi"
        },
        "scan_records": []
    }
}
```

**Available Fields (for `fields` parameter):**

Standard fields: `id`, `unique_id`, `event_id`, `event_name`, `login_name`, `type`, `status`, `checkin_status`, `checkin_time`, `zone`, `table_no`, `enable_track`, `data_fields`, `scan_records`

Custom data fields: Any key from `data_fields` (e.g., `first_name`, `last_name`, `email`)

**Example with field filtering:**

```bash
curl "https://qss.qestsoln.com/api/v1/registrants/DB63B506-3536-B771-018B-25F1748AF252?fields=id,unique_id,event_name,first_name,last_name" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Response with filtering returns only requested fields:

```json
{
    "status": "success",
    "message": "OK",
    "data": {
        "id": 959,
        "unique_id": "DB63B506-3536-B771-018B-25F1748AF252",
        "event_name": "PSDay Malaysia",
        "first_name": "Jerry",
        "last_name": "Bi"
    }
}
```

**cURL Example (full response):**

```bash
curl https://qss.qestsoln.com/api/v1/registrants/DB63B506-3536-B771-018B-25F1748AF252 \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Error Responses

All errors use a consistent JSON format:

```json
{
    "name": "Error Name",
    "message": "Human-readable description",
    "code": 0,
    "status": 404
}
```

### HTTP Status Codes

| Status | Name | Description |
|--------|------|-------------|
| `400` | Bad Request | Missing required parameter (e.g., `unique_id`) |
| `401` | Unauthorized | Missing/invalid token, or invalid API credentials |
| `403` | Forbidden | No event associated with your account |
| `404` | Not Found | Registrant not found for the given `unique_id` |
| `405` | Method Not Allowed | Using a non-POST method on the OAuth endpoint |
| `429` | Too Many Requests | Rate limit exceeded (2,000 requests / 10 minutes) |
| `500` | Internal Server Error | Unexpected server error |

---

## Rate Limiting

Each exhibitor is limited to **2,000 requests per 10-minute window**. Rate limit information is included in response headers:

| Header | Description |
|--------|-------------|
| `X-Rate-Limit-Limit` | Maximum requests per window (2000) |
| `X-Rate-Limit-Remaining` | Remaining requests in current window |
| `X-Rate-Limit-Reset` | Seconds until the window resets |

When the limit is exceeded, the API returns `429 Too Many Requests`.

---

## Event Binding

Your API credentials are bound to a specific event at the time your account is created. You do not need to specify `event_id` in any API request — the system automatically determines your event from your Bearer Token. All data returned by the API belongs to your assigned event.

---

## CORS Support

The API supports Cross-Origin Resource Sharing (CORS) for browser-based applications:

- **Allowed Origins:** `*` (all origins)
- **Allowed Methods:** `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`
- **Allowed Headers:** `Authorization`, `Content-Type`, `Accept`, `Origin`, `X-Requested-With`

Preflight `OPTIONS` requests return `204 No Content`.

---

## Complete Code Examples

### PHP

```php
<?php
$apiKey = 'qss_6cfb54cbf59ec6a14f9553a3af989e70';
$apiSecret = '23d4551385b837db667cf8e65108d51507d0f45a';
$baseUrl = 'https://qss.qestsoln.com/api/v1';

// Step 1: Get Bearer Token
$ch = curl_init($baseUrl . '/oauth/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . base64_encode("$apiKey:$apiSecret"),
        'Content-Type: application/x-www-form-urlencoded',
    ],
    CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
]);
$resp = json_decode(curl_exec($ch), true);
curl_close($ch);

$token = $resp['access_token'];

// Step 2: Get registrant details (no event_id needed)
$uniqueId = 'DB63B506-3536-B771-018B-25F1748AF252';
$ch = curl_init($baseUrl . '/registrants/' . urlencode($uniqueId));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
    ],
]);
$resp = json_decode(curl_exec($ch), true);
curl_close($ch);

print_r($resp);
```

### Python

```python
import requests

API_KEY = 'qss_6cfb54cbf59ec6a14f9553a3af989e70'
API_SECRET = '23d4551385b837db667cf8e65108d51507d0f45a'
BASE_URL = 'https://qss.qestsoln.com/api/v1'

# Step 1: Get Bearer Token
resp = requests.post(f'{BASE_URL}/oauth/token',
    headers={'Authorization': f'Basic {requests.auth._basic_auth_str(API_KEY, API_SECRET)}'},
    data={'grant_type': 'client_credentials'})
token = resp.json()['access_token']

# Step 2: Get registrant details (no event_id needed)
unique_id = 'DB63B506-3536-B771-018B-25F1748AF252'
resp = requests.get(f'{BASE_URL}/registrants/{unique_id}',
    headers={'Authorization': f'Bearer {token}'})
registrant = resp.json()['data']

print(f"Name: {registrant['data_fields'].get('first_name', '')} {registrant['data_fields'].get('last_name', '')}")
print(f"Event: {registrant['event_name']}")
print(f"Check-in: {'Yes' if registrant['checkin_status'] else 'No'}")
```

### JavaScript (Node.js)

```javascript
const API_KEY = 'qss_6cfb54cbf59ec6a14f9553a3af989e70';
const API_SECRET = '23d4551385b837db667cf8e65108d51507d0f45a';
const BASE_URL = 'https://qss.qestsoln.com/api/v1';

// Step 1: Get Bearer Token
const authResp = await fetch(`${BASE_URL}/oauth/token`, {
    method: 'POST',
    headers: {
        'Authorization': `Basic ${Buffer.from(`${API_KEY}:${API_SECRET}`).toString('base64')}`,
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'grant_type=client_credentials',
});
const { access_token: token } = await authResp.json();

// Step 2: Get registrant details (no event_id needed)
const uniqueId = 'DB63B506-3536-B771-018B-25F1748AF252';
const detailResp = await fetch(
    `${BASE_URL}/registrants/${uniqueId}`,
    { headers: { 'Authorization': `Bearer ${token}` } }
);
const detail = await detailResp.json();
console.log(detail.data.data_fields);
```

### cURL (Quick Test)

```bash
# Set your credentials
API_KEY="qss_6cfb54cbf59ec6a14f9553a3af989e70"
API_SECRET="23d4551385b837db667cf8e65108d51507d0f45a"

# Step 1: Get token
BASIC=$(echo -n "${API_KEY}:${API_SECRET}" | base64)
TOKEN=$(curl -s -X POST https://qss.qestsoln.com/api/v1/oauth/token \
  -H "Authorization: Basic ${BASIC}" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials" | python3 -c "import sys,json;print(json.load(sys.stdin)['access_token'])")

# Step 2: Get registrant details (no event_id needed)
curl -s "https://qss.qestsoln.com/api/v1/registrants/DB63B506-3536-B771-018B-25F1748AF252" \
  -H "Authorization: Bearer ${TOKEN}" | python3 -m json.tool
```

---

## Quick Reference

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/v1/oauth/token` | POST | Basic | Exchange API key/secret for Bearer token |
| `/api/v1/registrants/{unique_id}` | GET | Bearer | Get single registrant details |

---

## FAQ

**Q: How long does the Bearer Token last?**
A: 30 minutes. After expiry, request a new token using the same OAuth endpoint.

**Q: Do I need to pass event_id in my requests?**
A: No. Your event is automatically determined from your API credentials. The system knows which event belongs to your account.

**Q: What happens if I exceed the rate limit?**
A: You'll receive a `429 Too Many Requests` response. Wait for the window to reset (check the `X-Rate-Limit-Reset` header for the remaining time in seconds).

**Q: What if my API credentials are compromised?**
A: Contact your QSS administrator immediately to revoke or regenerate your credentials.
