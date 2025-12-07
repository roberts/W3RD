# **Social API Specification (V1)**

Namespace: /api/v1/social  
Authentication: All endpoints require user authentication (auth:sanctum), unless explicitly noted as PUBLIC.

## **1. User & Profile Management**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/users/{username} | View a user's public profile. | **PUBLIC.** Returns UserResource (BHS score, public stats, badges). |
| **POST** | /social/users/{username}/block | Block/Mute another user. | **Rec #4 (Safety).** Adds user_id to the authenticated user's block list. Blocks future DMs/friend requests/clan invitations. |
| **GET** | /social/users/{id}/audit | Public audit log. | **Rec #9 (Trust).** Lists verifiable activity: successful Bounty PR merges, disputes opened/resolved, total earnings/spend. |
| **GET** | /social/me/dashboard | Dashboard summary (Auth user). | Returns customized DashboardResource (stats, pending requests). |
| **PATCH** | /social/me/status | Update user presence (online/away). | Updates the user's real-time presence status (Rec #6). |

## **2. Friends Management (/social/friends)**

This system implements the X-like "Friends" and "Requests" tabs to control inbox spam.

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/friends | List current friends & pending requests. | Returns two lists: accepted (friends) and pending_inbound (requests to me). |
| **POST** | /social/friends | Send a friend request. | **Request Body:** {"recipient\_id": 123}. Creates a FriendRequest model. |
| **PATCH** | /social/friends/{request_id} | Accept or reject a request. | **Request Body:** \`{"action": "accept" |
| **DELETE** | /social/friends/{id} | Remove a friend. | Removes both directions of the friendship link. |

## **3. Clan Management (/social/clans)**

This establishes the team structure for bounty hunting groups.

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/clans | List all public/searchable clans. | Accepts filters: type (public, invite_only), min_members, search). |
| **POST** | /social/clans | Create a new clan. | **Request Body:** `{"name": "...", "type": "public" |
| **GET** | /social/clans/{id} | View clan details & members. | Includes leader, member list, and clan stats (leaderboard data, average BHS). |
| **POST** | /social/clans/{id}/join | Join a clan (based on type). | **Logic:** If public, joins directly. If application_driven, creates a ClanApplication. If invite_only, fails unless invited. |
| **DELETE** | /social/clans/{id}/leave | Leave a clan. | Clan Leader cannot leave until ownership is transferred. |
| **POST** | /social/clans/{id}/invite | Invite user to an invite-only clan. | **Request Body:** {"user_id": 123}. Creates a pending invitation. |

## **4. Messaging & Chat (/social/chats & /social/messages)**

This system handles both structured chats (linked to bounties) and direct/group messaging.

### **4.1. Chat Endpoints**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/chats | List user's active chats. | **Filter:** Use `?status=open |
| **POST** | /social/chats | Create a new Chat. | **Request Body:** {"participant_ids": [1, 2, ...], "bid_id": 456 (optional)}. Creates a Group Chat. |
| **GET** | /social/chats/{id}/members | List chat participants. | Useful for displaying member count/names in a group/bounty chat. |

### **4.2. Message Endpoints**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/messages | List messages. | Requires chat_id as a query parameter. |
| **POST** | /social/messages | Send a message. | **Request Body:** (See below). Logic determines if it's a DM (friend-status check) or Chat message. **Rec #3 (Media Support)** included. |

Message Creation Request Body (JSON/Multipart):  
| Field | Type | Required | Validation Notes |  
| :--- | :--- | :--- | :--- |  
| content | string | YES/NO | Text content (required if no file). |  
| chat_id | integer | NO | Required for Group/Bounty Chat. Nullable for DMs. |  
| recipient_id| integer | NO | Required for DMs. If chat\_id is null, uses this ID. |  
| file | file | NO | Rec #3 (Media). Max 10MB (image/zip/pdf). Handled via Multipart Form Data. |

## **5. Public Timeline (/social/timeline)**

The social media-style public posting feature.

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **GET** | /social/timeline | Global timeline feed. | **PUBLIC.** Accepts pagination and filtering (e.g., ?by_clan=123). |
| **POST** | /social/timeline | Create a new public post. | **Request Body:** (See below). |
| **POST** | /social/timeline/{id}/like | Like/Unlike a post. | Toggle action. |
| **POST** | /social/timeline/{id}/comment | Comment on a post. | Creates a nested Comment resource. |

Post Creation Request Body (Multipart/JSON):  
| Field | Type | Required | Validation Notes |  
| :--- | :--- | :--- | :--- |  
| content_markdown | string | YES | The main post text (supports Markdown). |  
| link_url | url | NO | Optional link (e.g., YouTube video, GitHub repo). Must be a valid URL. |  
| image | file | NO | Optional image upload. |

## **6. AI & Utility Endpoints**

### **6.1. AI Agent Interaction**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **POST** | /social/prompts | Send a new prompt to an AI agent. | **Request Body:** {"chat_id": 456, "prompt_text": "..."}. Creates a Prompt record and dispatches a Job to the AI processing queue. |
| **GET** | /social/responses | List responses for a chat. | Requires chat_id filter. Responses are added asynchronously by the AI processing Job. |
| **POST** | /social/ai-templates | Share/Save AI Prompt Templates. | **Rec #7 (Template Sharing).** Allows users to save complex prompts for reuse (potential future monetization). |

### **6.2. Economy-Tied Social Features**

| HTTP Method | Endpoint | Action | Logic / Validation Notes |
| :---- | :---- | :---- | :---- |
| **POST** | /economy/gift | Send Connects/Crypto to a user. | **Rec #8 (Gifting).** **Request Body:** `{"recipient_id": 123, "amount": 100, "currency": "connects" |

