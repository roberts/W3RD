# 📜 The Gamer Protocol API Constitution

This document establishes the foundational principles for the development of the Gamer Protocol API. Adherence to these standards is mandatory to ensure the creation of a high-quality, maintainable, and scalable product.

---

## Article I: Code Quality & Maintainability

All code contributed to the Gamer Protocol API must be clean, readable, and built for the long term.

1.  **Embrace the Framework:** Business logic must be implemented using a **Service-Oriented Architecture**. Controllers will remain thin, delegating complex tasks to dedicated service classes. This enforces the **DRY (Don't Repeat Yourself)** principle and improves testability.
2.  **Leverage the Ecosystem:** Before writing custom solutions, developers must utilize the features provided by Laravel 12 and its installed packages. This includes, but is not limited to:
    *   **Eloquent:** Use relationships, scopes, and accessors/mutators to their full potential. Avoid raw DB queries.
    *   **Laravel Cashier:** All subscription and billing logic must be handled through Cashier's abstractions, not direct Stripe API calls.
    *   **Laravel Reverb:** All real-time client communication must be handled through Laravel's broadcasting system.
3.  **Strict Standards:** All code must adhere to the **PSR-12** coding standard. `laravel/pint` is the official tool for ensuring compliance and must be run before committing code.
4.  **Configuration over Code:** Environment-specific values, API keys, and other credentials must be stored in the `.env` file and accessed via the `config()` helper. No sensitive data shall be hard-coded.

---

## Article II: Testing Standards & Reliability

Quality is not optional. The application's reliability is guaranteed through a rigorous and comprehensive testing strategy using **Pest v4**.

1.  **Mandatory Feature Tests:** Every API endpoint **must** be covered by a Pest feature test. Each test file should validate:
    *   The primary success path (a "200-level" response).
    *   Authentication and authorization failures (401/403 responses).
    *   Validation errors for invalid request data (a 422 response with a predictable error structure).
2.  **Required Unit Tests:** Complex algorithms and business logic encapsulated within Service classes (e.g., matchmaking pairing, game-specific win conditions, AI move calculation) **must** have dedicated unit tests to validate their correctness in isolation.
3.  **The "Arrange, Act, Assert" Pattern:** All tests must be structured clearly, following the AAA pattern to ensure readability and maintainability.
4.  **Clean Test Environment:** Tests must not rely on a persistent local database state. Every test will use **factories and in-memory SQLite databases** to create a clean, predictable, and isolated environment.

---

## Article III: User Experience & API Consistency

The API is a product. Its design directly impacts the end-user experience and the development speed of all frontend applications. Consistency is paramount.

1.  **Predictable JSON Responses:** All API responses must adhere to a strict contract.
    *   **Success:** `2xx` responses will return a `{"data": {...}}` structure.
    *   **Client Error:** `4xx` responses will return `{"message": "...", "errors": {...}}` (for validation).
2.  **ULIDs for Public Identifiers:** All database models exposed to the public via an API endpoint (e.g., Games, Users) must use a `ulid` field for external identification. Primary integer IDs will not be exposed.
3.  **Dual-Factor Authorization:** Every authenticated request **must** be protected by two layers:
    *   A **Sanctum Bearer Token** to identify the `User`.
    *   An **`X-Interface-Key`** header to identify the `Interface` (the frontend application).
4.  **Real-Time First:** The user experience must be instant. Client-side polling is forbidden for game state. **Laravel Reverb** will be used to push immediate updates for game state changes, chat messages, and matchmaking status.

---

## Article IV: Performance & Scalability

The API must be fast, efficient, and ready to scale. Performance is a feature.

1.  **Database Query Optimization:** All database queries must be efficient. The **N+1 query problem is to be actively monitored for and eliminated**. Eager loading (`->with()`) must be used wherever appropriate.
2.  **Judicious Use of Redis:** For high-frequency, low-complexity tasks like matchmaking queues, caching, and managing job locks, **Redis must be used** instead of the primary MySQL database to ensure low latency.
3.  **Queue Everything:** Any task that is not required to provide an immediate response to the user **must** be offloaded to a queued job. This includes sending emails, processing webhooks, and running complex calculations.
4.  **Index with Intent:** All foreign key columns and any column frequently used in `WHERE` clauses must have a database index. Performance under load will be a key metric for code review.