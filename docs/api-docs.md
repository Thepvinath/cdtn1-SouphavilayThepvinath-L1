# Smart CRM – Mekong Mobile · API Documentation (BT3)

**Flow:** L1 – Create, search and merge duplicate customer profiles · **Track:** SE  
**Author:** Thepvinath Souphavilay (237480201IS03) · **Version:** tag `v1.0`

This document describes **every endpoint that exists in `src/public/index.php`** (17 method + path combinations). All request/response examples were **copied from a real run** of this version on 13/11/2026 (fresh database created by `database/migrate.php` + `database/seed_bt2.php`). CSRF tokens are replaced by `<csrf_token>`.

## 1. Conventions

| Item | Value |
|---|---|
| Base URL (Docker) | `http://localhost:8080/api` (nginx `web` service proxies `/api` to the `api` service) |
| Base URL (Windows/XAMPP, README section 3) | `http://127.0.0.1:8000/api` |
| Format | JSON request and response bodies, `Content-Type: application/json; charset=utf-8` |
| Authentication | PHP session cookie (`PHPSESSID`, `HttpOnly`, `SameSite=Strict`) set by `POST /api/auth/login`. Send the cookie on every later request. |
| CSRF | Every `POST`, `PUT`, `DELETE` must send header `X-CSRF-Token: <token from GET /api/auth/csrf>` (the token belongs to the current session). |
| Roles | `SALES_STAFF`, `STORE_MANAGER`, `MARKETING` (table `roles`). |
| Messages | Error/success `message` texts are Vietnamese without diacritics (e.g. `"Vui long dang nhap"`). Clients should rely on the HTTP status and `error` code. |
| Phone privacy | Every customer response masks the phone number: first 2 and last 2 digits of the normalized number, e.g. `09******67` (BR5/NFR3). `phone_normalized` is never returned. |

### 1.1. Common error codes

| HTTP | `error` | When |
|---:|---|---|
| 400 | `VALIDATION_ERROR` | Invalid JSON or invalid field value; `message` names the problem |
| 401 | `UNAUTHORIZED` | No valid session (not logged in, or the account was disabled) |
| 403 | `FORBIDDEN` | Logged in, but the role is not allowed |
| 403 | `CSRF_INVALID` | Missing or wrong `X-CSRF-Token` on POST/PUT/DELETE |
| 404 | `NOT_FOUND` / `CUSTOMER_NOT_FOUND` / `USER_NOT_FOUND` | Unknown route or record |
| 405 | `METHOD_NOT_ALLOWED` | Method not supported on that path (response has an `Allow` header) |
| 409 | `BUSINESS_ERROR` | A business rule refused the request (duplicate phone, invalid merge, last manager, ...) |
| 500 | `INTERNAL_SERVER_ERROR` | Unexpected error. Body is always `{"error":"INTERNAL_SERVER_ERROR","message":"He thong gap loi"}` – no stack trace, SQL or connection string |

Example – unknown route and unsupported method (real output):

```http
GET /api/unknown            -> 404 {"error":"NOT_FOUND","message":"API khong ton tai"}
PATCH /api/customers        -> 405 {"error":"METHOD_NOT_ALLOWED","message":"Chi ho tro GET va POST"}
```

### 1.2. Endpoint list

| # | Method | Path | Login | Roles | CSRF |
|---:|---|---|---|---|---|
| 1 | GET | `/api/health` | No | – | No |
| 2 | GET | `/api/auth/csrf` | No | – | No |
| 3 | POST | `/api/auth/login` | No | – | Yes |
| 4 | GET | `/api/auth/me` | Yes | any | No |
| 5 | POST | `/api/auth/logout` | Yes | any | Yes |
| 6 | GET | `/api/customers` | Yes | any | No |
| 7 | POST | `/api/customers` | Yes | SALES_STAFF, STORE_MANAGER | Yes |
| 8 | GET | `/api/customers/{id}` | Yes | any | No |
| 9 | PUT | `/api/customers/{id}` | Yes | SALES_STAFF, STORE_MANAGER | Yes |
| 10 | DELETE | `/api/customers/{id}` | Yes | SALES_STAFF, STORE_MANAGER | Yes |
| 11 | GET | `/api/customers/duplicates` | Yes | any | No |
| 12 | POST | `/api/customers/merge` | Yes | SALES_STAFF, STORE_MANAGER | Yes |
| 13 | GET | `/api/users` | Yes | STORE_MANAGER | No |
| 14 | POST | `/api/users` | Yes | STORE_MANAGER | Yes |
| 15 | GET | `/api/users/{id}` | Yes | STORE_MANAGER | No |
| 16 | PUT | `/api/users/{id}` | Yes | STORE_MANAGER | Yes |
| 17 | DELETE | `/api/users/{id}` | Yes | STORE_MANAGER | Yes |

