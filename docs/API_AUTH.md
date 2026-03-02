# REST API Authentication (Postman)

Token-based authentication for the PAPeR REST API. Use Postman to test.

---

## 1. Run migration

Before using the API, create the `api_tokens` table:

```bash
php cli/migrate.php
```

---

## 2. Endpoints

Base URL (adjust for your environment):
- Local: `http://localhost/api`
- Example: `http://localhost/api/auth/login`

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST   | `/api/auth/login`  | No  | Login with username/password, returns token |
| GET    | `/api/auth/me`     | Yes | Current user info |
| POST   | `/api/auth/logout` | Yes | Revoke token (optional) |

---

## 3. Login

**Request**

- **Method:** `POST`
- **URL:** `http://localhost/api/auth/login`
- **Headers:** `Content-Type: application/json`
- **Body (raw JSON):**

```json
{
  "username": "your_username",
  "password": "your_password"
}
```

**Success (200)**

```json
{
  "token": "64-char-hex-string",
  "expires_at": "2026-04-02 12:00:00",
  "user": {
    "id": 1,
    "username": "admin",
    "display_name": "Administrator",
    "email": "admin@example.com"
  }
}
```

**Errors**

- `400` – Missing username or password
- `401` – Invalid credentials
- `403` – 2FA enabled (use web login)
- `429` – Too many attempts (15 min block)

---

## 4. Get current user (me)

**Request**

- **Method:** `GET`
- **URL:** `http://localhost/api/auth/me`
- **Headers:** `Authorization: Bearer <token>`

Replace `<token>` with the value from the login response.

**Success (200)**

```json
{
  "id": 1,
  "username": "admin",
  "display_name": "Administrator",
  "email": "admin@example.com",
  "role_name": "Administrator"
}
```

**Error (401)**

```json
{
  "error": "Unauthorized",
  "message": "Authentication required"
}
```

---

## 5. Logout (revoke token)

**Request**

- **Method:** `POST`
- **URL:** `http://localhost/api/auth/logout`
- **Headers:** `Authorization: Bearer <token>`

**Success (200)**

```json
{
  "message": "Logged out"
}
```

---

## 6. Postman setup

### 6.1 Login request

1. Create a new request.
2. Method: **POST**
3. URL: `http://localhost/api/auth/login` (or your base URL)
4. **Headers:**
   - `Content-Type` = `application/json`
5. **Body:** raw, JSON:
   ```json
   {
     "username": "admin",
     "password": "your_password"
   }
   ```
6. Send and copy the `token` from the response.

### 6.2 Using the token for other requests

**Option A – Manual**

For each protected request (e.g. `/api/auth/me`):

1. Add header: `Authorization` = `Bearer <paste-token-here>`

**Option B – Environment variable**

1. Create an environment (or use Postman variables).
2. Add variable: `api_token` = (paste token from login).
3. For protected requests, add header: `Authorization` = `Bearer {{api_token}}`
4. After login, use **Tests** tab to store the token:
   ```javascript
   if (pm.response.code === 200) {
     var json = pm.response.json();
     if (json.token) {
       pm.environment.set("api_token", json.token);
     }
   }
   ```

### 6.3 Example: me request

1. Method: **GET**
2. URL: `http://localhost/api/auth/me`
3. Headers: `Authorization` = `Bearer {{api_token}}`
4. Send.

---

## 7. Using the token for other API endpoints

The same Bearer token works for existing API endpoints, for example:

- `GET /api/dashboard` – Dashboard data
- `GET /api/projects` – Projects list
- `GET /api/profiles` – Profiles list
- etc.

Add the header: `Authorization: Bearer <your-token>`

---

## 8. Notes

- Tokens expire after 30 days. Re-login to get a new token.
- 2FA is not supported via API; use web login if 2FA is enabled.
- Tokens are hashed; the raw token is only returned at login. Store it securely on the client.
