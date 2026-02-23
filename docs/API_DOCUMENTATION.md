# JumpStart Backend API – Documentation

**Version:** 1.0  
**Base URL:** `https://your-domain.com/api` (e.g. `https://laravelbackendapi.alwaysdata.net/api`)

**Download as PDF:** Open this file in VS Code/Cursor → right-click → “Markdown: Export (PDF)” (if extension installed), or open in browser and use Print → Save as PDF.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Authentication](#2-authentication)
3. [Common Headers & Errors](#3-common-headers--errors)
4. [User APIs](#4-user-apis)
5. [Admin APIs](#5-admin-apis)
6. [Quick Reference](#6-quick-reference)

---

## 1. Overview

- All responses are **JSON**.
- Send **`Accept: application/json`** (and **`Content-Type: application/json`** for POST/PUT/PATCH).
- **Protected routes** need header: **`Authorization: Bearer <token>`**.
- Date/time fields are in **ISO 8601** (e.g. `2026-02-23T10:30:00.000000Z`).

---

## 2. Authentication

| Role   | Token from                    | Use in header              |
|--------|-------------------------------|----------------------------|
| User   | User login / register / OTP  | `Authorization: Bearer <user_token>`  |
| Admin  | Admin login / register        | `Authorization: Bearer <admin_token>` |

- User and Admin tokens are **different**; use the correct token for user vs admin routes.
- Token is returned as `data.token` on login/register.

---

## 3. Common Headers & Errors

### Request headers (recommended)

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>   // for protected routes
```

### Error response format

| Status | Meaning   | Example body |
|--------|-----------|--------------|
| 401    | Unauthenticated | `{ "message": "Unauthenticated." }` |
| 403    | Forbidden | `{ "message": "..." }` |
| 404    | Not found | `{ "message": "..." }` |
| 422    | Validation error | `{ "message": "...", "errors": { "field": ["error"] } }` |

**Hint:** On 422, show `errors` object to the user (e.g. under each form field).

---

## 4. User APIs

**Prefix:** `/api/user`  
**Auth:** All routes under `Route::prefix('user')` need **User** token (except auth routes).

---

### 4.1 User Auth (no token required for login/register)

#### Register

**`POST /api/user/auth/register`**

**Body:**

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "dob": "2000-01-15",
  "phone": "9876543210",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "device_name": "web"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| first_name | string | Yes | Max 255 |
| last_name | string | Yes | Max 255 |
| dob | date (Y-m-d) | Yes | Date of birth |
| phone | string | No | Max 20 |
| email | string | Yes | Unique |
| password | string | Yes | Min 8, must match confirmation |
| password_confirmation | string | Yes | Same as password |
| device_name | string | No | e.g. "web", "android" – for token name |

**Response (201):**

```json
{
  "message": "Registered successfully.",
  "data": {
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "dob": "2000-01-15",
      "phone": "9876543210",
      "status": "active"
    },
    "token": "1|abc...",
    "token_type": "Bearer"
  }
}
```

**Hint:** Save `data.token` and use it in `Authorization: Bearer <token>` for all user APIs.

---

#### Login

**`POST /api/user/auth/login`**

**Body:**

```json
{
  "email": "john@example.com",
  "password": "password123",
  "device_name": "web"
}
```

**Response (200):** Same structure as register (user + token).  
**401:** Invalid credentials.

---

#### Logout

**`POST /api/user/auth/logout`**  
**Auth:** Required (User token).

**Body:** None.

**Response (200):** `{ "message": "Logged out successfully." }`  
**Hint:** After logout, remove token from storage and redirect to login.

---

#### OTP – Request

**`POST /api/user/auth/otp/request`**

**Body:**

```json
{
  "email": "john@example.com"
}
```

**Response (200):** `{ "message": "OTP generated successfully." }`  
In local env, response may include `debug_otp` for testing.

---

#### OTP – Verify (login with OTP)

**`POST /api/user/auth/otp/verify`**

**Body:**

```json
{
  "email": "john@example.com",
  "code": "123456",
  "device_name": "web"
}
```

**Response (200):** Same as login (user + token).  
**422:** Invalid or expired OTP.

---

#### Social login

**`POST /api/user/auth/social/{provider}`**  
Replace `{provider}` with e.g. `google`, `facebook`.

**Body:** Depends on provider (e.g. provider token). Check backend for exact payload.

---

### 4.2 User – Init / Profile / Dashboard (Auth required)

#### Auth check

**`GET /api/user/init`**  
**Response (200):** `{ "message": "You Authenticate User." }`  
**Hint:** Use to verify token is still valid on app load.

---

#### Get profile

**`GET /api/user/profile`**

**Response (200):**

```json
{
  "message": "Profile.",
  "data": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "name": "John Doe",
    "email": "john@example.com",
    "dob": "2000-01-15",
    "phone": "9876543210",
    "status": "active",
    "email_verified_at": null,
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-02-23T00:00:00.000000Z"
  }
}
```

---

#### Update profile

**`PUT /api/user/profile`** or **`PATCH /api/user/profile`**

**Body (all fields optional):**

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "dob": "2000-01-15",
  "phone": "9876543210",
  "email": "newemail@example.com"
}
```

To change password:

```json
{
  "current_password": "oldpass123",
  "password": "newpass123",
  "password_confirmation": "newpass123"
}
```

**Response (200):** Same as GET profile with updated data.  
**422:** Validation error or "Current password is incorrect." if changing password.

---

#### Dashboard

**`GET /api/user/dashboard`**

**Response (200):**

```json
{
  "message": "Dashboard.",
  "data": {
    "stats": {
      "active_packages": 1,
      "total_orders": 3,
      "tests_completed": 2,
      "unread_notifications": 1
    },
    "recent_attempts": [
      {
        "attempt_id": 5,
        "test_id": 1,
        "test_title": "Math Test",
        "status": "completed",
        "started_at": "2026-02-23T10:00:00.000000Z",
        "completed_at": "2026-02-23T10:30:00.000000Z"
      }
    ]
  }
}
```

**Hint:** Use `stats` for dashboard cards; `recent_attempts` for “Recent tests” list.

---

### 4.3 User – Packages (Auth required)

#### List all packages (catalog)

**`GET /api/user/packages`**

**Response (200):**

```json
{
  "data": [
    {
      "id": 1,
      "title": "Premium Pack",
      "description": "...",
      "price": 999,
      "validity_days": 365,
      "total_tests": 5,
      "is_purchased": false,
      "expiry_date": null,
      "tests": [
        { "id": 1, "title": "Math", "difficulty": "medium", "total_time": 60 }
      ]
    }
  ]
}
```

---

#### My purchased packages

**`GET /api/user/packages/my`**

**Response (200):**

```json
{
  "message": "My packages.",
  "data": [
    {
      "user_package_id": 2,
      "package_id": 1,
      "package_name": "Premium Pack",
      "activated_at": "2026-02-01T00:00:00.000000Z",
      "expiry_date": "2027-02-01",
      "status": "active",
      "is_active": true,
      "attempts_used": 0,
      "max_attempts": null,
      "tests_count": 5,
      "tests": [
        { "id": 1, "title": "Math Test", "total_time": 60 }
      ]
    }
  ]
}
```

**Hint:** Use `user_package_id` when starting a test: `POST /api/user/packages/{user_package_id}/tests/{test_id}/start`.

---

#### Single package detail

**`GET /api/user/packages/{id}`**  
**Response (200):** `{ "data": { ...package with tests... } }`

---

#### Buy package (create order)

**`POST /api/user/packages/{id}/buy`**  
**Body (optional):** `{ "coupon_code": "SAVE10" }`

**Response (201):** Order object. Then use order ID for payment: `POST /api/user/orders/{orderId}/pay`.

---

#### Start test

**`POST /api/user/packages/{userPackageId}/tests/{testId}/start`**

- `userPackageId` = from **My packages** (`user_package_id`).
- `testId` = from package’s `tests[].id`.

**Response (200):**

```json
{
  "message": "Test started successfully.",
  "attempt_id": 10,
  "test_id": 1,
  "test_title": "Math Test",
  "total_time": 60,
  "started_at": "2026-02-23T10:00:00.000000Z",
  "status": "in_progress",
  "total_questions": 20,
  "questions": [
    {
      "id": 101,
      "section_id": 1,
      "question": "What is 2+2?",
      "type": "mcq",
      "difficulty": "easy",
      "sequence": 1,
      "options": [
        { "id": 201, "text": "4", "sequence": 1 },
        { "id": 202, "text": "3", "sequence": 2 }
      ]
    }
  ]
}
```

**Hint:** Save `attempt_id`. Show first question; on “Next”, submit answer then show `next_question` from submit response.

---

### 4.4 User – Orders (Auth required)

#### List my orders

**`GET /api/user/orders`**  
**Response (200):** `{ "data": [ ...orders... ] }`

---

#### Single order

**`GET /api/user/orders/{id}`**

---

#### Create Razorpay order (get payment link/order id)

**`POST /api/user/orders/{orderId}/pay`**  
**Body:** Optional amount override; usually empty.

**Response (200):** Razorpay order details (e.g. `order_id`, `amount`) for frontend Razorpay SDK.

**Hint:** Use returned data with Razorpay checkout; on success call verify.

---

#### Verify payment

**`POST /api/user/orders/{orderId}/verify`**  
**Body:** Signature and payment details from Razorpay (as required by backend).

**Response (200):** Payment verified; order/package status updated.

---

### 4.5 User – Tests (Auth required)

#### Tests of active package

**`GET /api/user/tests`**

**Response (200):**

```json
{
  "package": { "id": 1, "title": "...", "expiry_date": "2027-02-01" },
  "tests": [
    { "id": 1, "title": "Math Test", "difficulty": "medium", "total_time": 60, "total_questions": 20 }
  ]
}
```

**Hint:** Shown when user has an active package; use to list tests before “Start test”.

---

### 4.6 User – Test attempts (Auth required)

#### List my attempts

**`GET /api/user/attempts`**

**Response (200):**

```json
{
  "message": "My attempts.",
  "data": [
    {
      "attempt_id": 10,
      "test_id": 1,
      "test_title": "Math Test",
      "package_name": "Premium Pack",
      "status": "in_progress",
      "started_at": "2026-02-23T10:00:00.000000Z",
      "completed_at": null,
      "total_questions": 20,
      "answered_count": 5
    }
  ]
}
```

---

#### Get single attempt (resume or result)

**`GET /api/user/attempts/{attemptId}`**

- If **in_progress:** same shape as “Start test” (questions list for resume).
- If **completed:** result with `section_wise`, `total_correct`, `total_questions`, `score_percentage`.

**Hint:** Use to resume test or show result screen.

---

#### Submit single answer

**`POST /api/user/attempts/{attemptId}/answer`**

**Body (MCQ):**

```json
{
  "question_id": 101,
  "question_option_id": 201
}
```

**Body (scale/other):** `{ "question_id": 101, "answer_value": "5" }`

**Response (200):**

```json
{
  "message": "Answer saved.",
  "answered_count": 6,
  "total_questions": 20,
  "all_answered": false,
  "next_question": {
    "id": 102,
    "section_id": 1,
    "question": "Next question text?",
    "type": "mcq",
    "difficulty": "medium",
    "sequence": 2,
    "options": [ ... ]
  }
}
```

**Hint:** If `next_question` is null and `all_answered` is true, show “Generate Score” button.

---

#### Generate score (finish test)

**`POST /api/user/attempts/{attemptId}/submit-score`**

**Response (200):**

```json
{
  "message": "Result generated.",
  "attempt_id": 10,
  "test_id": 1,
  "test_title": "Math Test",
  "status": "completed",
  "completed_at": "2026-02-23T10:35:00.000000Z",
  "section_wise": [
    { "section_id": 1, "section_name": "Quant", "correct": 8, "total": 10 },
    { "section_id": 2, "section_name": "Verbal", "correct": 7, "total": 10 }
  ],
  "total_correct": 15,
  "total_questions": 20,
  "score_percentage": 75.00
}
```

---

### 4.7 User – Notifications (Auth required)

#### List notifications

**`GET /api/user/notifications`**

**Query:** `?read=all|read|unread` (default: all), `?per_page=15` (max 50).

**Response (200):**

```json
{
  "message": "My notifications.",
  "data": [
    {
      "receiver_id": 5,
      "notification_id": 3,
      "type": "order",
      "title": "Order confirmed",
      "body": "Your order #ORD001 is confirmed.",
      "data": { "order_id": 1 },
      "read_at": null,
      "created_at": "2026-02-23T10:00:00.000000Z"
    }
  ],
  "meta": { "current_page": 1, "last_page": 1, "per_page": 15, "total": 1 },
  "unread_count": 1
}
```

---

#### Get new notifications (after last_id)

**`GET /api/user/notifications/new?last_id=5&limit=50`**

- **last_id:** Last `receiver_id` UI has (from list or previous “new” call). Omit on first load.
- **limit:** Max 100, default 50.

**Response (200):**

```json
{
  "message": "New notifications.",
  "data": [ ... same item shape as list ... ],
  "latest_receiver_id": 8,
  "count": 2
}
```

**Hint:** Save `latest_receiver_id`; next time call with `last_id=8` to get only newer ones. Use for badge or “new” strip.

---

#### Unread count

**`GET /api/user/notifications/unread-count`**  
**Response (200):** `{ "unread_count": 3 }`

---

#### Mark all as read

**`POST /api/user/notifications/mark-all-read`**  
**Response (200):** `{ "message": "All notifications marked as read.", "count": 3 }`

---

#### Mark one as read

**`PATCH /api/user/notifications/{receiverId}/read`**  
**Response (200):** `{ "message": "Marked as read." }`

---

## 5. Admin APIs

**Prefix:** `/api/admin`  
**Auth:** All admin routes need **Admin** token (except `admin/auth` login/register).

---

### 5.1 Admin Auth (no token for login/register)

#### Register

**`POST /api/admin/auth/register`**  
**Body:** Same pattern as user (backend may use different fields). Check backend for exact validation.

---

#### Login

**`POST /api/admin/auth/login`**

**Body:**

```json
{
  "email": "admin@example.com",
  "password": "password123",
  "device_name": "admin-web"
}
```

**Response (200):** Admin object + token. Use token in `Authorization: Bearer <admin_token>`.

---

#### Logout

**`POST /api/admin/auth/logout`**  
**Auth:** Required.

---

#### Two-factor start

**`POST /api/admin/auth/two-factor/start`**  
**Body:** As required by backend (e.g. email).

---

#### Two-factor verify

**`POST /api/admin/auth/two-factor/verify`**  
**Body:** Code + any other required fields.

---

### 5.2 Public / Health (no auth)

**`GET /api/init`** → `{ "message": "Api Running." }`  
**`GET /api/deploy`** → same.  
**`GET /api/questions/sample`** → Excel sample for question import (download).

---

### 5.3 Admin – Meta (dropdowns)

**Auth:** Required.

- **`GET /api/admin/meta/difficulties`** → `[{ "key": "easy", "label": "Easy" }, ...]`
- **`GET /api/admin/meta/question-types`** → `[{ "key": "mcq", "label": "Multiple Choice (MCQ)" }, ...]`
- **`GET /api/admin/meta/test-statuses`** → `[{ "key": "draft", "label": "Draft" }, ...]`
- **`GET /api/admin/meta/yes-no`** → `[{ "key": 1, "label": "Yes" }, { "key": 0, "label": "No" }]`

**Hint:** Use for dropdowns when creating/editing tests, sections, questions.

---

### 5.4 Admin – Test management

**Base:** `/api/admin/test`  
**Auth:** Required.

#### Sections

- **`GET /api/admin/test/sections`** – List
- **`POST /api/admin/test/sections`** – Create (name, description, status, etc.)
- **`GET /api/admin/test/sections/{id}`** – Show
- **`PUT /api/admin/test/sections/{id}`** – Update
- **`DELETE /api/admin/test/sections/{id}`** – Delete

#### Questions

- **`GET /api/admin/test/questions`** – List (filter by section_id, difficulty, etc. if supported)
- **`POST /api/admin/test/questions`** – Create (section_id, question_text, question_type, difficulty, status, options array)
- **`GET /api/admin/test/questions/{id}`** – Show
- **`PUT /api/admin/test/questions/{id}`** – Update
- **`DELETE /api/admin/test/questions/{id}`** – Delete

#### Tests

- **`GET /api/admin/test/tests`** – List
- **`POST /api/admin/test/tests`** – Create (title, intro, instructions, difficulty, total_time, status, etc.)
- **`GET /api/admin/test/tests/{id}`** – Show
- **`PUT /api/admin/test/tests/{id}`** – Update
- **`DELETE /api/admin/test/tests/{id}`** – Delete
- **`POST /api/admin/test/tests/{id}/publish`** – Publish test

#### Test sections (rules per test)

- **`GET /api/admin/test/tests/{testId}/sections`** – List sections linked to test
- **`POST /api/admin/test/tests/{testId}/sections`** – Attach section + rules (total_questions, section_time, marks_per_question, rules: [{ difficulty, question_count }])
- **`DELETE /api/admin/test/tests/{testId}/sections/{id}`** – Remove

#### Import questions

- **`GET /api/admin/test/import/questions/sample`** – Download sample Excel
- **`POST /api/admin/test/import/questions`** – Upload Excel (multipart/form-data, file)

---

### 5.5 Admin – Packages

**Base:** `/api/admin/packages`  
**Auth:** Required.

- **`GET /api/admin/packages`** – List
- **`POST /api/admin/packages`** – Create (name, slug, description, price, validity_days, attempt_limit, status, test_ids[])
- **`GET /api/admin/packages/{id}`** – Show
- **`PUT /api/admin/packages/{id}`** – Update
- **`DELETE /api/admin/packages/{id}`** – Delete

---

### 5.6 Admin – Coupons

**Base:** `/api/admin/coupons`  
**Auth:** Required.

- **`GET /api/admin/coupons`** – List
- **`POST /api/admin/coupons`** – Create
- **`GET /api/admin/coupons/{id}`** – Show
- **`PUT /api/admin/coupons/{id}`** – Update
- **`DELETE /api/admin/coupons/{id}`** – Delete

---

### 5.7 Admin – Orders & payments

**Auth:** Required.

- **`GET /api/admin/orders`** – List orders
- **`GET /api/admin/orders/stats`** – Order statistics
- **`GET /api/admin/orders/{id}`** – Single order
- **`PUT /api/admin/orders/{id}/status`** – Update order status
- **`GET /api/admin/orders/{orderId}/payments`** – Payments for order
- **`GET /api/admin/payments`** – All payments
- **`GET /api/admin/payments/{id}`** – Single payment

---

### 5.8 Admin – Dashboard

**`GET /api/admin/dashboard`**  
**Response (200):** Analytics/stats (structure as per backend). Use for admin home page.

---

### 5.9 Admin – Roles & permissions

**Base:** `/api/admin/access`  
**Auth:** Required.

- **`GET /api/admin/access/roles`** – List roles
- **`POST /api/admin/access/roles`** – Create role
- **`GET /api/admin/access/roles/{id}`** – Role details
- **`PUT /api/admin/access/roles/{id}`** – Update role
- **`DELETE /api/admin/access/roles/{id}`** – Delete role
- **`GET /api/admin/access/permissions`** – List permissions
- **`POST /api/admin/access/roles/{id}/permissions`** – Assign permissions to role (body: permission ids or names as per backend)

---

### 5.10 Admin – Notifications

**Auth:** Required.

- **`GET /api/admin/notifications`** – List (same query as user: `?read=all|read|unread`, `?per_page=15`)
- **`GET /api/admin/notifications/new?last_id=5&limit=50`** – New after last_id (same as user)
- **`GET /api/admin/notifications/unread-count`** – Unread count
- **`POST /api/admin/notifications/mark-all-read`** – Mark all read
- **`PATCH /api/admin/notifications/{receiverId}/read`** – Mark one read

#### Send notification to users/admins

**`POST /api/admin/notifications/send`**

**Body:**

```json
{
  "title": "New offer",
  "body": "Check out our new package.",
  "type": "general",
  "data": { "link": "/packages/1" },
  "user_ids": [1, 2, 3],
  "admin_ids": [1]
}
```

| Field     | Required | Description |
|----------|----------|-------------|
| title    | Yes      | Max 255     |
| body     | No       | Text        |
| type     | No       | e.g. general, order, test_result |
| data     | No       | JSON object |
| user_ids | No       | Array of user IDs (at least one of user_ids or admin_ids required) |
| admin_ids| No       | Array of admin IDs |

**Response (201):** `{ "message": "Notification sent.", "data": { "notification_id": 1, "receivers_count": 4 } }`

---

## 6. Quick Reference

| Area        | Method | Endpoint (relative to /api) | Auth  |
|------------|--------|-----------------------------|--------|
| Health     | GET    | /init                       | No     |
| User register | POST | /user/auth/register         | No     |
| User login | POST   | /user/auth/login            | No     |
| User logout| POST   | /user/auth/logout           | User   |
| User profile | GET  | /user/profile               | User   |
| User profile update | PUT/PATCH | /user/profile      | User   |
| User dashboard | GET | /user/dashboard             | User   |
| My packages | GET  | /user/packages/my           | User   |
| Start test | POST   | /user/packages/{upId}/tests/{testId}/start | User |
| Submit answer | POST | /user/attempts/{attemptId}/answer | User |
| Submit score | POST | /user/attempts/{attemptId}/submit-score | User |
| My attempts | GET  | /user/attempts              | User   |
| User notifications | GET | /user/notifications         | User   |
| New notifications (user) | GET | /user/notifications/new?last_id= | User |
| Admin login | POST  | /admin/auth/login           | No     |
| Admin logout | POST | /admin/auth/logout          | Admin  |
| Admin meta | GET    | /admin/meta/difficulties, question-types, test-statuses, yes-no | Admin |
| Admin sections | CRUD | /admin/test/sections        | Admin  |
| Admin questions | CRUD | /admin/test/questions      | Admin  |
| Admin tests | CRUD  | /admin/test/tests          | Admin  |
| Admin packages | CRUD | /admin/packages             | Admin  |
| Admin orders | GET   | /admin/orders              | Admin  |
| Admin dashboard | GET | /admin/dashboard            | Admin  |
| Admin send notification | POST | /admin/notifications/send  | Admin  |

---

**End of API Documentation.**  
For validation details (max length, formats), rely on 422 responses from the API. For downloadable sheet: open this file in any editor and use “Print to PDF” or export to PDF from VS Code / Cursor.