`{id}` is a positive integer (`[1-9][0-9]*`); any other value gives 404 `NOT_FOUND`.

---

## 2. System and authentication

### 2.1. `GET /api/health`
1. **Method and path:** `GET /api/health`
2. **Business description:** Liveness check used by the Docker healthcheck and by the Deployment Guide "Verify it works" step. Runs `SELECT 1` on the database.
3. **Authentication:** none.
4. **Parameters / body:** none.
5. **Success response:** `200 {"status":"ok","database":"ok"}`
6. **Errors:**

   | HTTP | Body | When |
   |---:|---|---|
   | 503 | `{"status":"error","database":"unavailable"}` | MySQL unreachable (details only in the server log) |
   | 405 | `METHOD_NOT_ALLOWED` | Any method other than GET |
7. **Notes / limits:** never returns connection details; used by the `api` and `web` container healthchecks.

```http
GET /api/health
HTTP 200
{"status":"ok","database":"ok"}
```

### 2.2. `GET /api/auth/csrf`
1. **Method and path:** `GET /api/auth/csrf`
2. **Business description:** Returns the CSRF token of the current session; the client sends it as `X-CSRF-Token` on every POST/PUT/DELETE.
3. **Authentication:** none (creates the session cookie if needed).
4. **Parameters / body:** none.
5. **Success response:** `200 {"data":{"csrf_token":"<64 hex chars>"}}`
6. **Errors:** 405 for other methods.
7. **Notes / limits:** one token per session; it stays valid after login.

```http
GET /api/auth/csrf
HTTP 200
{"data":{"csrf_token":"<csrf_token>"}}
```

### 2.3. `POST /api/auth/login`
1. **Method and path:** `POST /api/auth/login`
2. **Business description:** Logs an employee in with email and password (bcrypt hash in `users.password_hash`). Disabled accounts cannot log in. The session id is regenerated on success.
3. **Authentication:** none; **CSRF required**.
4. **Body:** `{"email": string, "password": string}`
5. **Success response:** `200 {"message":"Dang nhap thanh cong","data":{user}}` – `user` = `user_id, full_name, email, role_id, role_name` (never the password hash).
6. **Errors:**

   | HTTP | `error` / message | When |
   |---:|---|---|
   | 400 | `VALIDATION_ERROR` "Du lieu JSON khong hop le" / "Email va mat khau khong hop le" | Body is not JSON / fields are not strings |
   | 401 | `UNAUTHORIZED` "Email hoac mat khau khong dung" | Wrong email/password or disabled account (same message, no account enumeration) |
   | 403 | `CSRF_INVALID` | Missing/wrong CSRF token |
7. **Notes / limits:** no rate limiting / lockout yet (see `docs/checklist-bao-mat.md`). Demo accounts: `demo.sales@smartcrm.local`, `demo.manager@smartcrm.local` (passwords from `.env`, see Deployment Guide).

```http
POST /api/auth/login
X-CSRF-Token: <csrf_token>
{"email":"demo.sales@smartcrm.local","password":"Demo-Sales-2026"}

HTTP 200
{"message":"Dang nhap thanh cong","data":{"user_id":1,"full_name":"Local Demo Sales","email":"demo.sales@smartcrm.local","role_id":1,"role_name":"SALES_STAFF"}}
```
```http
POST /api/auth/login        {"email":"demo.sales@smartcrm.local","password":"wrong-password"}
HTTP 401 {"error":"UNAUTHORIZED","message":"Email hoac mat khau khong dung"}

POST /api/auth/login        (no X-CSRF-Token header)
HTTP 403 {"error":"CSRF_INVALID","message":"CSRF token khong hop le"}

POST /api/auth/login        {"email":1,"password":2}
HTTP 400 {"error":"VALIDATION_ERROR","message":"Email va mat khau khong hop le"}
```

