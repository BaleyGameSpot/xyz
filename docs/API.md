# Chinar Signals — REST API Documentation

## Overview

The Chinar Signals API is a JSON REST API that powers the Android trading signal application.
All API responses follow a consistent envelope format.

**Base URL**
```
https://api.chinarsignals.com/api/v1
```

**Response Envelope**
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

**Error Envelope**
```json
{
    "success": false,
    "message": "Error description",
    "errors": { "field": ["Validation error"] }
}
```

---

## Authentication

The API uses **JWT (JSON Web Tokens)** for stateless authentication.

### Obtaining a Token

`POST /auth/login`

**Request Body**
```json
{
    "email": "user@example.com",
    "password": "secret123",
    "fcm_token": "firebase-device-token-here"
}
```

**Response 200**
```json
{
    "success": true,
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 86400,
        "user": {
            "id": 1,
            "name": "Ahmad Khan",
            "email": "user@example.com",
            "subscription": {
                "is_active": true,
                "package": "Pro",
                "expires_at": "2025-12-31T23:59:59Z"
            }
        }
    }
}
```

### Using the Token

Include the token in the `Authorization` header for all protected endpoints:

```
Authorization: Bearer <your-jwt-token>
```

### Refreshing a Token

`POST /auth/refresh`

**Headers**: `Authorization: Bearer <expired-token>`

**Response 200**
```json
{
    "success": true,
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 86400
    }
}
```

---

## Rate Limiting

| Endpoint Group     | Limit              |
|--------------------|--------------------|
| `/auth/*`          | 10 req/min per IP  |
| `/signals/*`       | 60 req/min per user|
| All other routes   | 120 req/min per user|

Rate limit headers are included in every response:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
X-RateLimit-Reset: 1703001600
```

When the limit is exceeded, the API returns `429 Too Many Requests`.

---

## Endpoints

### Authentication

#### Register
`POST /auth/register`

**Request Body**
```json
{
    "name": "Ahmad Khan",
    "email": "user@example.com",
    "password": "secret123",
    "password_confirmation": "secret123",
    "fcm_token": "firebase-device-token"
}
```

**Response 201**
```json
{
    "success": true,
    "message": "Registration successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "user": {
            "id": 42,
            "name": "Ahmad Khan",
            "email": "user@example.com",
            "created_at": "2025-03-25T10:00:00Z"
        }
    }
}
```

#### Login
`POST /auth/login`
*(See Authentication section above)*

#### Logout
`POST /auth/logout`
**Headers**: `Authorization: Bearer <token>`

**Response 200**
```json
{ "success": true, "message": "Logged out successfully" }
```

#### Get Current User
`GET /auth/me`
**Headers**: `Authorization: Bearer <token>`

**Response 200**
```json
{
    "success": true,
    "data": {
        "id": 42,
        "name": "Ahmad Khan",
        "email": "user@example.com",
        "phone": "+964771234567",
        "subscription": {
            "is_active": true,
            "package_id": 2,
            "package_name": "Pro",
            "expires_at": "2025-12-31T23:59:59Z",
            "days_remaining": 281
        }
    }
}
```

#### Update FCM Token
`PUT /auth/fcm-token`
**Headers**: `Authorization: Bearer <token>`

```json
{ "fcm_token": "new-firebase-token" }
```

**Response 200**
```json
{ "success": true, "message": "FCM token updated" }
```

---

### Packages

#### List Packages
`GET /packages`

No authentication required.

**Response 200**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Basic",
            "description": "Essential signals for beginners",
            "price": 9.99,
            "promo_price": null,
            "pairs_limit": 5,
            "daily_signals_limit": 3,
            "timeframes": ["H1", "H4", "D1"],
            "is_popular": false,
            "is_active": true
        },
        {
            "id": 2,
            "name": "Pro",
            "description": "Full access for serious traders",
            "price": 19.99,
            "promo_price": 14.99,
            "pairs_limit": 0,
            "daily_signals_limit": 0,
            "timeframes": ["M5","M15","M30","H1","H4","D1","W1"],
            "is_popular": true,
            "is_active": true
        }
    ]
}
```

#### Get Single Package
`GET /packages/{id}`

---

### Payments

#### Submit Payment
`POST /payments`
**Headers**: `Authorization: Bearer <token>`

```json
{
    "package_id": 2,
    "crypto_type": "USDT_TRC20",
    "tx_hash": "a1b2c3d4e5f6...",
    "amount": 19.99
}
```

**Response 201**
```json
{
    "success": true,
    "message": "Payment submitted for verification",
    "data": {
        "id": 15,
        "status": "pending",
        "package": "Pro",
        "amount": 19.99,
        "crypto_type": "USDT_TRC20",
        "tx_hash": "a1b2c3d4e5f6...",
        "created_at": "2025-03-25T10:30:00Z"
    }
}
```

#### Get Payment History
`GET /payments`
**Headers**: `Authorization: Bearer <token>`

**Response 200**
```json
{
    "success": true,
    "data": [
        {
            "id": 15,
            "package": "Pro",
            "amount": 19.99,
            "crypto_type": "USDT_TRC20",
            "tx_hash": "a1b2c3d4e5f6...",
            "status": "verified",
            "created_at": "2025-03-25T10:30:00Z",
            "verified_at": "2025-03-25T11:00:00Z"
        }
    ]
}
```

