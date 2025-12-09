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
| **`POST /api/subscriptions/newsletter`** | **Requires:** Authenticated user **AND** `user.is_paid_subscriber = TRUE`. | If true, proceed to subscribe. If false, return **HTTP 403 Forbidden**. |

---

## 3. Message Visibility Logic (Core Social Graph Enforcement)

The most complex logic is defining who can view a **`message`** based on its `visibility` setting and the current user's relationships (`friends`, `clans`). This must be implemented via a robust **`MessagePolicy@view`** or a query scope on the `Message` model.

| `visibility` Value | Logic (Criteria for Allowing View) | Database Checks Required |
| :--- | :--- | :--- |
| **`public`** | Always allow viewing by any authenticated user. | None (Default behavior) |
| **`private`** | Allow viewing if the current user is the **sender** or a designated **agent/author** of the article. | `(message.user_id = auth_user.id)` **OR** `(auth_user.role = 'agent' AND article_id is NOT NULL)` |
| **`friends`** | Allow viewing if the current user is the **sender** or has an **accepted friendship** with the sender. | `(message.user_id = auth_user.id)` **OR** `EXISTS (SELECT * FROM friendships WHERE status='accepted' AND sender/recipient IDs match)` |
| **`clan`** | Allow viewing if the current user is the **sender** or shares at least one **Clan** with the sender. | `(message.user_id = auth_user.id)` **OR** `EXISTS (SELECT * FROM clan_members cm1, clan_members cm2 WHERE cm1.clan_id = cm2.clan_id AND cm1.user_id = auth_user.id AND cm2.user_id = message.user_id)` |
| **Global Rule** | **Block Check:** If the current user has explicitly **blocked** the message sender, the message must **never** be returned, regardless of the visibility setting. | `NOT EXISTS (SELECT * FROM friendships WHERE status='blocked' AND sender_id = auth_user.id AND recipient_id = message.user_id)` |



---

## 4. AI Content Regeneration Logic (Rank 8)

This is a privileged operation for content maintenance.

| Endpoint | Logic |
| :--- | :--- |
| **`POST /api/articles/{slug}/regenerate`** | 1. **Authorization:** Check that the requesting user has the `writer` or `editor` role via a Laravel Gate. |
| | 2. **Process Request:** Log the request with the original article ID and time. |
| | 3. **AI Task:** Dispatch a **queued job** (e.g., Laravel Queue) to the background worker. This worker will execute the Gemini CLI call, ensuring the API response is fast and the task doesn't time out. |
| | 4. **Draft Management:** The background job saves the new Markdown response to a dedicated `article_drafts` table, linked to the original article. It sends a notification to the Editor/Moderator group upon completion. |
| | 5. **API Response:** Return **HTTP 202 Accepted**, indicating the request is being processed asynchronously. |
