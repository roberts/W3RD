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

### 6. 💳 Billing & Subscriptions

| HTTP Method | Endpoint | Purpose | Auth Requirements |
| :--- | :--- | :--- | :--- |
| `GET` | `/v1/billing/plans` | Get available subscription plans | Bearer + Client Key |
| `GET` | `/v1/billing/status` | Get current plan level and renewal details | Bearer + Client Key |
| `POST` | `/v1/billing/subscribe` | Initiate subscription (returns Stripe checkout URL) | Bearer + Client Key |
| `GET` | `/v1/billing/manage` | Get Stripe customer portal URL | Bearer + Client Key |
| `POST` | `/v1/billing/apple/verify` | Verify Apple receipt | Bearer + Client Key |
| `POST` | `/v1/billing/google/verify` | Verify Google receipt | Bearer + Client Key |
| `POST` | `/v1/billing/telegram/verify` | Verify Telegram receipt | Bearer + Client Key |

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

### Response Structure Standards

All API responses follow consistent patterns based on industry best practices:

**Single Resources:**
```json
{
  "data": {...},
  "message": "Optional success message"
}
```

**Collections (Paginated):**
```json
{
  "data": [...],
  "links": {
    "first": "https://gamerprotocol.io/api/v1/games?page=1",
    "last": "https://gamerprotocol.io/api/v1/games?page=10",
    "prev": null,
    "next": "https://gamerprotocol.io/api/v1/games?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "path": "https://gamerprotocol.io/api/v1/games",
    "per_page": 20,
    "to": 20,
    "total": 200
  }
}
```

**Authentication (Special Case - Flat Structure):**
```json
{
  "token": "1|abc123...",
  "user": {...}
}
```

**Errors:**
```json
{
  "message": "Human-readable error message",
  "error_code": "VALIDATION_ERROR",
  "errors": {
    "field_name": ["Error message for this field"]
  }
}
```

**Success Messages (No Data):**
```json
{
  "message": "Action completed successfully"
}
```