### 2.4. `GET /api/auth/me`
1. **Method and path:** `GET /api/auth/me`
2. **Business description:** Returns the logged-in employee; the frontend calls it on start-up to restore the session.
3. **Authentication:** login required (any role).
4. **Parameters / body:** none.
5. **Success response:** `200 {"data":{user}}` – re-read from the database on every request, so a role change or disabling takes effect immediately.
6. **Errors:** 401 `UNAUTHORIZED` "Vui long dang nhap" (no session, or account disabled).
7. **Notes / limits:** none.

```http
GET /api/auth/me
HTTP 200
{"data":{"user_id":1,"full_name":"Local Demo Sales","email":"demo.sales@smartcrm.local","role_id":1,"role_name":"SALES_STAFF"}}
```
```http
GET /api/auth/me            (no session)
HTTP 401 {"error":"UNAUTHORIZED","message":"Vui long dang nhap"}
```

### 2.5. `POST /api/auth/logout`
1. **Method and path:** `POST /api/auth/logout`
2. **Business description:** Ends the employee session.
3. **Authentication:** login required; **CSRF required**.
4. **Parameters / body:** none.
5. **Success response:** `200 {"message":"Dang xuat thanh cong"}`
6. **Errors:** 401 (not logged in), 403 `CSRF_INVALID`.
7. **Notes / limits:** after logout `GET /api/auth/me` returns 401 (verified).

```http
POST /api/auth/logout
X-CSRF-Token: <csrf_token>
HTTP 200
{"message":"Dang xuat thanh cong"}
```

---

## 3. Customers (FR1–FR5)

Customer object returned by the API:

| Field | Type | Meaning |
|---|---|---|
| `customer_id` | int | Primary key |
| `full_name` | string | 1–150 characters |
| `phone` | string | **Masked** normalized phone, e.g. `09******67` |
| `email`, `address` | string \| null | Optional |
| `status` | `ACTIVE` \| `MERGED` | `MERGED` = absorbed by a merge (never returned by the list) |
| `created_at`, `updated_at` | datetime | Server time |
| `deleted_at` | datetime \| null | Soft-delete timestamp (BR3) |

### 3.1. `GET /api/customers` (FR2 – search)
1. **Method and path:** `GET /api/customers?search={keyword}`
2. **Business description:** Lists / searches ACTIVE, non-deleted customers by name or by normalized phone digits.
3. **Authentication:** login required (any role).
4. **Parameters:** `search` (optional query string). Matches `full_name LIKE %keyword%` **or** `phone_normalized LIKE %keyword%`. Empty → all.
5. **Success response:** `200 {"data":[customer, ...]}` – newest first (`customer_id DESC`), **maximum 100 rows**. Empty result → `{"data":[]}`.
6. **Errors:** 401 `UNAUTHORIZED` (no session – fixed in BUG-02).
7. **Notes / limits:** phone search uses the normalized digits (search `0901`, not `+84 901`). No pagination beyond the first 100 rows; NFR1 (≤ 2 s on 65,000 rows) not yet measured.

```http
GET /api/customers?search=NORMAL
HTTP 200
{"data":[{"customer_id":3,"full_name":"BT2 DEMO NORMAL","phone":"08******27","email":"bt2.normal@example.com","address":"Viet Nam","status":"ACTIVE","created_at":"2026-11-13 07:08:41","updated_at":"2026-11-13 07:08:41","deleted_at":null}]}
```
```http
GET /api/customers          (no session)
HTTP 401 {"error":"UNAUTHORIZED","message":"Vui long dang nhap"}
```

