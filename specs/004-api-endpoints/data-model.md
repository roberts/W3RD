# Data Model: Core API Endpoints

This document outlines the database schema changes required to support the new Core API Endpoints feature.

## 1. New Tables

### `notifications`

This table will store notifications for users, such as game invites, billing issues, or friend requests.

| Column        | Type        | Modifiers                               | Description                                      |
| ------------- | ----------- | --------------------------------------- | ------------------------------------------------ |
| `id`          | `ulid`      | `primary`                               | Primary key for the notification.                |
| `user_id`     | `foreignId` | `constrained()->onDelete('cascade')`    | The user who receives the notification.          |
| `type`        | `string`    |                                         | The type of notification (e.g., `billing_issue`). |
| `data`        | `json`      |                                         | A payload containing notification-specific data. |
| `read_at`     | `timestamp` | `nullable`                              | When the user marked the notification as read.   |
| `created_at`  | `timestamp` |                                         |                                                  |
| `updated_at`  | `timestamp` |                                         |                                                  |

**Example `data` payload for a `billing_issue` type:**

```json
{
  "message": "Your subscription payment failed. Please update your payment method.",
  "action_url": "/billing/manage"
}
```

## 2. Modified Tables

### `users`

The `users` table will be updated to include fields for the user's public profile.

| Column         | Type     | Modifiers  | Description                                      |
| -------------- | -------- | ---------- | ------------------------------------------------ |
| ...            | ...      | ...        | ...                                              |
| `bio`          | `text`   | `nullable` | A short biography for the user's public profile. |
| `social_links` | `json`   | `nullable` | JSON object to store links to social media profiles. |

**Example `social_links` JSON:**

```json
{
  "twitter": "https://twitter.com/username",
  "website": "https://example.com"
}
```

### `subscriptions`

The `subscriptions` table (from Laravel Cashier) needs modification to support multiple payment providers and lifetime memberships.

| Column      | Type     | Modifiers  | Description                                                                 |
| ----------- | -------- | ---------- | --------------------------------------------------------------------------- |
| ...         | ...      | ...        | ...                                                                         |
| `provider`  | `string` | `nullable` | The source of the subscription (e.g., `stripe`, `apple`, `google`, `admin`). |
| `ends_at`   | `timestamp`| `nullable` | This column already exists, but will now be `null` for lifetime memberships. |

**Note**: The `provider` column will be added. The `ends_at` column (aliased as `expires_at` in some contexts) already exists and will be made nullable if it isn't already, to signify a non-expiring (lifetime) subscription when `NULL`.
