# GamerProtocol.io API Documentation

Complete API reference for the GamerProtocol.io platform. All endpoints use the base path `/v1/`.

**Required Headers for Authorization:**
- `Authorization: Bearer [Sanctum User Token]`
- `X-Client-Key: [API Key from clients table]` (For application authorization)

---

## 📋 Table of Contents

1. [Implemented Endpoints](#implemented-endpoints)
   - [Authentication & User Management](#1-authentication--user-management)
   - [Public Information](#2-public-information)
   - [User Profile & Stats](#3-user-profile--stats)
   - [Matchmaking](#4-matchmaking)
   - [Game Management](#5-game-management)
   - [Billing & Subscriptions](#6-billing--subscriptions)
   - [Webhooks](#7-webhooks)
2. [Future Endpoints (Not Yet Implemented)](#future-endpoints-not-yet-implemented)

---

## Implemented Endpoints

### 1. 🔑 Authentication & User Management

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `POST` | `/v1/auth/register` | Standard registration - creates pending registration and sends verification email | `X-Client-Key` Only |
| `POST` | `/v1/auth/verify` | Verify email - validates token, creates user, returns login token | `X-Client-Key` Only |
| `POST` | `/v1/auth/login` | Standard login with email/password | `X-Client-Key` Only |
| `POST` | `/v1/auth/social` | Social login with provider access token | `X-Client-Key` Only |
| `POST` | `/v1/auth/logout` | User logout - revokes current API token | Bearer + Client Key |
| `GET` | `/v1/auth/user` | Get authenticated user profile | Bearer + Client Key |
| `PATCH` | `/v1/auth/user` | Update authenticated user profile | Bearer + Client Key |

---

### 2. 🌐 Public Information

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `GET` | `/v1/status` | API health check | Public |
| `GET` | `/v1/titles` | List all available game titles with metadata | Public |
| `GET` | `/v1/titles/{gameTitle}/rules` | Get rules for a specific game title | Public |
| `GET` | `/v1/leaderboard/{gameTitle}` | Get leaderboard for a specific game title | Public |

---

### 3. 👤 User Profile & Stats

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `GET` | `/v1/me/profile` | Get authenticated user's detailed profile | Bearer + Client Key |
| `PATCH` | `/v1/me/profile` | Update authenticated user's profile | Bearer + Client Key |
| `GET` | `/v1/me/stats` | Get user statistics (wins, losses, points, rank) | Bearer + Client Key |
| `GET` | `/v1/me/levels` | Get user's title levels and XP progress | Bearer + Client Key |
| `GET` | `/v1/me/alerts` | Get user's alert history (paginated) | Bearer + Client Key |
| `POST` | `/v1/me/alerts/mark-as-read` | Mark alerts as read (specific ULIDs or all) | Bearer + Client Key |

---

### 4. 🎮 Matchmaking

#### Quickplay (Public Matchmaking)

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `POST` | `/v1/games/quickplay` | Join quickplay matchmaking queue | Bearer + Client Key |
| `DELETE` | `/v1/games/quickplay` | Leave quickplay matchmaking queue | Bearer + Client Key |
| `POST` | `/v1/games/quickplay/accept` | Accept a quickplay match | Bearer + Client Key |

#### Lobbies (Private Matchmaking)

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `GET` | `/v1/games/lobbies` | List available lobbies | Bearer + Client Key |
| `POST` | `/v1/games/lobbies` | Create a new lobby | Bearer + Client Key |
| `GET` | `/v1/games/lobbies/{lobby_ulid}` | Get lobby details | Bearer + Client Key |
| `DELETE` | `/v1/games/lobbies/{lobby_ulid}` | Delete/leave a lobby | Bearer + Client Key |
| `POST` | `/v1/games/lobbies/{lobby_ulid}/ready-check` | Initiate ready check for lobby | Bearer + Client Key |
| `POST` | `/v1/games/lobbies/{lobby_ulid}/players` | Add player to lobby | Bearer + Client Key |
| `PUT` | `/v1/games/lobbies/{lobby_ulid}/players/{username}` | Update player status in lobby | Bearer + Client Key |
| `DELETE` | `/v1/games/lobbies/{lobby_ulid}/players/{username}` | Remove player from lobby | Bearer + Client Key |

---

### 5. 🎯 Game Management

#### Game State & Actions

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `GET` | `/v1/games` | List authenticated user's active and recent games | Bearer + Client Key |
| `GET` | `/v1/games/{gameUlid}` | Get current game state by ULID | Bearer + Client Key |
| `GET` | `/v1/games/{gameUlid}/history` | Get full action history for game (replay) | Bearer + Client Key |
| `POST` | `/v1/games/{gameUlid}/action` | Execute a game action | Bearer + Client Key |
| `GET` | `/v1/games/{gameUlid}/options` | Get list of valid actions for current state | Bearer + Client Key |
| `POST` | `/v1/games/{gameUlid}/forfeit` | Forfeit/concede a game | Bearer + Client Key |

#### Rematch System

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `POST` | `/v1/games/{gameUlid}/rematch` | Request rematch with same opponent | Bearer + Client Key |
| `POST` | `/v1/games/rematch/{requestId}/accept` | Accept a rematch request (by ULID) | Bearer + Client Key |
| `POST` | `/v1/games/rematch/{requestId}/decline` | Decline a rematch request (by ULID) | Bearer + Client Key |

---

### 6. � Billing & Subscriptions

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `GET` | `/v1/billing/plans` | Get available subscription plans | Bearer + Client Key |
| `GET` | `/v1/billing/status` | Get current plan level and renewal details | Bearer + Client Key |
| `POST` | `/v1/billing/subscribe` | Initiate subscription (returns Stripe checkout URL) | Bearer + Client Key |
| `GET` | `/v1/billing/manage` | Get Stripe customer portal URL | Bearer + Client Key |
| `POST` | `/v1/billing/{provider}/verify` | Verify mobile receipt (Apple/Google) | Bearer + Client Key |

---

### 7. 🔗 Webhooks

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `POST` | `/v1/stripe/webhook` | Stripe webhook for subscription events | None (Vendor Auth) |

---

## Future Endpoints (Not Yet Implemented)

The following endpoints are documented for future development but not yet implemented in the codebase.

### 🎖️ Gamification

| HTTP Method | Endpoint | Purpose | Priority |
| :--- | :--- | :--- | :--- |
| `GET` | `/v1/user/badges` | Get user's earned badges | Medium |
| `GET` | `/v1/leaderboard` | Global leaderboard (all games) | Low |
| `GET` | `/v1/leaderboard/daily/history/{date}` | Historical daily leaderboard | Low |

### ⚙️ Platform Utilities

| HTTP Method | Endpoint | Purpose | Priority |
| :--- | :--- | :--- | :--- |
| `GET` | `/v1/config` | Get platform configuration data | Medium |
| `GET` | `/v1/time` | Get server time for timezone sync | Medium |

### 💰 Advanced Billing

| HTTP Method | Endpoint | Purpose | Priority |
| :--- | :--- | :--- | :--- |
| `GET` | `/v1/billing/payment-methods` | List saved payment methods | Low |
| `POST` | `/v1/billing/payment-methods` | Add new payment method | Low |
| `DELETE` | `/v1/billing/subscription` | Cancel subscription | Medium |

---

## Request & Response Examples

### Authentication

**POST /v1/auth/login**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

Response:
```json
{
  "token": "1|abc123...",
  "user": {
    "ulid": "01HQ...",
    "username": "johndoe",
    "name": "John Doe",
    "email": "user@example.com",
    "avatar": "https://res.cloudinary.com/..."
  }
}
```

### Alerts

**GET /v1/me/alerts**

Response:
```json
{
  "data": [
    {
      "ulid": "01HQ...",
      "type": "game_invite",
      "data": {
        "lobby_ulid": "01HQ...",
        "host_username": "player1"
      },
      "read_at": null,
      "created_at": "2025-11-17T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

**POST /v1/me/alerts/mark-as-read**
```json
{
  "alert_ulids": ["01HQ...", "01HQ..."]
}
```

Response:
```json
{
  "message": "Alerts marked as read."
}
```

### Game Action

**POST /v1/games/{gameUlid}/action**
```json
{
  "action_type": "drop_piece",
  "action_details": {
    "column": 3
  }
}
```

Response:
```json
{
  "action": {
    "ulid": "01HQ...",
    "action_type": "drop_piece",
    "action_details": {"column": 3}
  },
  "game_state": {
    "status": "active",
    "current_turn": 2,
    "board": [[],[],[],...]
  },
  "message": "Action recorded successfully"
}
```

### Rematch System

**POST /v1/games/{gameUlid}/rematch**

Response:
```json
{
  "data": {
    "ulid": "01HQ...",
    "status": "pending",
    "expires_at": "2025-11-17T11:00:00Z"
  },
  "message": "Rematch request sent."
}
```

**POST /v1/games/rematch/{requestId}/accept**

Response:
```json
{
  "data": {
    "rematch_request_ulid": "01HQ...",
    "new_game_ulid": "01HQ...",
    "status": "accepted"
  },
  "message": "Rematch accepted. New game created."
}
```

### Lobby Management

**POST /v1/games/lobbies**
```json
{
  "game_title": "connect-four",
  "game_mode": "standard",
  "is_public": true,
  "min_players": 2
}
```

Response:
```json
{
  "lobby": {
    "ulid": "01HQ...",
    "game_title": "connect-four",
    "game_mode": "standard",
    "host": {
      "username": "johndoe",
      "name": "John Doe",
      "avatar": "https://res.cloudinary.com/..."
    },
    "is_public": true,
    "min_players": 2,
    "status": "pending",
    "players": [
      {
        "username": "johndoe",
        "name": "John Doe",
        "avatar": "https://res.cloudinary.com/...",
        "status": "accepted"
      }
    ]
  }
}
```

---

## Important Notes

### ULID Usage
All resources (Games, Lobbies, Actions, Alerts, RematchRequests) use **ULIDs** as public identifiers instead of internal database IDs. ULIDs are:
- Lexicographically sortable
- URL-safe
- 26-character strings (e.g., `01HQ5X9K3G2YM4N6P7Q8R9S0T1`)

### Username-based User Identification
User resources are identified by **username** in API endpoints (e.g., `/lobbies/{lobby_ulid}/players/{username}`), not by user ID. This:
- Prevents user enumeration attacks
- Provides a consistent, human-readable identifier
- Maintains security while enabling social features

### Authentication
All authenticated endpoints return:
- `401 Unauthorized` if the Bearer token is missing or invalid
- `403 Forbidden` if the user is not authorized to access the resource
- `404 Not Found` if the resource ULID doesn't exist

### WebSocket Events
The platform uses Laravel Broadcasting for real-time updates. Key events include:
- `GameActionProcessed` - Broadcasts game actions with `action_ulid`
- `LobbyInvitation` - Notifies users of lobby invites
- `LobbyReadyCheck` - Initiates ready check for lobby players
- `RematchRequested` - Notifies opponent of rematch request

---

**Note:** All authenticated endpoints return `401 Unauthorized` if the Bearer token is missing or invalid. Game-specific endpoints return `404 Not Found` if the game ULID doesn't exist or `403 Forbidden` if the user is not a participant.