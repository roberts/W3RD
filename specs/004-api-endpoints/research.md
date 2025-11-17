# Research: In-App Purchase Validation

This document outlines the research and decisions for implementing server-side validation for Apple, Google, and Telegram in-app purchases.

## 1. Apple App Store

-   **Method**: The legacy `verifyReceipt` endpoint is deprecated. The current and recommended method is the **App Store Server API**.
-   **Authentication**: Requests to the App Store Server API must be authenticated using a JSON Web Token (JWT).
-   **JWT Generation**:
    -   **Algorithm**: `ES256`
    -   **Header**:
        -   `alg`: "ES256"
        -   `kid`: The private key ID from App Store Connect.
        -   `typ`: "JWT"
    -   **Payload**:
        -   `iss`: The issuer ID from App Store Connect.
        -   `iat`: Issued at time (current Unix time).
        -   `exp`: Expiration time (not more than 60 minutes after `iat`).
        -   `aud`: "appstoreconnect-v1"
        -   `nonce`: A unique random value.
        -   `bid`: The bundle ID of the app.
-   **PHP Implementation**: A library will be needed to generate the ES256-signed JWT. `web-token/jwt-framework` is a popular and robust choice.
-   **Decision**: We will use the App Store Server API and the `web-token/jwt-framework` library to handle authentication and validation.

## 2. Google Play Store

-   **Method**: The **Google Play Developer API** is used for validation.
-   **Endpoint**: `GET https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{packageName}/purchases/products/{productId}/tokens/{token}`
-   **Authentication**: Requires an OAuth 2.0 access token. This can be obtained using a service account and the appropriate Google API client library for PHP.
-   **PHP Implementation**: The `google/apiclient` package is the official Google client library for PHP and will be used to manage authentication and make requests to the API.
-   **Decision**: We will use the `google/apiclient` library to interact with the Google Play Developer API for purchase validation.

## 3. Telegram Mini Apps

-   **Method**: Validation is performed by verifying a hash sent with the payment data.
-   **Process**:
    1.  Construct a `data-check-string` by sorting all received fields alphabetically and concatenating them in the format `key=<value>`, separated by newline characters (`\n`).
    2.  Create a secret key by generating an HMAC-SHA256 signature of the bot's token with the constant string "WebAppData" as the key.
    3.  Calculate the HMAC-SHA256 signature of the `data-check-string` using the secret key.
    4.  Compare the hexadecimal representation of the calculated signature with the `hash` parameter received from Telegram.
-   **PHP Implementation**: This can be implemented directly using PHP's built-in `hash_hmac()` function. No external library is required.
-   **Decision**: We will implement the Telegram validation logic within our own service class using native PHP functions.

## 4. Unified Libraries

-   **Evaluation**: Libraries like `revenuecat/purchases-php-sdk` offer a unified interface for multiple platforms.
-   **Pros**:
    -   Simplifies the codebase by abstracting platform-specific details.
    -   Handles edge cases and API changes from the providers.
-   **Cons**:
    -   Adds an external dependency and a potential point of failure.
    -   May have a subscription cost for the service.
    -   Less control over the exact validation logic.
-   **Decision**: For this project, we will implement platform-specific validation logic ourselves. This provides maximum control, avoids additional third-party dependencies and costs, and the individual implementation for each platform is straightforward with the chosen libraries and methods. This approach also aligns with the goal of building a self-contained, robust system.