### 3.2. `POST /api/customers` (FR1 – create)
1. **Method and path:** `POST /api/customers`
2. **Business description:** Creates a customer profile. The phone is normalized (QT-02: `+84…`/`84…`/spaces/dots → `0xxxxxxxxx`) and must not already exist on an ACTIVE profile (BR1/QT-01).
3. **Authentication:** login required, role `SALES_STAFF` or `STORE_MANAGER`; **CSRF required**.
4. **Body:**

   | Field | Required | Rule |
   |---|---|---|
   | `full_name` | yes | trimmed, 1–150 characters |
   | `phone` | yes | only digits, spaces, `+ ( ) . -`; 8–15 digits |
   | `email` | no | valid email, ≤ 150 characters |
   | `address` | no | ≤ 255 characters |
5. **Success response:** `201 {"message":"Tao ho so khach hang thanh cong","data":customer}`
6. **Errors:**

   | HTTP | `error` / message | When |
   |---:|---|---|
   | 400 | `VALIDATION_ERROR` e.g. "Ho ten khach hang la bat buoc", "So dien thoai phai co tu 8 den 15 chu so", "Email khong dung dinh dang" | Invalid field (no row inserted) |
   | 401 | `UNAUTHORIZED` | Not logged in |
   | 403 | `FORBIDDEN` / `CSRF_INVALID` | Role `MARKETING` / bad token |
   | 409 | `BUSINESS_ERROR` "So dien thoai da ton tai trong he thong" | Normalized phone already used by an ACTIVE profile |
7. **Notes / limits:** the duplicate check is in the service layer; there is no UNIQUE index on `phone_normalized`, so two simultaneous requests could both pass (known limitation).

```http
POST /api/customers
X-CSRF-Token: <csrf_token>
{"full_name":"Nguyen Van An","phone":"+84 901 234 567","email":"an.nguyen@example.com","address":"Quan 1, TP. HCM"}

HTTP 201
{"message":"Tao ho so khach hang thanh cong","data":{"customer_id":4,"full_name":"Nguyen Van An","phone":"09******67","email":"an.nguyen@example.com","address":"Quan 1, TP. HCM","status":"ACTIVE","created_at":"2026-11-13 07:09:37","updated_at":"2026-11-13 07:09:37","deleted_at":null}}
```
```http
POST /api/customers         {"phone":"0901234568"}
HTTP 400 {"error":"VALIDATION_ERROR","message":"Ho ten khach hang la bat buoc"}

POST /api/customers         {"full_name":"Nguyen Van An 2","phone":"0901234567"}
HTTP 409 {"error":"BUSINESS_ERROR","message":"So dien thoai da ton tai trong he thong"}

POST /api/customers         (X-CSRF-Token: invalid)
HTTP 403 {"error":"CSRF_INVALID","message":"CSRF token khong hop le"}
```

### 3.3. `GET /api/customers/{id}` (FR3 – detail)
1. **Method and path:** `GET /api/customers/{id}`
2. **Business description:** Returns one customer profile that is not deleted.
3. **Authentication:** login required (any role).
4. **Parameters:** `id` path parameter (positive integer).
5. **Success response:** `200 {"data":customer}`
6. **Errors:** 401; 404 `CUSTOMER_NOT_FOUND` "Khong tim thay khach hang" (unknown, soft-deleted or merged id).
7. **Notes / limits:** none.

```http
GET /api/customers/4
HTTP 200
{"data":{"customer_id":4,"full_name":"Nguyen Van An","phone":"09******67","email":"an.nguyen@example.com","address":"Quan 1, TP. HCM","status":"ACTIVE","created_at":"2026-11-13 07:09:37","updated_at":"2026-11-13 07:09:37","deleted_at":null}}
```
```http
GET /api/customers/999
HTTP 404 {"error":"CUSTOMER_NOT_FOUND","message":"Khong tim thay khach hang"}
```

### 3.4. `PUT /api/customers/{id}` (update)
1. **Method and path:** `PUT /api/customers/{id}`
2. **Business description:** Updates name, email and address of an ACTIVE profile. The phone number cannot be changed here (it is the duplicate-detection key).
3. **Authentication:** login required, role `SALES_STAFF` or `STORE_MANAGER`; **CSRF required**.
4. **Body:** `full_name` (required, 1–150), `email` (optional, valid, ≤ 150), `address` (optional, ≤ 255). **Omitted `email`/`address` are saved as `null`** (full replacement, send the current values to keep them). Sending `phone` or `phone_normalized` is rejected.
5. **Success response:** `200 {"message":"Cap nhat khach hang thanh cong","data":customer}`
6. **Errors:** 400 `VALIDATION_ERROR` ("Khong duoc thay doi so dien thoai", invalid field); 401; 403; 404 `CUSTOMER_NOT_FOUND` "Khong tim thay ho so dang hoat dong".
7. **Notes / limits:** no optimistic locking (last write wins).