---

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
    "username": "johndoe",
    "name": "John Doe",
    "avatar": "https://res.cloudinary.com/...",
    "bio": null,
    "social_links": null
  }
}
```

**GET /v1/auth/user**

Response:
```json
{
  "data": {
    "username": "johndoe",
    "name": "John Doe",
    "avatar": "https://res.cloudinary.com/...",
    "bio": "Professional gamer",
    "social_links": {
      "twitter": "https://twitter.com/johndoe"
    }
  }
}
```

**POST /v1/auth/logout**

Response:
```json
{
  "message": "Logged out successfully"
}
```

---

### User Profile & Stats

**GET /v1/me/profile**

Response:
```json
{
  "data": {
    "username": "johndoe",
    "name": "John Doe",
    "avatar": "https://res.cloudinary.com/...",
    "bio": "Professional gamer",
    "social_links": {
      "twitter": "https://twitter.com/johndoe",
      "twitch": "https://twitch.tv/johndoe"
    }
  }
}
```

**GET /v1/me/stats**

Response:
```json
{
  "data": {
    "total_games": 150,
    "wins": 95,
    "losses": 55,
    "win_rate": 63.33,
    "total_points": 2850,
    "global_rank": null
  }
}
```

**GET /v1/me/levels**

Response:
```json
{
  "data": [
    {
      "game_title": "validate-four",
      "level": 15,
      "experience_points": 3450,
      "last_played_at": "2025-11-17T10:30:00Z"
    },
    {
      "game_title": "checkers",
      "level": 8,
      "experience_points": 1200,
      "last_played_at": "2025-11-16T14:20:00Z"
    }
  ]
}
```

---

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
  "links": {
    "first": "https://gamerprotocol.io/api/v1/me/alerts?page=1",
    "last": "https://gamerprotocol.io/api/v1/me/alerts?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "https://gamerprotocol.io/api/v1/me/alerts",
    "per_page": 20,
    "to": 1,
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

---

### Public Information

**GET /v1/status**

Response:
```json
{
  "data": {
    "status": "ok"
  }
}
```

**GET /v1/titles**

Response:
```json
{
  "data": [
    {
      "key": "validate-four",
      "name": "Validate Four",
      "description": "Classic connect four game where players compete to align four pieces in a row.",
      "min_players": 2,
      "max_players": 2
    },
    {
      "key": "checkers",
      "name": "Checkers",
      "description": "Classic board game where players move pieces diagonally, capturing opponent pieces by jumping over them.",
      "min_players": 2,
      "max_players": 2
    },
    {
      "key": "hearts",
      "name": "Hearts",
      "description": "Classic 4-player card game where the goal is to avoid taking hearts and the Queen of Spades, or shoot the moon to score big.",
      "min_players": 4,
      "max_players": 4
    }
  ]
}
```

**GET /v1/titles/{gameTitle}/rules**

Response:
```json
{
  "data": {
    "title": "Validate Four",
    "objective": "Connect four pieces in a row",
    "rules": [...],
    "modes": {...},
    "timeout": {
      "timelimit_seconds": 30,
      "grace_period_seconds": 2,
      "penalty": "lose_turn"
    }
  }
}
```

**GET /v1/leaderboard/{gameTitle}**

Response:
```json
{
  "data": {
    "game_title": "validate-four",
    "entries": [
      {
        "rank": 1,
        "user": {
          "username": "progamer123",
          "name": "Pro Gamer",
          "avatar": "https://res.cloudinary.com/..."
        },
        "level": 25,
        "experience_points": 12450
      }
    ]
  }
}
```

---

### Matchmaking

**POST /v1/games/quickplay**

Request:
```json
{
  "game_title": "validate-four",
  "game_mode": "blitz"
}
```

Response (202 Accepted):
```json
{
  "data": {
    "game_title": "validate-four",
    "game_mode": "blitz"
  },
  "message": "Successfully joined the queue"
}
```

**DELETE /v1/games/quickplay**

Response (204 No Content):
```
No response body
```

**POST /v1/games/quickplay/accept**
```json
{
  "match_id": "abc123"
}
```

Response (202 Accepted):
```json
{
  "message": "Acceptance registered. Waiting for opponent..."
}
```

Or when both players accept:
```json
{
  "data": {
    "match_id": "abc123"
  },
  "message": "Match accepted! Starting game..."
}
```

---

### Game Management

**GET /v1/games**

Response:
```json
{
  "data": [
    {
      "ulid": "01HQ...",
      "game_title": "validate-four",
      "status": "active",
      "players": [...],
      "created_at": "2025-11-17T10:00:00Z"
    }
  ],
  "links": {
    "first": "https://gamerprotocol.io/api/v1/games?page=1",
    "last": "https://gamerprotocol.io/api/v1/games?page=5",
    "prev": null,
    "next": "https://gamerprotocol.io/api/v1/games?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "https://gamerprotocol.io/api/v1/games",
    "per_page": 20,
    "to": 20,
    "total": 95
  }
}
```

**GET /v1/games/{gameUlid}**

Response:
```json
{
  "data": {
    "ulid": "01HQ...",
    "game_title": "validate-four",
    "status": "active",
    "game_state": {
      "board": [[null, null, ...], ...],
      "currentPlayerUlid": "01HQ...",
      "winnerUlid": null
    },
    "players": [
      {
        "ulid": "01HQ...",
        "user": {
          "username": "player1",
          "name": "Player One",
          "avatar": "https://res.cloudinary.com/..."
        },
        "position_id": 1
      }
    ],
    "created_at": "2025-11-17T10:00:00Z",
    "started_at": "2025-11-17T10:01:00Z"
  }
}
```

**GET /v1/games/{gameUlid}/history**

Response:
```json
{
  "data": [
    {
      "ulid": "01HQ...",
      "action_type": "drop_piece",
      "action_details": {"column": 3},
      "player": {
        "username": "player1",
        "name": "Player One"
      },
      "created_at": "2025-11-17T10:02:00Z"
    }
  ]
}
```

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
  "data": {
    "action": {
      "ulid": "01HQ..."
    },
    "game": {
      "ulid": "01HQ...",
      "status": "active",
      "game_state": {
        "board": [...],
        "currentPlayerUlid": "01HQ...",
        "winnerUlid": null,
        "is_draw": false,
        "finish_reason": null
      },
      "winner_ulid": null,
      "is_draw": false,
      "finish_reason": null
    },
    "next_action_deadline": "2025-11-17T10:03:00Z",
    "timeout": {
      "timelimit_seconds": 30,
      "grace_period_seconds": 2,
      "penalty": "lose_turn"
    }
  },
  "message": "Action applied successfully"
}
```

**GET /v1/games/{gameUlid}/options**

Response:
```json
{
  "data": {
    "options": [
      {
        "action_type": "drop_piece",
        "action_details": {"column": 0},
        "description": "Drop piece in column 0"
      },
      {
        "action_type": "drop_piece",
        "action_details": {"column": 1},
        "description": "Drop piece in column 1"
      }
    ],
    "is_your_turn": true,
    "phase": "active",
    "deadline": "2025-11-17T10:03:00Z",
    "timelimit_seconds": 30
  }
}
```

**POST /v1/games/{gameUlid}/forfeit**

Response:
```json
{
  "data": {
    "ulid": "01HQ...",
    "status": "completed",
    "winner_id": 123,
    "finished_at": "2025-11-17T10:05:00Z"
  },
  "message": "Game forfeited successfully."
}
```

---

### Rematch System

**POST /v1/games/{gameUlid}/rematch**

