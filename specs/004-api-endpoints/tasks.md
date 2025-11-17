# Task Breakdown: Core API Endpoints

This document outlines the tasks required to implement the "Core API Endpoints" feature.

## Phase 1: Setup & Configuration

- [ ] T001 Install dependencies with `composer require google/apiclient web-token/jwt-framework`
- [ ] T002 Add Google Cloud, App Store Connect, and Telegram Bot credentials to `config/services.php` and `.env.example`
- [ ] T003 Create `config/protocol.php` for storing game titles and other platform-specific settings

## Phase 2: Foundational - Database & Models

- [ ] T004 Create migration for the `notifications` table in `database/migrations/`
- [ ] T005 Create migration to add `bio` and `social_links` to the `users` table in `database/migrations/`
- [ ] T006 Create migration to add `provider` column to the `subscriptions` table in `database/migrations/`
- [ ] T007 Run migrations with `php artisan migrate`
- [ ] T008 Create the `App\Models\Notification` model
- [ ] T009 Update the `App\Models\User` model to include `bio`, `social_links`, and the `notifications()` relationship
- [ ] T010 Update the `App\Models\Subscription` model to include `provider` in the `$fillable` array

## Phase 3: User Story 1 - Player Explores Games and Profiles

**Goal**: A user can browse games, leaderboards, and their own stats.
**Independent Test**: A client can make `GET` requests to `/v1/titles`, `/v1/leaderboards/{gameTitle}`, `/v1/me/stats`, and `/v1/me/levels` and receive valid data.

- [ ] T011 [US1] Create `App\Http\Controllers\Api\V1\TitleController`
- [ ] T012 [US1] Implement `TitleController@index` to return a list of game titles from `config/protocol.php`
- [ ] T013 [US1] Create `App\Http\Controllers\Api\V1\LeaderboardController`
- [ ] T014 [US1] Implement `LeaderboardController@show` to return ranked users for a given game title
- [ ] T015 [US1] Create `App\Http\Controllers\Api\V1\UserStatsController`
- [ ] T016 [US1] Implement `UserStatsController@show` to return global stats for the authenticated user
- [ ] T017 [US1] Create `App\Http\Controllers\Api\V1\UserLevelsController`
- [ ] T018 [US1] Implement `UserLevelsController@show` to return game-specific levels for the authenticated user
- [ ] T019 [US1] Add routes for `titles`, `leaderboards`, `me/stats`, and `me/levels` to `routes/api.php`

## Phase 4: User Story 2 - Player Manages and Resumes Active Games

**Goal**: A user can see their active games and resume a session.
**Independent Test**: A client can call `GET /v1/games` to list games and `GET /v1/games/{gameUlid}` to get details for a specific game.

- [ ] T020 [US2] Create `App\Http\Controllers\Api\V1\GameController`
- [ ] T021 [US2] Implement `GameController@index` to list games for the authenticated user
- [ ] T022 [US2] Implement `GameController@show` to return the state of a specific game
- [ ] T023 [US2] Add routes for `games` and `games/{gameUlid}` to `routes/api.php`

## Phase 5: User Story 10 - User Customizes Their Public Profile

**Goal**: A user can view and update their public-facing profile.
**Independent Test**: A client can `GET` profile data from `/v1/me/profile`, `PATCH` new data to it, and see the changes reflected.

- [ ] T024 [US10] Create `App\Services\ProfileService` to handle profile update logic
- [ ] T025 [US10] Create `App\Http\Controllers\Api\V1\ProfileController`
- [ ] T026 [US10] Implement `ProfileController@show` to return the authenticated user's public profile
- [ ] T027 [US10] Implement `ProfileController@update` using `ProfileService`
- [ ] T028 [US10] Add `GET` and `PATCH` routes for `/v1/me/profile` to `routes/api.php`

## Phase 6: User Story 3 - Web User Subscribes via Stripe

**Goal**: A web user can subscribe to a plan and manage their subscription via Stripe.
**Independent Test**: A client can get a redirect URL from `/v1/billing/subscribe` and `/v1/billing/manage`. The `/webhooks/stripe` endpoint must correctly update user subscription status.