```http
PUT /api/customers/4
X-CSRF-Token: <csrf_token>
{"full_name":"Nguyen Van An","email":"an.nguyen@example.com","address":"Quan 3, TP. HCM"}

HTTP 200
{"message":"Cap nhat khach hang thanh cong","data":{"customer_id":4,"full_name":"Nguyen Van An","phone":"09******67","email":"an.nguyen@example.com","address":"Quan 3, TP. HCM","status":"ACTIVE","created_at":"2026-11-13 07:09:37","updated_at":"2026-11-13 07:09:37","deleted_at":null}}
```
```http
PUT /api/customers/4        {"full_name":"Nguyen Van An","phone":"0900000000"}
HTTP 400 {"error":"VALIDATION_ERROR","message":"Khong duoc thay doi so dien thoai"}
```

### 3.5. `DELETE /api/customers/{id}` (soft delete, BR3/QT-13)
1. **Method and path:** `DELETE /api/customers/{id}`
2. **Business description:** Soft-deletes an ACTIVE profile (`deleted_at = now`); the row and its history are kept.
3. **Authentication:** login required, role `SALES_STAFF` or `STORE_MANAGER`; **CSRF required**.
4. **Parameters:** `id`; no body.
5. **Success response:** `200 {"message":"Xoa mem ho so khach hang thanh cong","customer_id":id}`
6. **Errors:** 401; 403; 404 `CUSTOMER_NOT_FOUND` "Khong tim thay ho so dang hoat dong" (unknown or already deleted – second call).
7. **Notes / limits:** there is no restore endpoint.

```http
DELETE /api/customers/4
X-CSRF-Token: <csrf_token>
HTTP 200
{"message":"Xoa mem ho so khach hang thanh cong","customer_id":4}

DELETE /api/customers/4     (second time)
HTTP 404 {"error":"CUSTOMER_NOT_FOUND","message":"Khong tim thay ho so dang hoat dong"}
```

### 3.6. `GET /api/customers/duplicates` (FR4 – duplicate detection)
1. **Method and path:** `GET /api/customers/duplicates`
2. **Business description:** Returns every ACTIVE profile whose normalized phone is shared with at least one other ACTIVE profile (candidates for merging).
3. **Authentication:** login required (any role).
4. **Parameters / body:** none.
5. **Success response:** `200 {"data":[customer, ...]}` ordered by normalized phone, then id (rows of the same group are adjacent). No duplicates → `{"data":[]}`.
6. **Errors:** 401.
7. **Notes / limits:** the only duplicate rule is "same normalized phone"; similar names are not detected. No pagination.

```http
GET /api/customers/duplicates
HTTP 200
{"data":[{"customer_id":1,"full_name":"BT2 DEMO MASTER","phone":"08******26","email":"bt2.master@example.com","address":"Viet Nam","status":"ACTIVE","created_at":"2026-11-13 07:08:41","updated_at":"2026-11-13 07:08:41","deleted_at":null},{"customer_id":2,"full_name":"BT2 DEMO SOURCE","phone":"08******26","email":"bt2.source@example.com","address":"Viet Nam","status":"ACTIVE","created_at":"2026-11-13 07:08:41","updated_at":"2026-11-13 07:08:41","deleted_at":null}]}
```

