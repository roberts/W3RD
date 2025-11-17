# Implementation Plan: Core API Endpoints

**Feature Spec**: [spec.md](./spec.md)  
**Branch**: `004-api-endpoints`  
**Created**: 2025-11-16  
**Author**: Gemini

## 1. Technical Context & Design

This feature involves creating a suite of RESTful API endpoints to support core client application functionality. The implementation will be done in Laravel, leveraging existing authentication and database structures.

-   **Framework**: Laravel
-   **Authentication**: Laravel Sanctum (`auth:sanctum` middleware).
-   **Database**: MySQL (using Eloquent ORM).
-   **Billing**:
    -   Stripe via `laravel/cashier`.
    -   Manual verification for Apple, Google, and Telegram In-App Purchases.
-   **Real-time Notifications**: Laravel Reverb (for future integration, though this plan focuses on database notifications).
-   **Key Dependencies**:
    -   `laravel/cashier`: For Stripe integration.
    -   `spatie/laravel-permission`: Assumed for admin roles (granting lifetime membership).
    -   [NEEDS CLARIFICATION: Specific libraries for Apple/Google/Telegram receipt validation, e.g., `revenuecat/purchases-php-sdk` or custom implementations].

## 2. Constitution Check

-   **Principle Adherence**:
    -   **Service-Oriented Architecture**: This plan adheres by creating distinct controllers and services for each resource (Titles, Games, Billing, etc.), keeping controllers thin.
    -   **Testing Standards**: All new endpoints and logic will require dedicated Feature and Unit tests.
    -   **API Consistency**: The plan follows RESTful principles, uses ULIDs for public IDs where appropriate, and enforces authentication via middleware.
    -   **Performance**: The plan assumes intelligent use of Eloquent, with opportunities for caching (e.g., for `/v1/titles` or `/v1/config`) to be considered during implementation.
-   **Violations**: None. The plan is in full alignment with the project's architectural principles.

## 3. Phase 0: Outline & Research

This phase focuses on resolving the single technical unknown: the best approach for server-side validation of mobile and third-party receipts.

-   **Task 1: Research Apple App Store Receipt Validation**
    -   **Objective**: Determine the best library or method for validating App Store receipts.
    -   **Approach**: Investigate official Apple documentation for `verifyReceipt`, and evaluate PHP libraries that simplify this process.
-   **Task 2: Research Google Play Store Purchase Validation**
    -   **Objective**: Determine the best library or method for validating Google Play purchases.
    -   **Approach**: Research the Google Play Developer API (specifically the `purchases.products.get` endpoint) and evaluate PHP client libraries.
-   **Task 3: Research Telegram Mini App Payment Validation**
    -   **Objective**: Understand the process for verifying payments made with Telegram Stars.
    -   **Approach**: Review Telegram's official documentation for Mini Apps and payment processing.
-   **Task 4: Evaluate Unified Receipt Validation Libraries**
    -   **Objective**: Investigate if a single library like `revenuecat/purchases-php-sdk` can handle multiple platforms to reduce code duplication.
    -   **Approach**: Compare the benefits of an all-in-one solution versus platform-specific implementations.

**Deliverable**: A `research.md` file in the feature spec directory with a clear decision and rationale for the chosen validation libraries/methods.

## 4. Phase 1: Design & Contracts

This phase focuses on defining the data structures and API contracts before writing any implementation code.

-   **Task 1: Create `data-model.md`**
    -   **Objective**: Formally document the database schema changes required.
    -   **Details**:
        -   Define the structure for the new `notifications` table.
        -   Update the `subscriptions` table to include a `provider` column and a nullable `expires_at` for lifetime memberships.
        -   Update the `users` table to include new profile fields like `bio` and `social_links` (as a JSON column).
-   **Task 2: Create OpenAPI Specification**
    -   **Objective**: Generate a formal API contract for all new endpoints.
    -   **Details**: Create an `openapi.yaml` file in the `/contracts` directory. This file will define all paths, methods, request bodies, and response schemas for the new endpoints outlined in the feature spec.
-   **Task 3: Create `quickstart.md`**
    -   **Objective**: Provide a simple guide for client developers to start using the new API.
    -   **Details**: Include `curl` examples for key endpoints like `GET /v1/titles`, `GET /v1/me/profile`, and the authentication flow.
