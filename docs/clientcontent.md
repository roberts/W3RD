# 🛡️ Application Logic Specification: Content Brands & Access Control

This document specifies the core backend logic required to manage and serve two distinct content brands (**Laity** and **W3RD**) from a single API, including content gating, subscription checks, and message visibility based on the user's social graph.

## 1. Brand Separation Logic

The core logic for content separation relies on the `brand` field present on both the **`Article`** model and the authenticated **`Client`** or **`User`**.

| Component | Logic | Database Check |
| :--- | :--- | :--- |
| **Article Retrieval** | The API must filter all results from `/api/articles` to include **only** articles whose `brand` matches the brand of the requesting application/client. | **`WHERE articles.brand = requesting_client.brand`** |
| **Article Creation** | Articles created (via admin or AI generation) must be assigned a `brand` (`laity` or `w3rd`) at creation time. | **`INSERT INTO articles (brand, ...)`** |
| **User/Agent Messages** | Messages must be scoped to the article's brand. Users creating messages on a Laity article will have that message associated with the Laity brand context. | Implicit via `messages.article_id` |

---

## 2. Content Gating & Role Logic (Website Access)

This logic enforces the 7-day restriction on the website and relies on the user's **`role`** and the article's **`published_at`** timestamp.

### 2.1. Content Access Policy (`ArticlePolicy@view`)

This policy must run whenever a user attempts to access an article on the **website client**.

| Condition | Logic | Outcome |
| :--- | :--- | :--- |
| **A: Within 7 Days** | `article.published_at >= NOW() - 7 days` | **ALLOW** (Any authenticated user can view recent content.) |
| **B: Older than 7 Days** | `article.published_at < NOW() - 7 days` **AND** `user.role IN ('writer', 'editor', 'moderator')` | **ALLOW** (Staff roles bypass the gate.) |
| **C: Restricted Access** | `article.published_at < NOW() - 7 days` **AND** `user.role = 'reader'` | **DENY** (The website frontend should display a "gated" message or prompt to use the app.) |

### 2.2. Email Newsletter Subscription Logic

The API endpoint for managing newsletter subscriptions must be protected by a middleware check.

| Endpoint | Logic | Outcome |
| :--- | :--- | :--- |
| **`POST /api/subscriptions/newsletter`** | **Requires:** Authenticated user **AND** `User::hasActiveSubscription('laity_premium')`. | If true, proceed to subscribe. If false, return **HTTP 403 Forbidden**. |

---

## 3. Hybrid Interaction Model: Side Conversations & Breakout Chats

This system implements a dual-layer interaction architecture designed to keep main content clean while enabling deep, scoped discussions.

### 3.1. Nomenclature & Definitions

*   **Public Square (Comments)**: Top-level, public remarks on an Article. Visible to everyone (Brand-scoped).
*   **Side Conversations (Threads)**: Contextual message threads spawned from a specific Comment. These are "Side Conversations" because they are visible only to specific groups (Friends, Clan) or the public, depending on the initiator's choice.
*   **Breakout Chats**: The mechanism of promoting a high-activity Side Conversation into a permanent, standalone **Social Chat** (see `social.md`) to preserve history and unlock full chat features (media, invites).

### 3.2. Data Structure & Polymorphism

| Level | Component | Model | Polymorphic Relation | Visibility |
| :--- | :--- | :--- | :--- | :--- |
| **1** | **Comment** | `Comment` | `commentable_type = 'App\Models\Article'` | **Public** (Brand Scoped). |
| **2** | **Side Conversation** | `Message` | `context_type = 'App\Models\Comment'` | **Scoped** (Public, Friends, Clan). |
| **3** | **Breakout Chat** | `Chat` | *Converted from Side Conversation* | **Private/Group** (Invite only). |

### 3.3. Side Conversation Logic (Message Visibility)

The core logic defines who can view a **Side Conversation** based on the `visibility` setting of the *first message* (the thread starter) and the viewer's relationship to the starter.

#### 3.3.1. Visibility Rules

