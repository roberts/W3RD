# GamerProtocol.io API Overview

This repository contains the Laravel 12 API backend for the multi-platform game hub, powering all frontend brands (e.g., TokenGames.io, Cabin Games). It serves as the single source of truth for **user authentication**, **game state management**, **real-time updates**, and **subscription/billing logic**.

## Core Technology Stack

* **Framework:** Laravel 12
* **Database:** MySQL 8.0+ (Utilizing `JSON` type and efficient indexing)
* **Real-Time:** Laravel Reverb (WebSockets)
* **Authentication:** Laravel Sanctum (API Tokens)
* **Billing:** Laravel Cashier (Stripe)

## Key Features and Architectural Concepts

1.  **Domain-Driven Architecture:** Models, Controllers, and Services are organized into distinct domains (**Auth**, **Game**, **Billing**) for maintainability.
    * *See **`models.md`** and **`services.md`** for detailed structures.*
2.  **Two-Factor Authorization:** Every request requires a **Sanctum Bearer Token** (user authentication) and an **X-Client-Key** (application authorization) found in the `clients` table.
3.  **Real-Time Gameplay:** **Laravel Reverb** pushes instant game state updates to all subscribed clients.
4.  **Flexible Game State:** All game boards and hands are stored in the unified **`games`** table using the **JSON column type** (MySQL 8.0+ required) and Laravel casting. Game titles are defined as PHP enums (Validate Four, Checkers, Hearts, Spades), allowing for unlimited game variations without database schema changes.
5.  **Usage Metering:** Custom database logic handles complex quotas: **`strikes`** (daily free losses) and **`quotas`** (monthly member games).

## Required Composer Packages

These packages are essential for implementing the core features and security structures:

| Package | Purpose | Notes |
| :--- | :--- | :--- |
| `laravel/sanctum` | **API Authentication** | Handles user login, token generation, and Bearer token validation. |
| `laravel/cashier` | **Subscription Billing** | Integrates with Stripe for managing Member/Master subscriptions and webhooks. |
| `laravel/reverb` | **Real-Time Communication** | The official first-party WebSocket server for Laravel Broadcasting. |
| `guzzlehttp/guzzle` | **HTTP Client** | Needed within your **Billing Services** to verify mobile app store receipts and interact with external APIs. |
| `illuminate/database` | **Database Features** | Ensure support for **ULIDs** and **JSON column casting**. |

## Documentation Links

For full implementation details, refer to the following files in the `/docs` directory:

* **API Endpoints:** [api.md](docs/api.md)
* **Database Migrations:** [database.md](docs/database.md)
* **Model Definitions:** [models.md](docs/models.md)
* **Game Services:** [services.md](docs/services.md)
* **Admin Panel:** [admin.md](docs/admin.md)
* **Feature Expansion:** [expansion.md](docs/expansion.md)
* **Matchmaking:** [matchmaking.md](docs/matchmaking.md)