Response:
```json
{
  "data": {
    "ulid": "01HQ...",
    "status": "pending",
    "requester_ulid": "01HQ...",
    "opponent_ulid": "01HQ...",
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
    "ulid": "01HQ...",
    "status": "accepted",
    "new_game_ulid": "01HQ...",
    "accepted_at": "2025-11-17T10:30:00Z"
  },
  "message": "Rematch accepted. New game created."
}
```

**POST /v1/games/rematch/{requestId}/decline**

Response:
```json
{
  "data": {
    "ulid": "01HQ...",
    "status": "declined",
    "declined_at": "2025-11-17T10:30:00Z"
  },
  "message": "Rematch request declined"
}
```

---

### Lobby Management

**GET /v1/games/lobbies**

Response:
```json
{
  "data": [
    {
      "ulid": "01HQ...",
      "game_title": "validate-four",
      "host": {
        "username": "johndoe",
        "name": "John Doe",
        "avatar": "https://res.cloudinary.com/..."
      },
      "min_players": 2,
      "current_players": 1,
      "status": "pending"
    }
  ]
}
```

**POST /v1/games/lobbies**
```json
{
  "game_title": "validate-four",
  "game_mode": "standard",
  "is_public": true,
  "min_players": 2
}
```

Response (201 Created):
```json
{
  "data": {
    "ulid": "01HQ...",
    "game_title": "validate-four",
    "is_public": true,
    "min_players": 2,
    "status": "pending",
    "host": {
      "username": "johndoe",
      "name": "John Doe",
      "avatar": "https://res.cloudinary.com/..."
    },
    "players": [
      {
        "username": "johndoe",
        "name": "John Doe",
        "avatar": "https://res.cloudinary.com/...",
        "status": "accepted"
      }
    ]
  },
  "message": "Lobby created successfully"
}
```

**GET /v1/games/lobbies/{lobby_ulid}**

Response:
```json
{
  "data": {
    "lobby": {
      "ulid": "01HQ...",
      "game_title": "validate-four",
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
    },
    "game": {
      "ulid": "01HQ..."
    }
  }
}
```

Note: The `game` object is only present if the lobby has transitioned to `completed` status and a game has been created.

**POST /v1/games/lobbies/{lobby_ulid}/ready-check**

Response (202 Accepted):
```json
{
  "data": {
    "ready_check_initiated": true
  },
  "message": "Ready check initiated"
}
```

---

### Billing & Subscriptions

**GET /v1/billing/plans**

Response:
```json
{
  "data": [
    {
      "id": "basic",
      "name": "Basic",
      "price": 4.99,
      "features": [...]
    },
    {
      "id": "pro",
      "name": "Pro",
      "price": 9.99,
      "features": [...]
    }
  ]
}
```

**GET /v1/billing/status**

Response (with active subscription):
```json
{
  "data": {
    "subscription": {
      "plan": "pro",
      "status": "active",
      "current_period_end": "2025-12-17T00:00:00Z"
    }
  }
}
```

Response (without subscription):
```json
{
  "data": {
    "subscription": null
  }
}
```

**POST /v1/billing/subscribe**
```json
{
  "plan": "pro",
  "success_url": "https://yourapp.com/success",
  "cancel_url": "https://yourapp.com/cancel"
}
```

Response:
```json
{
  "data": {
    "checkout_url": "https://checkout.stripe.com/..."
  }
}
```

**GET /v1/billing/manage**

Response:
```json
{
  "data": {
    "portal_url": "https://billing.stripe.com/..."
  }
}
```

**POST /v1/billing/apple/verify**
```json
{
  "receipt_data": "base64_encoded_receipt..."
}
```

Response:
```json
{
  "data": {
    "verified": true,
    "subscription": {
      "plan": "pro",
      "expires_at": "2025-12-17T00:00:00Z"
    }
  }
}
```

**POST /v1/billing/google/verify**
```json
{
  "purchase_token": "token_from_google_play..."
}
```

Response:
```json
{
  "data": {
    "verified": true,
    "subscription": {
      "plan": "pro",
      "expires_at": "2025-12-17T00:00:00Z"
    }
  }
}
```

**POST /v1/billing/telegram/verify**
```json
{
  "payment_id": "telegram_payment_id..."
}
```

Response:
```json
{
  "data": {
    "verified": true,
    "subscription": {
      "plan": "pro",
      "expires_at": "2025-12-17T00:00:00Z"
    }
  }
}
```

---

## Error Response Examples

### Validation Error (422 Unprocessable Entity)
```json
{
  "message": "The given data was invalid.",
  "error_code": "VALIDATION_ERROR",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

### Not Found (404)
```json
{
  "message": "Game not found",
  "error_code": "RESOURCE_NOT_FOUND"
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated.",
  "error_code": "UNAUTHENTICATED"
}
```

### Forbidden (403)
```json
{
  "message": "You are not authorized to perform this action.",
  "error_code": "FORBIDDEN"
}
```

### Game Logic Error (400)
```json
{
  "message": "Invalid move: Column is full",
  "error_code": "INVALID_GAME_ACTION"
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