| `visibility` Value | Logic (Criteria for Allowing View) | Database Checks Required |
| :--- | :--- | :--- |
| **`public`** | Visible to everyone. | None. |
| **`friends`** | Visible to the **sender** and their **accepted friends**. | `(message.user_id = auth_user.id)` **OR** `EXISTS (SELECT * FROM friendships WHERE status='accepted' ...)` |
| **`clan`** | Visible to the **sender** and members of their **Clans**. | `(message.user_id = auth_user.id)` **OR** `EXISTS (SELECT * FROM clan_members ...)` |
| **`private`** | Visible only to **sender** and **Article Author/Agent**. | `(message.user_id = auth_user.id)` **OR** `(auth_user.role = 'agent')` |

#### 3.3.2. Performance Optimization (Caching)

To prevent N+1 query issues when loading Side Conversations:

1.  **Eager Loading:** Always eager load `sender` relationships.
2.  **Relationship Caching:** Fetch the authenticated user's `friend_ids` and `clan_ids` **once** at the start of the request.
3.  **In-Memory Filtering:** Use the cached ID lists to filter the message collection in memory.

### 3.4. Breakout Chat Implementation

When a Side Conversation becomes too active or requires privacy, participants can "Breakout" into a standalone Chat.

#### 3.4.1. Breakout Logic (`POST /comments/{id}/breakout`)

1.  **Trigger**: User clicks "Continue in Chat".
2.  **Action**:
    *   Create a new `Chat` (type: `group`).
    *   Add all unique participants from the Side Conversation as `ChatMember`s.
    *   (Optional) Copy the last N messages to the new Chat to preserve context.
    *   Post a system message in the Side Conversation: *"This conversation has moved to a [Chat](link)."*
3.  **Result**: The Side Conversation is locked (read-only), and activity moves to the new Chat resource.

### 3.5. Interaction Endpoints

| Endpoint | Method | Purpose | Logic |
| :--- | :--- | :--- | :--- |
| **`/articles/{id}/comments`** | `GET` | List top-level comments. | Public visibility. |
| **`/articles/{id}/comments`** | `POST` | Post a Comment. | Public. |
| **`/comments/{id}/messages`** | `GET` | View a Side Conversation. | Filtered by `MessagePolicy`. |
| **`/comments/{id}/messages`** | `POST` | Reply to Side Conversation. | Inherits visibility of thread. |
| **`/comments/{id}/breakout`** | `POST` | **Breakout** to Social Chat. | Creates `Chat`, migrates users. |

---

## 4. Brand Context Middleware

To ensure strict data separation between brands (Laity vs. W3RD) without repetitive controller code:

| Component | Logic |
| :--- | :--- |
| **Middleware** | `SetBrandContext` runs on every API request. |
| **Input** | Inspects the `X-Client-Key` header to identify the calling client application. |
| **Action** | 1. Resolves the `Client` model. <br> 2. Sets a singleton `Context::brand()` (e.g., 'laity'). <br> 3. Applies a **Global Scope** to `Article` and `Message` models to automatically filter by `brand = 'laity'`. |

---

## 5. Polymorphic Reactions

Reactions (Likes, Emojis) must be implemented polymorphically to support Articles, Messages, and Feed Posts uniformly.

| Model | Logic |
| :--- | :--- |
| **`Reaction`** | Uses `reactable_id` and `reactable_type` to link to any content type. |
| **Endpoints** | `POST /api/articles/{id}/reactions` <br> `POST /api/messages/{id}/reactions` |
| **Validation** | Ensures the user hasn't already reacted with the same type (if unique) or toggles the reaction. |

---

## 6. AI Content Regeneration Logic (Rank 8)

This is a privileged operation for content maintenance.

| Endpoint | Logic |
| :--- | :--- |
| **`POST /api/articles/{slug}/regenerate`** | 1. **Authorization:** Check that the requesting user has the `writer` or `editor` role via a Laravel Gate. |
| | 2. **Process Request:** Log the request with the original article ID and time. |
| | 3. **AI Task:** Dispatch a **queued job** (e.g., Laravel Queue) to the background worker. This worker will execute the Gemini CLI call, ensuring the API response is fast and the task doesn't time out. |
| | 4. **Draft Management:** The background job saves the new Markdown response to a dedicated `article_drafts` table, linked to the original article. It sends a notification to the Editor/Moderator group upon completion. |
| | 5. **API Response:** Return **HTTP 202 Accepted**, indicating the request is being processed asynchronously. |
