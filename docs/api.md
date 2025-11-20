# GamerProtocol.io API Documentation

Complete API reference for the GamerProtocol.io v1 platform. This headless API serves multiple frontend clients (web, mobile, Telegram) with a clean RESTful architecture organized into 9 functional namespaces.

**Base URL**: `https://api.gamerprotocol.io/v1`

**Required Headers for Authorization:**
- `Authorization: Bearer [Sanctum User Token]`
- `X-Client-Key: [API Key from clients table]` (For application authorization)

---

## 📋 Table of Contents

1. [API Architecture](#api-architecture)
2. [Authentication](#authentication)
3. [API Namespaces](#api-namespaces)
   - [System & Webhooks](#1-system--webhooks)
   - [Game Library](#2-game-library)
   - [Authentication](#3-authentication)
   - [Account Management](#4-account-management)
   - [Floor Coordination](#5-floor-coordination)
   - [Active Games](#6-active-games)
   - [Economy](#7-economy)
   - [Data Feeds](#8-data-feeds)
   - [Competitions](#9-competitions)
4. [Common Patterns](#common-patterns)
5. [Error Handling](#error-handling)
6. [Real-Time Events](#real-time-events)

---

## API Architecture

The GamerProtocol.io API is organized into 9 logical namespaces that separate concerns and provide intuitive endpoint discovery:

| Namespace | Purpose | Authentication |
|-----------|---------|----------------|
| **System** | Health checks, webhooks | Public/Vendor |
| **Library** | Game catalog and rules | Public |
| **Auth** | Registration, login, tokens | Client Key |
| **Account** | User profile, stats, transactions | Bearer + Client Key |
| **Floor** | Matchmaking, lobbies, proposals | Bearer + Client Key |
| **Games** | Active game state and actions | Bearer + Client Key |
| **Economy** | Balance, transactions, subscriptions | Bearer + Client Key |
| **Feeds** | Real-time SSE streams | Bearer + Client Key |
| **Competitions** | Tournaments and brackets | Bearer + Client Key |

### Design Principles

- **RESTful**: Standard HTTP methods (GET, POST, PUT, PATCH, DELETE)
- **Resource-oriented**: Clear hierarchies (e.g., `/games/{ulid}/actions`)
- **Stateless**: Each request contains all necessary information
- **Idempotent actions**: Game actions include idempotency keys
- **JSON-only**: All requests and responses use `application/json`
- **Pagination**: Collections use cursor-based pagination
- **Versioning**: URL-based (`/v1/`, `/v2/`) with deprecation headers

---

## Authentication

### Dual Authentication Model

All authenticated endpoints require **both** a Bearer token (user identity) and a Client Key (application identity):

```http
GET /v1/account/profile
Authorization: Bearer 1|abc123xyz...
X-Client-Key: your-client-key-here
```

### Bearer Token (User Authentication)

Obtained via login endpoints in the Auth namespace. Represents a specific user session.

**Characteristics**:
- Laravel Sanctum personal access tokens
- Scoped to individual users
- Can be revoked via logout
- Expires based on session configuration

### Client Key (Application Authentication)

Identifies the client application (web, iOS, Android, Telegram bot). Required for all API calls.

**Characteristics**:
- Static key per application
- Stored in `clients` table
- Used for rate limiting and analytics
- Required even for public endpoints

---

## API Namespaces

### 1. System & Webhooks

Health monitoring and external service webhooks.

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| `GET` | `/system/health` | API health check with database connectivity | Public |
| `POST` | `/webhooks/stripe` | Stripe subscription event handler | Vendor Signature |
| `POST` | `/webhooks/apple` | Apple App Store notification handler | Vendor Signature |
| `POST` | `/webhooks/google` | Google Play notification handler | Vendor Signature |

**Example: Health Check**

```http
GET /v1/system/health
X-Client-Key: your-client-key
```

Response:
```json
{
  "status": "healthy",
  "version": "1.0.0",
  "database": "connected",
  "timestamp": "2025-11-20T12:00:00Z"
}
```

---

### 2. Game Library

Public catalog of available games, rules, and leaderboards.

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| `GET` | `/library` | List all available game titles | Public |
| `GET` | `/library/{titleKey}` | Get detailed game information | Public |
| `GET` | `/library/{titleKey}/rules` | Get complete rule documentation | Public |
| `GET` | `/library/{titleKey}/leaderboards` | Get game-specific leaderboards | Public |

**Example: List Games**

```http
GET /v1/library
X-Client-Key: your-client-key
```

Response:
```json
{
  "data": [
    {
      "key": "chess",
      "name": "Chess",
      "description": "Classic strategy game of checkmate",
      "min_players": 2,
      "max_players": 2,
      "pacing": "turn_based",
      "complexity": 5,
      "thumbnail": "https://cdn.gamerprotocol.io/games/chess/thumb.jpg"
    },
    {
      "key": "connect-four",
      "name": "Connect Four",
      "description": "Align four pieces in a row to win",
      "min_players": 2,
      "max_players": 2,
      "pacing": "turn_based",
      "complexity": 2,
      "thumbnail": "https://cdn.gamerprotocol.io/games/connect-four/thumb.jpg"
    }
  ]
}
```

**Example: Get Rules**

```http
GET /v1/library/chess/rules
X-Client-Key: your-client-key
```

Response:
```json
{
  "data": {
    "title_key": "chess",
    "name": "Chess",
    "objective": "Checkmate the opponent's king",
    "setup": "Standard 8x8 board with 16 pieces per player",
    "turn_structure": "Alternating moves, white moves first",
    "winning_conditions": ["checkmate", "resignation", "timeout"],
    "special_rules": {
      "castling": "King and rook special move under specific conditions",
      "en_passant": "Special pawn capture move",
      "promotion": "Pawn reaching opposite end promotes to any piece"
    },
    "time_controls": {
      "blitz": { "initial": 300, "increment": 0 },
      "rapid": { "initial": 600, "increment": 5 },
      "classic": { "initial": 1800, "increment": 10 }
    }
  }
}
```

---

### 3. Authentication

User registration, login, and session management.

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| `POST` | `/auth/register` | Create new account (email verification required) | Client Key |
| `POST` | `/auth/verify` | Verify email with token | Client Key |
| `POST` | `/auth/login` | Email/password login | Client Key |
| `POST` | `/auth/social` | Social provider login (Google, Apple, Telegram) | Client Key |
| `POST` | `/auth/logout` | Revoke current session token | Bearer + Client Key |
| `POST` | `/auth/refresh` | Refresh session token | Bearer + Client Key |

**Example: Registration**

```http
POST /v1/auth/register
X-Client-Key: your-client-key
Content-Type: application/json

{
  "email": "player@example.com",
  "username": "coolplayer",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

Response:
```json
{
  "message": "Verification email sent. Please check your inbox.",
  "registration_id": "01J3ABC..."
}
```

**Example: Login**

```http
POST /v1/auth/login
X-Client-Key: your-client-key
Content-Type: application/json

{
  "email": "player@example.com",
  "password": "SecurePass123!"
}
```

Response:
```json
{
  "token": "1|abc123xyz789...",
  "token_type": "Bearer",
  "expires_in": 31536000,
  "user": {
    "username": "coolplayer",
    "email": "player@example.com",
    "avatar": "https://cdn.gamerprotocol.io/avatars/default.jpg",
    "level": 1,
    "xp": 0,
    "created_at": "2025-11-20T12:00:00Z"
  }
}
```

---

### 4. Account Management

User profile, statistics, alerts, and transaction history.

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| `GET` | `/account/profile` | Get full user profile | Bearer + Client Key |
| `PATCH` | `/account/profile` | Update profile (avatar, bio, socials) | Bearer + Client Key |
| `GET` | `/account/stats` | Get gameplay statistics | Bearer + Client Key |
| `GET` | `/account/stats/{titleKey}` | Get game-specific statistics | Bearer + Client Key |
| `GET` | `/account/progression` | Get levels and XP across all games | Bearer + Client Key |
| `GET` | `/account/alerts` | Get user notifications (paginated) | Bearer + Client Key |
| `POST` | `/account/alerts/read` | Mark alerts as read | Bearer + Client Key |
| `GET` | `/account/transactions` | Get financial transaction history | Bearer + Client Key |

**Example: Get Profile**

```http
GET /v1/account/profile
Authorization: Bearer 1|abc123...
X-Client-Key: your-client-key
```

Response:
```json
{
  "data": {
    "username": "coolplayer",
    "email": "player@example.com",
    "name": "Cool Player",
    "avatar": "https://cdn.gamerprotocol.io/avatars/user123.jpg",
    "bio": "Competitive gamer and chess enthusiast",
    "social_links": {
      "twitter": "https://twitter.com/coolplayer",
      "twitch": "https://twitch.tv/coolplayer"
    },
    "level": 15,
    "total_xp": 4500,
    "member_since": "2025-01-15T10:00:00Z"
  }
}
```

---

### 5. Floor Coordination

Matchmaking hub for quickplay, lobbies, challenges, and rematches.

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| `POST` | `/floor/quickplay` | Join quickplay matchmaking | Bearer + Client Key |
| `DELETE` | `/floor/quickplay` | Leave quickplay queue | Bearer + Client Key |
| `GET` | `/floor/quickplay/status` | Check queue position and estimated wait | Bearer + Client Key |
| `GET` | `/floor/lobbies` | List public lobbies | Bearer + Client Key |
| `POST` | `/floor/lobbies` | Create new lobby | Bearer + Client Key |
| `GET` | `/floor/lobbies/{ulid}` | Get lobby details | Bearer + Client Key |
| `POST` | `/floor/lobbies/{ulid}/join` | Join existing lobby | Bearer + Client Key |
| `POST` | `/floor/lobbies/{ulid}/leave` | Leave lobby | Bearer + Client Key |
| `POST` | `/floor/lobbies/{ulid}/start` | Start game from lobby (host only) | Bearer + Client Key |
| `DELETE` | `/floor/lobbies/{ulid}` | Delete lobby (host only) | Bearer + Client Key |
| `POST` | `/floor/challenges` | Issue challenge to specific user | Bearer + Client Key |
| `GET` | `/floor/challenges` | List pending challenges | Bearer + Client Key |
| `POST` | `/floor/challenges/{ulid}/accept` | Accept challenge | Bearer + Client Key |
| `POST` | `/floor/challenges/{ulid}/decline` | Decline challenge | Bearer + Client Key |
| `DELETE` | `/floor/challenges/{ulid}` | Cancel challenge | Bearer + Client Key |
| `POST` | `/floor/rematches` | Request rematch after game | Bearer + Client Key |
| `POST` | `/floor/rematches/{ulid}/accept` | Accept rematch | Bearer + Client Key |
| `POST` | `/floor/rematches/{ulid}/decline` | Decline rematch | Bearer + Client Key |

---

### 6. Active Games

Game state, action submission, and game lifecycle management.

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| `GET` | `/games` | List user's active and recent games | Bearer + Client Key |
| `GET` | `/games/{ulid}` | Get current game state | Bearer + Client Key |
| `POST` | `/games/{ulid}/actions` | Submit game action | Bearer + Client Key |
| `GET` | `/games/{ulid}/actions` | Get complete action history | Bearer + Client Key |
| `GET` | `/games/{ulid}/options` | Get valid moves for current turn | Bearer + Client Key |
| `POST` | `/games/{ulid}/concede` | Concede game (standard penalty) | Bearer + Client Key |
| `POST` | `/games/{ulid}/abandon` | Abandon game (increased penalty) | Bearer + Client Key |
| `GET` | `/games/{ulid}/replay` | Get replay data | Bearer + Client Key |

---

### 7. Economy

Balance tracking and subscription management for entertainment purposes.

> **Important**: This API tracks virtual token/chip balances for entertainment only. No real money or cryptocurrency transactions occur within this system. Balances are managed exclusively by approved client applications for their authenticated users. This platform is for entertainment purposes only and does not involve wagering or gambling.

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| `GET` | `/economy/balance` | Get all token/chip balances | Bearer + Client Key |
| `GET` | `/economy/transactions` | Get balance transaction history | Bearer + Client Key |
| `POST` | `/economy/cashier` | Add or remove tokens/chips (approved clients only) | Bearer + Client Key |
| `GET` | `/economy/plans` | List subscription plans | Bearer + Client Key |
| `POST` | `/economy/subscribe` | Start subscription | Bearer + Client Key |
| `GET` | `/economy/subscription` | Get current subscription status | Bearer + Client Key |
| `POST` | `/economy/subscription/cancel` | Cancel subscription | Bearer + Client Key |
| `POST` | `/economy/receipts/{provider}` | Verify mobile purchase | Bearer + Client Key |

**Cashier Endpoint** - For approved clients managing user balances:

```http
POST /v1/economy/cashier
Authorization: Bearer 1|abc123...
X-Client-Key: your-approved-client-key
Content-Type: application/json

{
  "action": "add",
  "amount": 100.00,
  "currency": "tokens",
  "reference": "purchase_receipt_xyz",
  "note": "Token purchase via client platform"
}
```

Response:
```json
{
  "data": {
    "transaction_ulid": "01J3EFG...",
    "action": "add",
    "amount": 100.00,
    "currency": "tokens",
    "new_balance": 250.00,
    "reference": "purchase_receipt_xyz",
    "timestamp": "2025-11-20T12:00:00Z"
  },
  "message": "Balance updated successfully"
}
```

**Supported Actions**:
- `add` - Add tokens/chips to user balance
- `remove` - Remove tokens/chips from user balance

**Supported Currencies**:
- `tokens` - Virtual tokens for game entry
- `chips` - Virtual chips for game stakes

**Access Control**:
Only approved client applications with proper authorization can use the cashier endpoint. Unauthorized access returns `403 Forbidden`.

---

### 8. Data Feeds

Real-time Server-Sent Events (SSE) streams for live platform activity.

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| `GET` | `/feeds/games` | Stream public game activity | Bearer + Client Key |
| `GET` | `/feeds/wins` | Stream win announcements | Bearer + Client Key |
| `GET` | `/feeds/leaderboards` | Stream leaderboard updates | Bearer + Client Key |
| `GET` | `/feeds/tournaments` | Stream tournament progress | Bearer + Client Key |
| `GET` | `/feeds/challenges` | Stream challenge activity | Bearer + Client Key |
| `GET` | `/feeds/achievements` | Stream achievement unlocks | Bearer + Client Key |

All feed endpoints support query parameters for filtering.

**Example: Games Feed**

```javascript
const gamesSource = new EventSource(
  'https://api.gamerprotocol.io/v1/feeds/games?title_key=chess',
  {
    headers: {
      'Authorization': 'Bearer 1|abc123...',
      'X-Client-Key': 'your-client-key'
    }
  }
);

gamesSource.addEventListener('game-update', (event) => {
  const data = JSON.parse(event.data);
  console.log('Game event:', data);
});
```

---

### 9. Competitions

Tournament management, brackets, and standings.

| Method | Endpoint | Purpose | Auth |
|--------|----------|---------|------|
| `GET` | `/competitions` | List active tournaments | Bearer + Client Key |
| `GET` | `/competitions/{ulid}` | Get tournament details | Bearer + Client Key |
| `POST` | `/competitions/{ulid}/enter` | Register for tournament | Bearer + Client Key |
| `POST` | `/competitions/{ulid}/leave` | Leave tournament (before start) | Bearer + Client Key |
| `GET` | `/competitions/{ulid}/structure` | Get tournament format rules | Bearer + Client Key |
| `GET` | `/competitions/{ulid}/bracket` | Get tournament bracket | Bearer + Client Key |
| `GET` | `/competitions/{ulid}/standings` | Get current standings | Bearer + Client Key |

---

## Common Patterns

### Response Envelope Structure

All successful API responses follow a consistent envelope structure:

**Single Resource** - Wrapped in `data` key:
```json
{
  "data": {
    "username": "coolplayer",
    "email": "player@example.com",
    "level": 15
  }
}
```

**Single Resource with Message**:
```json
{
  "data": {
    "username": "coolplayer",
    "email": "player@example.com"
  },
  "message": "Profile updated successfully"
}
```

**Collections (Resource Arrays)** - Also wrapped in `data`:
```json
{
  "data": [
    {"key": "chess", "name": "Chess"},
    {"key": "checkers", "name": "Checkers"}
  ]
}
```

**Paginated Collections** - Includes `data`, `links`, and `meta`:
```json
{
  "data": [
    {
      "ulid": "01J3ABC...",
      "title_key": "chess",
      "status": "active"
    }
  ],
  "links": {
    "first": "https://api.gamerprotocol.io/v1/games?page=1",
    "last": "https://api.gamerprotocol.io/v1/games?page=8",
    "prev": null,
    "next": "https://api.gamerprotocol.io/v1/games?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 8,
    "path": "https://api.gamerprotocol.io/v1/games",
    "per_page": 20,
    "to": 20,
    "total": 156
  }
}
```

**Message-Only Responses** - No data, just confirmation:
```json
{
  "message": "Alerts marked as read"
}
```

**Authentication Response** - Special case with flat structure:
```json
{
  "token": "1|abc123xyz789...",
  "token_type": "Bearer",
  "expires_in": 31536000,
  "user": {
    "username": "coolplayer",
    "email": "player@example.com"
  }
}
```

### Pagination

All paginated endpoints return 20 items per page by default and use the same structure shown above.

**Query Parameters**:
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20, max: 100)

**Example Request**:
```http
GET /v1/account/alerts?page=2&per_page=50
Authorization: Bearer 1|abc123...
X-Client-Key: your-client-key
```

**Pagination Metadata**:
- `current_page` - Current page number
- `from` - Index of first item on page (1-based)
- `to` - Index of last item on page
- `total` - Total number of items across all pages
- `per_page` - Items per page
- `last_page` - Total number of pages

**Pagination Links**:
- `first` - URL to first page
- `last` - URL to last page  
- `prev` - URL to previous page (null if on first page)
- `next` - URL to next page (null if on last page)

### Resource Identifiers

- **ULIDs**: All resources use 26-character ULIDs (e.g., `01J3ABC...`)
- **Usernames**: Users identified by username in URLs (e.g., `/users/coolplayer`)
- **Title Keys**: Games identified by slug keys (e.g., `chess`, `connect-four`)

### Idempotency

Game actions support idempotency keys to prevent duplicate submissions:

```json
{
  "action_type": "move",
  "action_data": {...},
  "idempotency_key": "unique-key-here"
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| `200` | OK | Successful GET/PATCH |
| `201` | Created | Successful POST creating resource |
| `202` | Accepted | Async operation queued |
| `204` | No Content | Successful DELETE |
| `400` | Bad Request | Invalid action data (structural issues) |
| `401` | Unauthorized | Missing or invalid auth token |
| `402` | Payment Required | Payment validation failed |
| `403` | Forbidden | Valid auth but insufficient permissions |
| `404` | Not Found | Resource doesn't exist |
| `409` | Conflict | Resource state conflict (player busy) |
| `422` | Unprocessable Entity | Game action denied or validation failed |
| `429` | Too Many Requests | Rate limit exceeded or cooldown active |
| `500` | Internal Server Error | Unexpected server error |

### Error Response Formats

The API uses different error response formats depending on the type of exception:

**Standard Validation Error (422)**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**Game Action Denied (422)**:
```json
{
  "message": "Invalid move: It's not your turn",
  "error_code": "NOT_PLAYER_TURN",
  "game_title": "chess",
  "severity": "error",
  "retryable": true,
  "errors": {
    "current_player": "opponent_ulid"
  }
}
```

**Invalid Action Data (400)**:
```json
{
  "message": "Missing required field: column",
  "error_code": "MISSING_REQUIRED_FIELD",
  "game_title": "connect-four",
  "errors": {
    "field": "column",
    "required_type": "integer"
  }
}
```

**Resource Not Found (404)**:
```json
{
  "message": "Game not found",
  "errors": {
    "resource_type": "Game",
    "resource_id": "01J3ABC..."
  }
}
```

**Player Busy (409)**:
```json
{
  "message": "Player is currently busy with another activity",
  "errors": {
    "activity_type": "in_game",
    "game_ulid": "01J3XYZ..."
  }
}
```

**Rate Limit Exceeded (429)**:
```json
{
  "message": "Too many requests. Please try again in 60 seconds.",
  "errors": {
    "retry_after": 60,
    "limit": 60,
    "window": "1 minute"
  }
}
```
Response includes `Retry-After` header with seconds until retry allowed.

**Payment Validation Failed (402)**:
```json
{
  "message": "Receipt validation failed",
  "errors": {
    "provider": "apple",
    "validation_error": "Invalid receipt signature"
  }
}
```

**Game Access Denied (403)**:
```json
{
  "message": "You are not a player in this game",
  "errors": {
    "game_ulid": "01J3ABC...",
    "reason": "not_participant"
  }
}
```

---

## Real-Time Events

The platform uses **Laravel Broadcasting** with **Pusher-compatible channels** for real-time updates via WebSockets.

### Key Events

| Event | Channel | Data |
|-------|---------|------|
| `MatchFound` | `private-user.{username}` | Match details, opponent info |
| `ChallengeReceived` | `private-user.{username}` | Challenge proposal |
| `YourTurn` | `private-user.{username}` | Game ULID, deadline |
| `ActionProcessed` | `private-game.{ulid}` | Action details, new state |
| `GameCompleted` | `private-game.{ulid}` | Winner, outcome, rewards |

---

## Rate Limiting

| Endpoint Type | Limit | Window |
|---------------|-------|--------|
| Authentication | 10 requests | 1 minute |
| Game Actions | 60 requests | 1 minute |
| General API | 120 requests | 1 minute |
| SSE Feeds | 10 connections | Per user |

---

## Additional Resources

- **OpenAPI Specification**: `https://api.gamerprotocol.io/openapi.yaml`
- **API Status Page**: `https://status.gamerprotocol.io`
- **Support**: developers@gamerprotocol.io

---

**Last Updated**: November 20, 2025  
**API Version**: v1.0.0