- [ ] T029 [US3] Create `App\Http\Controllers\Api\V1\BillingController`
- [ ] T030 [US3] Implement `BillingController@getPlans` to return subscription plans
- [ ] T031 [US3] Implement `BillingController@getStatus` to return the user's subscription status
- [ ] T032 [US3] Implement `BillingController@createStripeSubscription` to return a Stripe Checkout session URL
- [ ] T033 [US3] Implement `BillingController@manageSubscription` to return a Stripe Customer Portal URL
- [ ] T034 [US3] Create `App\Http\Controllers\Api\V1\StripeWebhookController`
- [ ] T035 [US3] Implement `StripeWebhookController@handle` to process incoming Stripe events
- [ ] T036 [US3] Add routes for billing endpoints and the Stripe webhook to `routes/api.php`

## Phase 7: User Story 4 & 5 - Mobile & Telegram Subscriptions

**Goal**: A user can subscribe via native IAP (Apple/Google) or Telegram Stars.
**Independent Test**: The `/v1/billing/{provider}/verify` endpoints must correctly validate a test receipt and update the user's subscription status.

- [ ] T037 [P] [US4] Create `App\Services\AppleReceiptValidator` to communicate with the App Store Server API
- [ ] T038 [P] [US4] Create `App\Services\GooglePurchaseValidator` to communicate with the Google Play Developer API
- [ ] T039 [P] [US5] Create `App\Services\TelegramPaymentValidator` to validate Telegram payment data
- [ ] T040 [US4, US5] Implement `BillingController@verifyReceipt` to use the appropriate validation service based on the provider
- [ ] T041 [US4, US5] Add the `/v1/billing/{provider}/verify` route to `routes/api.php`

## Phase 8: User Story 8 & 9 - Notifications & Game History

**Goal**: A user can view their notifications and the move history of a game.
**Independent Test**: A client can fetch data from `/v1/me/notifications` and `/v1/games/{gameUlid}/history`.

- [ ] T042 [P] [US8] Create `App\Http\Controllers\Api\V1\NotificationController`
- [ ] T043 [P] [US8] Implement `NotificationController@index` to list user notifications
- [ ] T044 [P] [US8] Implement `NotificationController@markAsRead`
- [ ] T045 [P] [US9] Implement `GameController@history` to return a game's move history
- [ ] T046 [US8, US9] Add routes for notifications and game history to `routes/api.php`

## Phase 9: User Story 7 - API Health Check

**Goal**: A developer or admin can check the API's health.
**Independent Test**: A `GET` request to `/v1/status` returns a `200 OK` with `{"status": "ok"}`.

- [ ] T047 [US7] Create `App\Http\Controllers\Api\V1\StatusController`
- [ ] T048 [US7] Implement `StatusController@index` to return the API status
- [ ] T049 [US7] Add the `/v1/status` route to `routes/api.php`

## Phase 10: Polish & Finalization

- [ ] T050 Create Feature tests for all new API endpoints
- [ ] T051 Create Unit tests for all new Service classes
- [ ] T052 Review and update API documentation in `docs/api.md`
- [ ] T053 Remove temporary or deprecated routes (e.g., `GET /v1/games/{gameTitle}/rules`)

---

## Dependencies

- **US1** is a prerequisite for **US2**, **US3**, **US4**, **US5**, and **US10** as it provides the core context for games and users.
- **US3**, **US4**, and **US5** (Billing) can be worked on in parallel after **US1** is complete.
- **US7**, **US8**, and **US9** are largely independent and can be worked on in parallel with other stories.

## Parallel Execution Examples

- **Story 1**:
  - `T011` & `T013` can be done in parallel.
  - `T015` & `T017` can be done in parallel.
- **Story 4 & 5**:
  - `T037`, `T038`, and `T039` are fully independent and can be done in parallel.

## Implementation Strategy

The implementation will follow the phases outlined above, prioritizing the user stories as numbered. This allows for an MVP focused on core game discovery and exploration (US1, US2, US10), followed by monetization (US3, US4, US5), and finally supporting features (US7, US8, US9). Each user story represents an independently testable and deliverable increment of functionality.
