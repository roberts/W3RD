# **Social API Specification (V1)**

Namespace: /api/v1/social  
Authentication: All endpoints require user authentication (auth:sanctum), unless explicitly noted as PUBLIC.

## **1. User & Profile Management**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/users/{username} | View a user's public profile. | **PUBLIC.** Returns UserResource (BHS score, public stats, badges). |
| **POST** | /social/users/{username}/block | Block/Mute another user. | **Rec #4 (Safety).** Adds user_id to the authenticated user's block list. Blocks future DMs/friend requests/clan invitations. |
| **GET** | /social/users/{id}/audit | Public audit log. | **Rec #9 (Trust).** Lists verifiable activity: successful Bounty PR merges, disputes opened/resolved, total earnings/spend. |
| **POST** | /social/users/{user}/vouch | Vouch for a user. | **Rec #8 (Trust).** Endorse a user after a match/transaction (e.g., "Good Teammate"). |

## **2. Friends Management (/social/friends)**

This system implements the X-like "Friends" and "Requests" tabs to control inbox spam.

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/friends | List current friends & pending requests. | Returns two lists: accepted (friends) and pending_inbound (requests to me). |
| **POST** | /social/friends | Send a friend request. | **Request Body:** {"recipient\_id": 123}. Creates a FriendRequest model. |
| **PATCH** | /social/friends/{request_id} | Accept or reject a request. | **Request Body:** \`{"action": "accept" |
| **DELETE** | /social/friends/{id} | Remove a friend. | Removes both directions of the friendship link. |
| **PATCH** | /social/friends/{friend} | Set alias/note. | **Rec #10 (UX).** Set a private note/alias for a friend (e.g., "Mike from Discord"). |

## **3. Clan Management (/social/clans)**

This establishes the team structure for bounty hunting groups.

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/clans | List all public/searchable clans. | Accepts filters: type (public, invite_only), min_members, search). |
| **POST** | /social/clans | Create a new clan. | **Request Body:** `{"name": "...", "type": "public" |
| **GET** | /social/clans/{id} | View clan details & members. | Includes leader, member list, and clan stats (leaderboard data, average BHS). |
| **POST** | /social/clans/{id}/join | Join a clan (based on type). | **Logic:** If public, joins directly. If application_driven, creates a ClanApplication. If invite_only, fails unless invited. |
| **DELETE** | /social/clans/{id}/leave | Leave a clan. | Clan Leader cannot leave until ownership is transferred. |
| **POST** | /social/clans/{id}/invite | Invite user to an invite-only clan. | **Request Body:** {"user_id": 123}. **Rec #6 (Roles):** Officers and Leaders can invite/kick. |

## **4. Messaging & Chat (/social/chats)**

This system handles both structured chats (linked to bounties) and direct/group messaging.

### **4.1. Chat Endpoints**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/chats | List user's active chats. | **Filter:** Use `?status=open |
| **POST** | /social/chats | Create a new Chat. | **Rec #7 (Lifecycle):** DMs are persistent. Lobby/Match chats are ephemeral (auto-delete after 1h). |
| **GET** | /social/chats/{id}/members | List chat participants. | Useful for displaying member count/names in a group/bounty chat. |

### **4.2. Message Endpoints**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/chats/{chat}/messages | List messages for a chat. | Returns paginated MessageResources. |
| **POST** | /social/chats/{chat}/messages | Send a message to a chat. | **Request Body:** (See below). **Rec #3 (Media Support)** included. |

Message Creation Request Body (JSON/Multipart):  
| Field | Type | Required | Validation Notes |  
| :--- | :--- | :--- | :--- |  
| content | string | YES/NO | Text content (required if no file). |  
| file | file | NO | Rec #3 (Media). Max 10MB (image/zip/pdf). Handled via Multipart Form Data. |

### **4.3. Chat Commands (AI Agents)**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **POST** | /social/chats/{chat}/commands | Trigger an Agent action. | **Rec #11 (Agents).** Executes a preset command or template. Agent replies via standard Message stream. |

### **4.4. Chat Types & Context**

To support diverse interactions (DMs, Game Lobbies, Article Breakouts), the Chat model includes `type` and polymorphic `context` fields.

#### **4.4.1. Chat Types (Enum)**

| Type | Description | Membership Logic |
| :--- | :--- | :--- |
| **`direct`** | 1-on-1 private conversation. | Max 2 members. |
| **`group`** | Standard multi-user chat. | Invite-only. |
| **`match`** | Ephemeral game lobby. | System-managed (Matchmaking). Auto-deletes. |
| **`breakout`** | Discussion spawned from content. | System-created from Side Conversations. |

#### **4.4.2. Contextual Chat Logic**

Chats can be linked to a source object (e.g., an Article, a Game, or a Bounty) via polymorphic relations (`context_type`, `context_id`).

*   **Breakout Chats:** Created automatically when a Side Conversation (see `clientcontent.md`) is promoted.
    *   **Title:** Auto-generated from the source (e.g., "Discussion: [Article Title]").
    *   **Initial Members:** All participants from the original thread.
    *   **System Message:** The first message is a system-generated link back to the source content.

## **5. Posts & Feed (/social/posts & /social/feed)**

The social media-style public posting feature.

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/feed | Global timeline feed. | **Rec #2 (Engagement).** Includes user posts AND system events (e.g., "User X won Tournament Y"). |
| **POST** | /social/posts | Create a new public post. | **Request Body:** (See below). |
| **GET** | /social/posts/{post} | View a single post. | **PUBLIC.** Returns PostResource with comments/reactions count. |
| **POST** | /social/posts/{post}/reactions | React to a post. | **Rec #9 (Fun).** Toggle reaction (🔥, 👏, 😂, 💸). Replaces binary "Like". |
| **POST** | /social/posts/{post}/comments | Comment on a post. | Creates a nested Comment resource. |
| **POST** | /social/reports | Report content/user. | **Rec #3 (Safety).** Report User, Post, or Chat. Body: `target_type`, `target_id`, `reason`. |

Post Creation Request Body (Multipart/JSON):  
| Field | Type | Required | Validation Notes |  
| :--- | :--- | :--- | :--- |  
| content_markdown | string | YES | The main post text (supports Markdown). |  
| link_url | url | NO | Optional link (e.g., YouTube video, GitHub repo). Must be a valid URL. |  
| image | file | NO | Optional image upload. |

## **6. Economy-Tied Social Features**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **POST** | /economy/transfers | Send Connects/Crypto to a user. | **Rec #8 (Gifting).** **Request Body:** `{"recipient_id": 123, "amount": 100, "currency": "connects" |

## **7. Account Management (Social Extensions)**

These endpoints belong to the Account namespace but support social features.

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /account/templates | List saved command templates. | **Rec #7 (Templates).** User's saved presets for AI commands. |
| **POST** | /account/templates | Create a new template. | Save a complex prompt/command configuration for reuse. |
| **PATCH** | /account/status | Update user presence. | **Rec #4 (Rich Presence).** Set status (online/away) and activity (looking_for_match). |

