# Task Breakdown: Core API Endpoints

This document outlines the tasks required to implement the "Core API Endpoints" feature.

## Phase 1: Setup & Configuration

- [X] T001 Install dependencies with `composer require google/apiclient web-token/jwt-framework`
- [X] T002 Add Google Cloud, App Store Connect, and Telegram Bot credentials to `config/services.php` and `.env.example`
- [X] T003 Create `config/protocol.php` for storing game titles and other platform-specific settings

## Phase 2: Foundational - Database & Models

- [X] T004 Create migration for the `notifications` table in `database/migrations/`
- [X] T005 Create migration to add `bio` and `social_links` to the `users` table in `database/migrations/`
- [X] T006 Create migration to add `provider` column to the `subscriptions` table in `database/migrations/`
- [X] T007 Create migration for the `rematch_requests` table in `database/migrations/`
- [X] T008 Run migrations with `php artisan migrate`
- [X] T009 Create the `App\Models\Alert` model (table: `notifications`)
- [X] T010 Create the `App\Models\RematchRequest` model with relationships
- [X] T011 Update the `App\Models\User` model to include `bio`, `social_links`, and the `alerts()` relationship
- [X] T012 Update the `App\Models\Subscription` model to include `provider` in the `$fillable` array

## Phase 3: User Story 1 - Player Explores Games and Profiles

**Goal**: A user can browse games, leaderboards, and their own stats.
**Independent Test**: A client can make `GET` requests to `/v1/titles`, `/v1/leaderboard/{gameTitle}`, `/v1/me/stats`, and `/v1/me/levels` and receive valid data.

- [X] T013 [US1] Create `App\Http\Controllers\Api\V1\TitleController`
- [X] T014 [US1] Implement `TitleController@index` to return a list of game titles from `config/protocol.php`
- [X] T015 [US1] Create `App\Http\Controllers\Api\V1\LeaderboardController`
- [X] T016 [US1] Implement `LeaderboardController@show` to return ranked users for a given game title
- [X] T017 [US1] Create `App\Http\Controllers\Api\V1\UserStatsController`
- [X] T018 [US1] Implement `UserStatsController@show` to return global stats for the authenticated user
- [X] T019 [US1] Create `App\Http\Controllers\Api\V1\UserLevelsController`
- [X] T020 [US1] Implement `UserLevelsController@show` to return game-specific levels for the authenticated user
- [X] T021 [US1] Add routes for `titles`, `leaderboards`, `me/stats`, and `me/levels` to `routes/api.php`

## Phase 4: User Story 2 - Player Manages and Resumes Active Games

**Goal**: A user can see their active games and resume a session.
**Independent Test**: A client can call `GET /v1/games` to list games and `GET /v1/games/{gameUlid}` to get details for a specific game.

- [X] T022 [US2] Create `App\Http\Controllers\Api\V1\GameController`
- [X] T023 [US2] Implement `GameController@index` to list games for the authenticated user
- [X] T024 [US2] Implement `GameController@show` to return the state of a specific game
- [X] T025 [US2] Add routes for `games` and `games/{gameUlid}` to `routes/api.php`

## Phase 5: User Story 10 - User Customizes Their Public Profile

**Goal**: A user can view and update their public-facing profile.
**Independent Test**: A client can `GET` profile data from `/v1/me/profile`, `PATCH` new data to it, and see the changes reflected.

- [X] T026 [US10] Create `App\Services\ProfileService` to handle profile update logic
- [X] T027 [US10] Create `App\Http\Controllers\Api\V1\ProfileController`
- [X] T028 [US10] Implement `ProfileController@show` to return the authenticated user's public profile
- [X] T029 [US10] Implement `ProfileController@update` using `ProfileService`
- [X] T030 [US10] Add `GET` and `PATCH` routes for `/v1/me/profile` to `routes/api.php`

## Phase 6: User Story 11 - Player Requests a Rematch

**Goal**: A player can request a rematch after completing a game and the opponent can accept or decline.
**Independent Test**: A client can create a rematch request from a completed game, and the opponent receives a notification and can respond.

- [X] T031 [US11] Create `App\Services\RematchService` to handle rematch logic
- [X] T032 [US11] Implement `GameController@requestRematch` in `App\Http\Controllers\Api\V1\GameController`
- [X] T033 [US11] Create `App\Http\Controllers\Api\V1\RematchController`
- [X] T034 [US11] Implement `RematchController@accept` to accept a rematch request
- [X] T035 [US11] Implement `RematchController@decline` to decline a rematch request
- [X] T036 [US11] Create `App\Jobs\ExpireRematchRequests` job to handle automatic expiration
- [X] T037 [US11] Create events: `RematchRequested`, `RematchAccepted`, `RematchDeclined`, `RematchExpired`
- [X] T038 [US11] Create listeners for rematch events to send notifications
- [X] T039 [US11] Add rematch routes to `routes/api.php`
- [X] T040 [US11] Schedule `ExpireRematchRequests` job in `app/Console/Kernel.php`

