# QSS API Key Management Guide (For Administrators)

This guide explains how to generate, regenerate, and revoke API credentials for exhibitors from the QSS CMS admin panel.

---

## Overview

Each exhibitor who needs API access requires a pair of credentials:

- **API Key** — A public identifier (format: `qss_` + 32 hex characters). Used as the username in Basic Authentication.
- **API Secret** — A 40-character hex string. Used as the password in Basic Authentication.

These credentials allow exhibitors to obtain Bearer Tokens for accessing the QSS REST API v1.

---

## Where to Manage API Keys

### Method 1: From the Exhibitor List Page

1. Log in to the CMS admin panel at `https://qss.qestsoln.com/cms/login/index`
2. Navigate to **Exhibitors** from the main menu
3. Each row in the exhibitor list has a button group in the **Operation** column
4. Click the **🔑 key icon** button (tooltip: "Generate API Key")

   ![Button location: key icon in the action button group]

5. A confirmation dialog will appear:
   - Title: **Generate API Key**
   - Message: `Generate API key for exhibitor "<login_name>" (ID: <id>)?`
   - Click **Confirm** to proceed

6. After successful generation, a popup window will display:
   - **api_key** (blue text on gray background)
   - **api_secret** (red text on gray background)
   - A **Copy & Close** button to copy both values to your clipboard

7. Share these credentials with the exhibitor through a secure channel.

### Method 2: From the Exhibitor Edit Page

1. Navigate to **Exhibitors** and click the **Edit** button (pencil icon) on any exhibitor
2. Scroll down to the **API Key** and **API Secret** fields
3. If credentials have been previously generated, they will be displayed here
4. Click **Generate / Regenerate** to create new credentials
5. Click **Copy** to copy both values to clipboard

---

## How to Regenerate API Keys

Regenerating creates a completely new key pair. The old credentials stop working immediately.

1. Open the exhibitor's edit page (or use the key icon from the list)
2. Click **Generate / Regenerate**
3. Confirm the action in the dialog
4. The new `api_key` and `api_secret` will be displayed
5. The old credentials are now invalid — any exhibitor still using them will receive a `401 Unauthorized` error

> **When to regenerate:** If an exhibitor's API secret has been compromised or shared with unauthorized parties, regenerate immediately to invalidate the old credentials.

---

## How to Revoke API Keys

Revoking permanently removes the API credentials. The exhibitor will no longer be able to access the API.

1. Open the exhibitor's edit page
2. Click the **Revoke** button (red, with trash icon — only visible when API key exists)
3. Confirm the action in the dialog
4. The API Key and API Secret fields will be cleared
5. All future API requests from this exhibitor will return `401 Unauthorized`

> **Difference between Revoke and Regenerate:** Revoke removes credentials entirely (no API access). Regenerate replaces old credentials with new ones (API access continues with new keys).

---

## Important Notes

- **Credentials are stored in plaintext** in the database for easy viewing from the admin panel.
- **No email notification** is sent automatically — you must manually share credentials with the exhibitor.
- **Rate limiting** is enforced at 2,000 requests per 10-minute window per exhibitor.
- **Token expiry** is 30 minutes — exhibitors must re-authenticate after the token expires.
- **Data isolation** — each exhibitor can only access data within their own event. They cannot query data from other events even with valid credentials.

---

## Troubleshooting

| Issue | Cause | Solution |
|-------|-------|----------|
| Exhibitor gets `401 Unauthorized` on OAuth | Wrong api_key/api_secret, or credentials revoked | Verify credentials in the edit page; regenerate if needed |
| Exhibitor gets `403 Forbidden` on data requests | Requesting data from an event_id that doesn't match their account | Confirm the exhibitor's assigned event in the edit page |
| No API Key button visible | Exhibitor may be disabled (status ≠ 1) | Enable the exhibitor first |