-   **Task 4: Update Agent Context**
    -   **Objective**: Inform the AI agent about the new technology choices.
    -   **Action**: Run the `.specify/scripts/bash/update-agent-context.sh copilot` script to add the chosen receipt validation libraries to the context file.

## 5. Phase 2: Implementation Plan

## 5. Phase 2: Implementation Plan

This phase details the step-by-step process for building the Core API Endpoints.

### Step 1: Setup & Configuration
-   **Task 1.1: Install Dependencies**
    -   Run `composer require google/apiclient web-token/jwt-framework`.
-   **Task 1.2: Configure Services**
    -   Add Google Cloud service account credentials to the `.env` file.
    -   Add App Store Connect API keys (Key ID, Issuer ID, Bundle ID, private key) to the `.env` file.
    -   Add the Telegram Bot Token to the `.env` file.

### Step 2: Database
-   **Task 2.1: Create `notifications` Migration**
    -   Generate a migration to create the `notifications` table as defined in `data-model.md`.
-   **Task 2.2: Create `users` Table Migration**
    -   Generate a migration to add `bio` and `social_links` columns to the `users` table.
-   **Task 2.3: Create `subscriptions` Table Migration**
    -   Generate a migration to add the `provider` column to the `subscriptions` table.
-   **Task 2.4: Run Migrations**
    -   Execute `php artisan migrate`.

### Step 3: Models & Services
-   **Task 3.1: Create `Notification` Model**
    -   Generate the `App\Models\Notification` Eloquent model.
-   **Task 3.2: Update `User` Model**
    -   Add `bio` and `social_links` to the `$fillable` array.
    -   Add a `notifications()` relationship.
-   **Task 3.3: Update `Subscription` Model**
    -   Add `provider` to the `$fillable` array.
-   **Task 3.4: Create Validation Services**
    -   Create `App\Services\AppleReceiptValidator` to handle App Store Server API communication.
    -   Create `App\Services\GooglePurchaseValidator` to handle Google Play Developer API communication.
    -   Create `App\Services\TelegramPaymentValidator` to handle Telegram payment hash validation.
-   **Task 3.5: Create Profile Service**
    -   Create `App\Services\ProfileService` to encapsulate the logic for updating user profiles.

### Step 4: Routing
-   **Task 4.1: Add API Routes**
    -   In `routes/api.php`, add all the new endpoints defined in `openapi.yaml`.
    -   Group protected endpoints under the `auth:sanctum` middleware.
    -   Link routes to their respective controller methods.

### Step 5: Controllers
-   **Task 5.1: Generate Controllers**
    -   Generate all required controllers: `TitleController`, `GameController`, `ProfileController`, `LeaderboardController`, `BillingController`, `NotificationController`, `StatusController`.
-   **Task 5.2: Implement Public Endpoints**
    -   `TitleController@index`: Return a list of game titles.
    -   `StatusController@index`: Return the API status.
-   **Task 5.3: Implement Game Endpoints**
    -   `GameController@index`, `GameController@show`, `GameController@history`.
-   **Task 5.4: Implement User Profile Endpoints**
    -   `ProfileController@show`: Return the authenticated user's profile.
    -   `ProfileController@update`: Use `ProfileService` to update the profile.
-   **Task 5.5: Implement Billing Endpoints**
    -   `BillingController@createStripeSubscription`: Use Cashier to create a checkout session.
    -   `BillingController@manageSubscription`: Use Cashier to create a customer portal session.
    -   `BillingController@verifyReceipt`: Use the appropriate validation service based on the provider parameter.
-   **Task 5.6: Implement Notification Endpoints**
    -   `NotificationController@index`: Return the user's notifications.

### Step 6: Testing
-   **Task 6.1: Write Feature Tests**
    -   Create a feature test for every new API endpoint.
    -   Test success cases (200 OK), authentication failures (401), authorization failures (403), not found errors (404), and validation errors (422).
-   **Task 6.2: Write Unit Tests**
    -   Create unit tests for the validation services (`AppleReceiptValidator`, `GooglePurchaseValidator`, `TelegramPaymentValidator`). Mock external API calls.
    -   Create unit tests for the `ProfileService`.