#### Get Wallet Addresses
`GET /payments/wallets`

No authentication required.

**Response 200**
```json
{
    "success": true,
    "data": {
        "USDT_TRC20": "TXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "USDT_ERC20": "0xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxXX",
        "BTC": "bc1qxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
        "ETH": "0xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxXX",
        "BNB": "bnbxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
    }
}
```

---

### Signals

#### List Signals
`GET /signals`
**Headers**: `Authorization: Bearer <token>`

**Query Parameters**

| Parameter   | Type   | Description                       |
|-------------|--------|-----------------------------------|
| `pair`      | string | Filter by trading pair (e.g. XAUUSD) |
| `timeframe` | string | Filter by timeframe (M1–W1)       |
| `type`      | string | BUY or SELL                       |
| `status`    | string | pending, win, loss                |
| `page`      | int    | Page number (default: 1)          |
| `per_page`  | int    | Results per page (default: 20, max: 50) |

**Response 200**
```json
{
    "success": true,
    "data": {
        "signals": [
            {
                "id": 101,
                "pair": "XAUUSD",
                "timeframe": "H4",
                "type": "BUY",
                "entry_price": 2315.50,
                "stop_loss": 2295.00,
                "take_profits": [2335.00, 2355.00, 2380.00],
                "confidence_score": 78,
                "status": "pending",
                "reason": "Strong bullish momentum with RSI oversold bounce",
                "indicators": [
                    { "name": "RSI", "signal": "BUY", "value": "28.4", "weight": 25 },
                    { "name": "MACD", "signal": "BUY", "value": "crossover", "weight": 30 },
                    { "name": "EMA200", "signal": "BUY", "value": "above", "weight": 20 },
                    { "name": "Bollinger", "signal": "NEUTRAL", "value": "lower band", "weight": 15 },
                    { "name": "Volume", "signal": "BUY", "value": "above avg", "weight": 10 }
                ],
                "created_at": "2025-03-25T08:00:00Z",
                "closed_at": null
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 20,
            "total": 98
        }
    }
}
```

#### Get Signal Details
`GET /signals/{id}`
**Headers**: `Authorization: Bearer <token>`

Returns the full signal object as shown above.

**Error 403** — If the user's subscription does not include this signal's pair/timeframe:
```json
{
    "success": false,
    "message": "Your subscription plan does not include this pair. Upgrade to access."
}
```

#### Get Latest Signal for Pair
`GET /signals/latest?pair=XAUUSD&timeframe=H4`
**Headers**: `Authorization: Bearer <token>`

Returns the most recent signal for the specified pair and timeframe.

---

### Subscription

#### Get Subscription Status
`GET /subscription`
**Headers**: `Authorization: Bearer <token>`

**Response 200**
```json
{
    "success": true,
    "data": {
        "is_active": true,
        "package": {
            "id": 2,
            "name": "Pro",
            "pairs_limit": 0,
            "daily_signals_limit": 0,
            "timeframes": ["M5","M15","M30","H1","H4","D1","W1"]
        },
        "started_at": "2025-02-25T00:00:00Z",
        "expires_at": "2025-03-25T23:59:59Z",
        "days_remaining": 0,
        "is_expired": true
    }
}
```

---

## HTTP Status Codes

| Code | Meaning                                              |
|------|------------------------------------------------------|
| 200  | OK — Request succeeded                               |
| 201  | Created — Resource created                           |
| 400  | Bad Request — Invalid request body or parameters     |
| 401  | Unauthorized — Missing or invalid JWT token          |
| 403  | Forbidden — Valid token but insufficient permissions |
| 404  | Not Found — Resource does not exist                  |
| 422  | Unprocessable Entity — Validation errors             |
| 429  | Too Many Requests — Rate limit exceeded              |
| 500  | Internal Server Error — Unexpected server error      |

---

## Error Codes

| Code                    | HTTP | Description                              |
|-------------------------|------|------------------------------------------|
| `INVALID_CREDENTIALS`   | 401  | Wrong email or password                  |
| `TOKEN_EXPIRED`         | 401  | JWT token has expired — refresh it       |
| `TOKEN_INVALID`         | 401  | Malformed or tampered token              |
| `USER_BLOCKED`          | 403  | Account has been blocked by admin        |
| `NO_SUBSCRIPTION`       | 403  | Action requires an active subscription  |
| `SUBSCRIPTION_EXPIRED`  | 403  | Subscription has expired                 |
| `PAIR_NOT_IN_PLAN`      | 403  | Pair not available in current plan       |
| `PAYMENT_PENDING`       | 409  | A payment is already pending for user   |
| `DUPLICATE_TX`          | 409  | Transaction hash already used            |
| `MAINTENANCE_MODE`      | 503  | API temporarily unavailable              |

---

## Versioning

The API is versioned via the URL path (`/api/v1/`). Breaking changes will be introduced in a new version (`/api/v2/`) with a deprecation period for older versions.

---

## Changelog

| Version | Date       | Changes                                |
|---------|------------|----------------------------------------|
| v1.0.0  | 2025-03-25 | Initial release                        |
