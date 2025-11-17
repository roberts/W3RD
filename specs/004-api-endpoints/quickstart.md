# Quickstart: Core API

This guide provides a quick way to start interacting with the core API using `curl`.

## 1. Authentication

First, you need to authenticate to get a bearer token. Use the existing `/login` endpoint.

```bash
# Replace with your actual credentials
EMAIL="user@example.com"
PASSWORD="password"
DEVICE_NAME="my-test-client"

TOKEN=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "'"$EMAIL"'",
    "password": "'"$PASSWORD"'",
    "device_name": "'"$DEVICE_NAME"'"
  }' | sed -n 's/.*"token":"\([^"]*\)".*/\1/p')

echo "API Token: $TOKEN"
```

## 2. Set API Token

Export the token to a variable for easy use in subsequent requests.

```bash
export API_TOKEN="$TOKEN"
```

## 3. Get Game Titles

Retrieve the list of all available game titles. This endpoint does not require authentication.

```bash
curl -X GET http://localhost:8000/v1/titles \
  -H "Accept: application/json"
```

## 4. Get Your Profile

Retrieve your public user profile. This requires authentication.

```bash
curl -X GET http://localhost:8000/v1/me/profile \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $API_TOKEN"
```

## 5. Update Your Profile

Update your public profile information, such as your bio.

```bash
curl -X PATCH http://localhost:8000/v1/me/profile \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "bio": "A new bio for my profile.",
    "social_links": {
      "twitter": "https://twitter.com/new_handle"
    }
  }'
```