### 3.7. `POST /api/customers/merge` (FR5 – merge, BR6, NFR4)
1. **Method and path:** `POST /api/customers/merge`
2. **Business description:** Merges a duplicate (source) profile into a master profile in one database transaction: locks both rows (`SELECT … FOR UPDATE`), records `merge_operations` (who, when, master) and `merge_items` (source), sets the source to `MERGED` with `deleted_at`. Any failure rolls back everything.
3. **Authentication:** login required, role `SALES_STAFF` or `STORE_MANAGER`; **CSRF required**. `performed_by` = logged-in user.
4. **Body:** `{"master_customer_id": int, "source_customer_id": int}` – JSON integers (> 0).
5. **Success response:** `200 {"message":"Gop ho so khach hang thanh cong","data":{"merge_id","master_customer_id","source_customer_id","status":"SUCCESS"}}`
6. **Errors:**

   | HTTP | message | When |
   |---:|---|---|
   | 400 | "Ma ho so khach hang khong hop le" | Ids missing, not integers (e.g. `"1"`), or ≤ 0 |
   | 409 | "Khong the gop mot ho so vao chinh no" | master = source |
   | 409 | "Ho so khach hang khong ton tai" | An id does not exist |
   | 409 | "Chi duoc gop ho so ACTIVE" | A profile is MERGED or deleted |
   | 409 | "Hai ho so khong trung so dien thoai" | Normalized phones differ |
   | 401 / 403 | `UNAUTHORIZED` / `FORBIDDEN`, `CSRF_INVALID` | Session / role / token |
7. **Notes / limits:** one source per request; fields of the source are not copied into the master; a merge cannot be undone through the API.

```http
POST /api/customers/merge
X-CSRF-Token: <csrf_token>
{"master_customer_id":1,"source_customer_id":2}

HTTP 200
{"message":"Gop ho so khach hang thanh cong","data":{"merge_id":1,"master_customer_id":1,"source_customer_id":2,"status":"SUCCESS"}}
```
```http
POST /api/customers/merge   {"master_customer_id":1,"source_customer_id":1}
HTTP 409 {"error":"BUSINESS_ERROR","message":"Khong the gop mot ho so vao chinh no"}

POST /api/customers/merge   {"master_customer_id":1,"source_customer_id":3}
HTTP 409 {"error":"BUSINESS_ERROR","message":"Hai ho so khong trung so dien thoai"}

POST /api/customers/merge   {"master_customer_id":"1","source_customer_id":2}
HTTP 400 {"error":"VALIDATION_ERROR","message":"Ma ho so khach hang khong hop le"}
```

---

## 4. Employees (User CRUD – `STORE_MANAGER` only)

User object: `user_id, full_name, email, role_id, role_name, is_active` (`1` active, `0` disabled). The password hash is never returned. Every `/api/users` request checks login first (401), then the role `STORE_MANAGER` (403), then the method (405), then CSRF for non-GET (403).

### 4.1. `GET /api/users`
1. **Method and path:** `GET /api/users`
2. **Business description:** Lists all employee accounts, active and disabled, ordered by id.
3. **Authentication:** login required, role `STORE_MANAGER`.
4. **Parameters / body:** none.
5. **Success response:** `200 {"data":[user, ...]}`
6. **Errors:** 401; 403 `FORBIDDEN` "Ban khong co quyen thuc hien" (other roles).
7. **Notes / limits:** no pagination or filter.

```http
GET /api/users              (STORE_MANAGER)
HTTP 200
{"data":[{"user_id":1,"full_name":"Local Demo Sales","email":"demo.sales@smartcrm.local","role_id":1,"role_name":"SALES_STAFF","is_active":1},{"user_id":2,"full_name":"Local Demo Manager","email":"demo.manager@smartcrm.local","role_id":2,"role_name":"STORE_MANAGER","is_active":1}]}

GET /api/users              (SALES_STAFF)
HTTP 403 {"error":"FORBIDDEN","message":"Ban khong co quyen thuc hien"}
```

### 4.2. `POST /api/users`
1. **Method and path:** `POST /api/users`
2. **Business description:** Creates an employee account; the password is stored as a bcrypt hash.
3. **Authentication:** login required, role `STORE_MANAGER`; **CSRF required**.
4. **Body (only these fields are accepted):**

   | Field | Required | Rule |
   |---|---|---|
   | `full_name` | yes | 1–150 bytes |
   | `email` | yes | valid, ≤ 150, stored lower-case, unique |
   | `role_name` | yes | `STORE_MANAGER` \| `SALES_STAFF` \| `MARKETING` |
   | `password` | yes | 12–72 bytes, no null byte |