## Phase 7: User Story 3 - Web User Subscribes via Stripe

**Goal**: A web user can subscribe to a plan and manage their subscription via Stripe.
**Independent Test**: A client can get a redirect URL from `/v1/billing/subscribe` and `/v1/billing/manage`. The `/v1/stripe/webhook` endpoint must correctly update user subscription status.

- [X] T041 [US3] Create `App\Http\Controllers\Api\V1\BillingController`
- [X] T042 [US3] Implement `BillingController@getPlans` to return subscription plans
- [X] T043 [US3] Implement `BillingController@getStatus` to return the user's subscription status
- [X] T044 [US3] Implement `BillingController@createStripeSubscription` to return a Stripe Checkout session URL
- [X] T045 [US3] Implement `BillingController@manageSubscription` to return a Stripe Customer Portal URL
- [X] T046 [US3] Create `App\Http\Controllers\Api\V1\StripeWebhookController`
- [X] T047 [US3] Implement `StripeWebhookController@handle` to process incoming Stripe events
- [X] T048 [US3] Add routes for billing endpoints and the Stripe webhook to `routes/api.php`

## Phase 8: User Story 4 & 5 - Mobile & Telegram Subscriptions

**Goal**: A user can subscribe via native IAP (Apple/Google) or Telegram Stars.
**Independent Test**: The `/v1/billing/{provider}/verify` endpoints must correctly validate a test receipt and update the user's subscription status.

- [X] T049 [P] [US4] Create `App\Services\AppleReceiptValidator` to communicate with the App Store Server API
- [X] T050 [P] [US4] Create `App\Services\GooglePurchaseValidator` to communicate with the Google Play Developer API
- [X] T051 [P] [US5] Create `App\Services\TelegramPaymentValidator` to validate Telegram payment data
- [X] T052 [US4, US5] Implement `BillingController@verifyReceipt` to use the appropriate validation service based on the provider
- [X] T053 [US4, US5] Add the `/v1/billing/{provider}/verify` route to `routes/api.php`

## Phase 9: User Story 8 & 9 - Alerts & Game History

**Goal**: A user can view their alerts and the move history of a game.
**Independent Test**: A client can fetch data from `/v1/me/alerts` and `/v1/games/{gameUlid}/history`.

- [X] T054 [P] [US8] Create `App\Http\Controllers\Api\V1\AlertController`
- [X] T055 [P] [US8] Implement `AlertController@index` to list user alerts
- [X] T056 [P] [US8] Implement `AlertController@markAsRead`
- [X] T057 [P] [US9] Implement `GameController@history` to return a game's move history
- [X] T058 [US8, US9] Add routes for alerts and game history to `routes/api.php`

## Phase 10: User Story 7 - API Health Check

**Goal**: A developer or admin can check the API's health.
**Independent Test**: A `GET` request to `/v1/status` returns a `200 OK` with `{"status": "ok"}`.

- [X] T059 [US7] Create `App\Http\Controllers\Api\V1\StatusController`
- [X] T060 [US7] Implement `StatusController@index` to return the API status
- [X] T061 [US7] Add the `/v1/status` route to `routes/api.php`

## Phase 11: Polish & Finalization

- [ ] T062 Create Feature tests for all new API endpoints
- [ ] T063 Create Unit tests for all new Service classes
- [ ] T064 Review and update API documentation in `docs/api.md`
- [ ] T065 Remove temporary or deprecated routes (e.g., old `GET /v1/games/{gameTitle}/rules` if it existed)

---

## Dependencies

- **US1** is a prerequisite for **US2**, **US3**, **US4**, **US5**, **US10**, and **US11** as it provides the core context for games and users.
- **US2** is a prerequisite for **US11** (rematch) as players need to be able to view and complete games first.
- **US3**, **US4**, and **US5** (Billing) can be worked on in parallel after **US1** is complete.
- **US7**, **US8**, and **US9** are largely independent and can be worked on in parallel with other stories.
- **US11** (Rematch) depends on **US2** and **US8** (for notifications).

## Parallel Execution Examples

- **Story 1**:
  - `T013` & `T015` & `T017` & `T019` (controller creation) can be done in parallel.
- **Story 11** (Rematch):
  - `T037` (events) and `T036` (job) can be done in parallel after the service is created.
- **Story 4 & 5**:
  - `T049`, `T050`, and `T051` are fully independent and can be done in parallel.

## Implementation Strategy

The implementation will follow the phases outlined above, prioritizing the user stories as numbered. This allows for an MVP focused on core game discovery and exploration (US1, US2, US10), followed by enhanced player engagement (US11 - Rematch), then monetization (US3, US4, US5), and finally supporting features (US7, US8, US9). Each user story represents an independently testable and deliverable increment of functionality.

The rematch feature (US11) has been prioritized at P3 because it's a high-value retention feature that captures player momentum immediately after a game ends, keeping them engaged with the platform.