5. **Success response:** `201 {"data":user}`
6. **Errors:** 400 `VALIDATION_ERROR` (e.g. "Password must contain 12–72 bytes and no null bytes", "Invalid email", "Invalid role", "Unsupported user fields"); 401; 403; 409 `BUSINESS_ERROR` "Email already exists" (case-insensitive).
7. **Notes / limits:** user-management messages are in English (as implemented).

```http
POST /api/users
X-CSRF-Token: <csrf_token>
{"full_name":"Tran Thi Binh","email":"binh.tran@smartcrm.local","role_name":"MARKETING","password":"Marketing-2026-pw"}

HTTP 201
{"data":{"user_id":3,"full_name":"Tran Thi Binh","email":"binh.tran@smartcrm.local","role_id":3,"role_name":"MARKETING","is_active":1}}
```
```http
POST /api/users             {"full_name":"X","email":"x@smartcrm.local","role_name":"MARKETING","password":"short"}
HTTP 400 {"error":"VALIDATION_ERROR","message":"Password must contain 12–72 bytes and no null bytes"}

POST /api/users             {"full_name":"Y","email":"BINH.TRAN@smartcrm.local","role_name":"MARKETING","password":"Marketing-2026-pw"}
HTTP 409 {"error":"BUSINESS_ERROR","message":"Email already exists"}
```

### 4.3. `GET /api/users/{id}`
1. **Method and path:** `GET /api/users/{id}`
2. **Business description:** Returns one employee account (including disabled ones).
3. **Authentication:** login required, role `STORE_MANAGER`.
4. **Parameters:** `id` path parameter.
5. **Success response:** `200 {"data":user}`
6. **Errors:** 401; 403; 404 `USER_NOT_FOUND` "User not found".
7. **Notes / limits:** none.

```http
GET /api/users/3
HTTP 200
{"data":{"user_id":3,"full_name":"Tran Thi Binh","email":"binh.tran@smartcrm.local","role_id":3,"role_name":"MARKETING","is_active":1}}

GET /api/users/999
HTTP 404 {"error":"USER_NOT_FOUND","message":"User not found"}
```

### 4.4. `PUT /api/users/{id}`
1. **Method and path:** `PUT /api/users/{id}`
2. **Business description:** Updates name, email, role and optionally the password. Takes effect immediately for existing sessions of that user.
3. **Authentication:** login required, role `STORE_MANAGER`; **CSRF required**.
4. **Body:** `full_name`, `email`, `role_name` (required, same rules as 4.2); `password` optional (omitted → unchanged).
5. **Success response:** `200 {"data":user}`
6. **Errors:** 400; 401; 403; 404 `USER_NOT_FOUND`; 409 "Email already exists" / "Cannot remove the last active manager" (demoting the last active STORE_MANAGER).
7. **Notes / limits:** none.

```http
PUT /api/users/3
X-CSRF-Token: <csrf_token>
{"full_name":"Tran Thi Binh","email":"binh.tran@smartcrm.local","role_name":"SALES_STAFF"}

HTTP 200
{"data":{"user_id":3,"full_name":"Tran Thi Binh","email":"binh.tran@smartcrm.local","role_id":1,"role_name":"SALES_STAFF","is_active":1}}
```

### 4.5. `DELETE /api/users/{id}` (disable)
1. **Method and path:** `DELETE /api/users/{id}`
2. **Business description:** Disables an employee account (`is_active = 0`); the row is kept. The disabled user's open sessions are rejected on their next request.
3. **Authentication:** login required, role `STORE_MANAGER`; **CSRF required**.
4. **Parameters:** `id`; no body.
5. **Success response:** `200 {"data":user}` with `"is_active":0`.
6. **Errors:** 401; 403; 404 `USER_NOT_FOUND`; 409 "Cannot disable your own account" / "Cannot remove the last active manager".
7. **Notes / limits:** there is no re-enable endpoint.

```http
DELETE /api/users/3
X-CSRF-Token: <csrf_token>
HTTP 200
{"data":{"user_id":3,"full_name":"Tran Thi Binh","email":"binh.tran@smartcrm.local","role_id":1,"role_name":"SALES_STAFF","is_active":0}}

DELETE /api/users/2         (the logged-in manager's own id)
HTTP 409 {"error":"BUSINESS_ERROR","message":"Cannot disable your own account"}
```
